<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../semestres.php");
    exit();
}

$id = trim($_POST['id_semestre']);

if ($id === '') {
    header("Location: ../semestres.php?msg=" . urlencode("ID inválido"));
    exit();
}

try {
    // Validar dependencias antes de eliminar
    $check = $pdo->prepare("SELECT COUNT(*) FROM Grupo WHERE id_semestre = ?");
    $check->execute([$id]);
    $num_grupos = $check->fetchColumn();
    
    if ($num_grupos > 0) {
        $msg = "No se puede eliminar: el semestre tiene $num_grupos grupo(s) asociado(s).";
    } else {
        $pdo->prepare("DELETE FROM Semestre WHERE id_semestre = ?")->execute([$id]);
        $msg = "Semestre eliminado correctamente";
    }
    
    header("Location: ../semestres.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    header("Location: ../semestres.php?msg=" . urlencode("Error: " . $e->getMessage()));
}