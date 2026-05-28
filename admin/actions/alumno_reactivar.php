<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../alumnos.php");
    exit();
}

$id = (int) $_POST['id_alumno'];
$pdo->prepare("UPDATE Alumno SET activo = TRUE WHERE id_alumno = ?")->execute([$id]);
header("Location: ../alumnos.php?msg=" . urlencode("Alumno reactivado"));