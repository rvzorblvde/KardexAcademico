<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../grupos.php");
    exit();
}

// Recibir datos nuevos
$num_grupo     = (int) $_POST['num_grupo'];
$id_profesor   = (int) $_POST['id_profesor'];
$clave_materia = trim($_POST['clave_materia']);
$id_semestre   = trim($_POST['id_semestre']);
$salon         = trim($_POST['salon']) ?: null;
$cupo          = (int) $_POST['cupo'];

// Recibir PK original si estamos editando
$es_edicion         = isset($_POST['num_grupo_orig']);
$num_grupo_orig     = $es_edicion ? (int) $_POST['num_grupo_orig'] : null;
$id_profesor_orig   = $es_edicion ? (int) $_POST['id_profesor_orig'] : null;
$clave_materia_orig = $es_edicion ? $_POST['clave_materia_orig'] : null;
$id_semestre_orig   = $es_edicion ? $_POST['id_semestre_orig'] : null;

// Validación
if ($num_grupo < 1) {
    header("Location: ../grupos.php?msg=" . urlencode("Número de grupo inválido"));
    exit();
}
if ($id_profesor < 1 || $clave_materia === '' || $id_semestre === '') {
    header("Location: ../grupos.php?msg=" . urlencode("Selecciona profesor, materia y semestre"));
    exit();
}
if ($cupo < 1) {
    header("Location: ../grupos.php?msg=" . urlencode("Cupo inválido"));
    exit();
}

try {
    if ($es_edicion) {
        // ===== ACTUALIZAR =====
        // Si la PK cambia y hay inscripciones, bloqueamos
        $pk_cambia = (
            $num_grupo     !== $num_grupo_orig ||
            $id_profesor   !== $id_profesor_orig ||
            $clave_materia !== $clave_materia_orig ||
            $id_semestre   !== $id_semestre_orig
        );

        if ($pk_cambia) {
            $check = $pdo->prepare("
                SELECT COUNT(*) FROM Inscripcion 
                WHERE num_grupo = ? AND id_profesor = ? 
                  AND clave_materia = ? AND id_semestre = ?
            ");
            $check->execute([$num_grupo_orig, $id_profesor_orig, $clave_materia_orig, $id_semestre_orig]);
            if ($check->fetchColumn() > 0) {
                header("Location: ../grupos.php?msg=" . 
                    urlencode("No se puede cambiar la identificación del grupo: tiene inscripciones"));
                exit();
            }
        }

        $sql = "UPDATE Grupo 
                SET num_grupo = ?, id_profesor = ?, clave_materia = ?, id_semestre = ?, 
                    salon = ?, cupo = ?
                WHERE num_grupo = ? AND id_profesor = ? 
                  AND clave_materia = ? AND id_semestre = ?";
        $pdo->prepare($sql)->execute([
            $num_grupo, $id_profesor, $clave_materia, $id_semestre, $salon, $cupo,
            $num_grupo_orig, $id_profesor_orig, $clave_materia_orig, $id_semestre_orig
        ]);
        $msg = "Grupo actualizado correctamente";
    } else {
        // ===== INSERTAR =====
        $sql = "INSERT INTO Grupo (num_grupo, id_profesor, clave_materia, id_semestre, salon, cupo) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $num_grupo, $id_profesor, $clave_materia, $id_semestre, $salon, $cupo
        ]);
        $msg = "Grupo creado. Recuerda asignarle horarios.";
    }

    header("Location: ../grupos.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    $errorMsg = $e->getCode() === '23000'
        ? "Ya existe un grupo con esa combinación de número, profesor, materia y semestre"
        : "Error: " . $e->getMessage();
    header("Location: ../grupos.php?msg=" . urlencode($errorMsg));
}