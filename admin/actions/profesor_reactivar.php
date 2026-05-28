<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profesores.php");
    exit();
}

$id = (int) $_POST['id_profesor'];
$pdo->prepare("UPDATE Profesor SET activo = TRUE WHERE id_profesor = ?")->execute([$id]);
header("Location: ../profesores.php?msg=" . urlencode("Profesor reactivado"));