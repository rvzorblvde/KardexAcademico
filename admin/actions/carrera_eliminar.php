<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../carreras.php");
    exit();
}

$id = (int) $_POST['id_carrera'];

try {
    $check_alumnos = $pdo->prepare("SELECT COUNT(*) FROM Alumno WHERE id_carrera = ?");
    $check_alumnos->execute([$id]);
    $num_alumnos = $check_alumnos->fetchColumn();

    $check_materias = $pdo->prepare("SELECT COUNT(*) FROM Materia WHERE id_carrera = ?");
    $check_materias->execute([$id]);
    $num_materias = $check_materias->fetchColumn();

    if ($num_alumnos > 0 || $num_materias > 0) {
        $msg = "No se puede eliminar: " .
               ($num_alumnos > 0  ? "$num_alumnos alumno(s) "   : "") .
               ($num_alumnos > 0 && $num_materias > 0 ? "y " : "") .
               ($num_materias > 0 ? "$num_materias materia(s) " : "") .
               "siguen asociadas.";
    } else {
        $pdo->prepare("DELETE FROM Carrera WHERE id_carrera = ?")->execute([$id]);
        $msg = "Carrera eliminada correctamente";
    }

    header("Location: ../carreras.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    header("Location: ../carreras.php?msg=" . urlencode("Error: " . $e->getMessage()));
}