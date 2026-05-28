<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../grupos.php");
    exit();
}

$id_alumno     = (int) $_POST['id_alumno'];
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

try {
    // Validar que no tenga calificaciones registradas (si las tiene, no debería darse de baja sin más)
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM Calificacion 
        WHERE id_alumno = ? AND num_grupo = ? AND id_profesor = ? 
          AND clave_materia = ? AND id_semestre = ?
    ");
    $check->execute([$id_alumno, $num_grupo, $id_profesor, $clave_materia, $id_semestre]);
    
    if ($check->fetchColumn() > 0) {
        $msg = "No se puede dar de baja: el alumno tiene calificaciones registradas. " .
               "Elimina sus calificaciones primero o cambia su estado manualmente.";
    } else {
        // Marca la inscripción como Baja (estado lógico, no la borramos)
        $sql = "UPDATE Inscripcion SET Estado = 'Baja' 
                WHERE id_alumno = ? AND num_grupo = ? AND id_profesor = ? 
                  AND clave_materia = ? AND id_semestre = ?";
        $pdo->prepare($sql)->execute([
            $id_alumno, $num_grupo, $id_profesor, $clave_materia, $id_semestre
        ]);
        $msg = "Alumno dado de baja del grupo";
    }
    
    header("Location: ../grupo_inscripciones.php?$params&msg=" . urlencode($msg));
} catch (PDOException $e) {
    header("Location: ../grupo_inscripciones.php?$params&msg=" . urlencode("Error: " . $e->getMessage()));
}