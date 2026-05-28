<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../grupos.php");
    exit();
}

$num_grupo     = (int) $_POST['num_grupo'];
$id_profesor   = (int) $_POST['id_profesor'];
$clave_materia = trim($_POST['clave_materia']);
$id_semestre   = trim($_POST['id_semestre']);

$params = http_build_query([
    'num_grupo'     => $num_grupo,
    'id_profesor'   => $id_profesor,
    'clave_materia' => $clave_materia,
    'id_semestre'   => $id_semestre
]);
$volver = "../grupo_calificaciones.php?$params";

$calif_data = $_POST['calif'] ?? [];
$inasist_data = $_POST['inasist'] ?? [];

$tipos_validos = ['Parcial', 'EO', 'EE', 'ET', 'ER'];
$guardados = 0;
$borrados = 0;
$errores = [];

try {
    $pdo->beginTransaction();

    foreach ($calif_data as $id_alumno => $tipos) {
        $id_alumno = (int) $id_alumno;
        $inasistencias = (int) ($inasist_data[$id_alumno] ?? 0);

        foreach ($tipos as $tipo => $parciales) {
            if (!in_array($tipo, $tipos_validos)) {
                continue;
            }

            foreach ($parciales as $num_parcial => $valor) {
                // El input usa "0" para tipos no-Parcial; convertir a NULL para la BD
                $num_parcial = ($tipo === 'Parcial') ? (int) $num_parcial : null;
                
                $valor_trim = trim($valor);

                if ($valor_trim === '') {
                    // Celda vacía: si existía calificación previa, la borramos
                    $sql_del = "DELETE FROM Calificacion 
                                WHERE id_alumno = ? AND num_grupo = ? AND id_profesor = ? 
                                  AND clave_materia = ? AND id_semestre = ?
                                  AND tipo = ? AND " . 
                                  ($num_parcial === null ? "num_parcial IS NULL" : "num_parcial = ?");
                    
                    $params_del = [$id_alumno, $num_grupo, $id_profesor, $clave_materia, $id_semestre, $tipo];
                    if ($num_parcial !== null) $params_del[] = $num_parcial;
                    
                    $stmt = $pdo->prepare($sql_del);
                    $stmt->execute($params_del);
                    if ($stmt->rowCount() > 0) $borrados++;
                    continue;
                }

                $calif = (float) $valor_trim;
                if ($calif < 0 || $calif > 10) {
                    $errores[] = "Alumno $id_alumno / $tipo: calificación fuera de rango (0-10)";
                    continue;
                }

                // INSERT ... ON DUPLICATE KEY UPDATE (upsert)
                $sql = "INSERT INTO Calificacion 
                            (id_alumno, num_grupo, id_profesor, clave_materia, id_semestre,
                             tipo, num_parcial, calificacion, inasistencias)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            calificacion = VALUES(calificacion),
                            inasistencias = VALUES(inasistencias)";
                
                $pdo->prepare($sql)->execute([
                    $id_alumno, $num_grupo, $id_profesor, $clave_materia, $id_semestre,
                    $tipo, $num_parcial, $calif, $inasistencias
                ]);
                $guardados++;
            }
        }
    }

    $pdo->commit();

    $msg = "Calificaciones guardadas. Registros guardados: $guardados, eliminados: $borrados.";
    if (count($errores) > 0) {
        $msg .= " Errores: " . implode("; ", $errores);
    }
    header("Location: $volver&msg=" . urlencode($msg));
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header("Location: $volver&msg=" . urlencode("Error: " . $e->getMessage()));
}