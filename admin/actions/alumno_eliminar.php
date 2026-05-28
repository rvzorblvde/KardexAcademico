<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

$id = (int) $_POST['id_alumno'];
$pdo->prepare("UPDATE Alumno SET activo = FALSE WHERE id_alumno = ?")->execute([$id]);
header("Location: ../alumnos.php?msg=" . urlencode("Alumno dado de baja"));