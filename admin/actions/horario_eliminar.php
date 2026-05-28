<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../grupos.php");
    exit();
}

$id_horario    = (int) $_POST['id_horario'];
$num_grupo     = (int) $_POST['num_grupo'];
$id_profesor   = (int) $_POST['id_profesor'];
$clave_materia = trim($_POST['clave_materia']);
$id_semestre   = trim($_POST['id_semestre']);

$params_grupo = http_build_query([
    'num_grupo'     => $num_grupo,
    'id_profesor'   => $id_profesor,
    'clave_materia' => $clave_materia,
    'id_semestre'   => $id_semestre
]);

try {
    $pdo->prepare("DELETE FROM Horario WHERE id_horario = ?")->execute([$id_horario]);
    header("Location: ../grupo_horarios.php?$params_grupo&msg=" . 
           urlencode("Horario eliminado"));
} catch (PDOException $e) {
    header("Location: ../grupo_horarios.php?$params_grupo&msg=" . 
           urlencode("Error: " . $e->getMessage()));
}