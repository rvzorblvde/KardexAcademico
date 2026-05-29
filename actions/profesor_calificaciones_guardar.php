<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth_profesor.php';
require_once __DIR__ . '/../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profesor.php");
    exit();
}

$id_profesor   = $_SESSION['user_id'];  
$num_grupo     = (int) $_POST['num_grupo'];
$clave_materia = trim($_POST['clave_materia']);
$id_semestre   = trim($_POST['id_semestre']);

$params = http_build_query([
    'num_grupo'     => $num_grupo,
    'clave_materia' => $clave_materia,
    'id_semestre'   => $id_semestre
]);
$volver = "../profesor_calificar.php?$params";


$stmt = $pdo->prepare("
    SELECT s.activo 
    FROM Grupo g 
    INNER JOIN Semestre s ON g.id_semestre = s.id_semestre
    WHERE g.num_grupo = ? AND g.id_profesor = ? 
      AND g.clave_materia = ? AND g.id_semestre = ?
");
$stmt->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
$row = $stmt->fetch();

if (!$row) {
    header("Location: ../profesor.php?msg=" . urlencode("Grupo no encontrado o no te pertenece"));
    exit();
}
if (!$row['activo']) {
    header("Location: $volver&msg=" . urlencode("No se pueden modificar calificaciones de semestres archivados"));
    exit();
}

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
            if (!in_array($tipo, $tipos_validos)) continue;

            foreach ($parciales as $num_parcial => $valor) {
                $num_parcial = ($tipo === 'Parcial') ? (int) $num_parcial : null;
                $valor_trim = trim($valor);

                if ($valor_trim === '') {
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
                    $errores[] = "Alumno $id_alumno / $tipo: fuera de rango";
                    continue;
                }

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

    $msg = "Calificaciones guardadas ($guardados nuevas/actualizadas, $borrados eliminadas)";
    if (count($errores) > 0) $msg .= ". Errores: " . implode("; ", $errores);
    
    header("Location: $volver&msg=" . urlencode($msg));
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header("Location: $volver&msg=" . urlencode("Error: " . $e->getMessage()));
}