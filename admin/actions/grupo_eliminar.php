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

if ($num_grupo < 1 || $id_profesor < 1 || $clave_materia === '' || $id_semestre === '') {
    header("Location: ../grupos.php?msg=" . urlencode("Datos inválidos"));
    exit();
}

try {
    // Validar que no tenga inscripciones
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM Inscripcion 
        WHERE num_grupo = ? AND id_profesor = ? 
          AND clave_materia = ? AND id_semestre = ?
    ");
    $check->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
    $num_inscritos = $check->fetchColumn();
    
    if ($num_inscritos > 0) {
        $msg = "No se puede eliminar: el grupo tiene $num_inscritos inscripción(es).";
        header("Location: ../grupos.php?msg=" . urlencode($msg));
        exit();
    }

    $pdo->beginTransaction();
    
    $sqlHorarios = "DELETE FROM Horario 
                    WHERE num_grupo = ? AND id_profesor = ? 
                      AND clave_materia = ? AND id_semestre = ?";
    $pdo->prepare($sqlHorarios)->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
    
    $sqlGrupo = "DELETE FROM Grupo 
                 WHERE num_grupo = ? AND id_profesor = ? 
                   AND clave_materia = ? AND id_semestre = ?";
    $pdo->prepare($sqlGrupo)->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
    
    $pdo->commit();
    
    header("Location: ../grupos.php?msg=" . urlencode("Grupo eliminado correctamente"));
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header("Location: ../grupos.php?msg=" . urlencode("Error: " . $e->getMessage()));
}