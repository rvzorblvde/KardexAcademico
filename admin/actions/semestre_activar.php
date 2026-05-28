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
    // Verificar que el semestre existe
    $check = $pdo->prepare("SELECT COUNT(*) FROM Semestre WHERE id_semestre = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
        header("Location: ../semestres.php?msg=" . urlencode("Semestre no encontrado"));
        exit();
    }

    // Transacción: o se hacen las dos cosas, o ninguna
    $pdo->beginTransaction();
    
    // 1. Desactivar todos los semestres
    $pdo->exec("UPDATE Semestre SET activo = FALSE");
    
    // 2. Activar solo el elegido
    $stmt = $pdo->prepare("UPDATE Semestre SET activo = TRUE WHERE id_semestre = ?");
    $stmt->execute([$id]);
    
    $pdo->commit();
    
    header("Location: ../semestres.php?msg=" . urlencode("Semestre $id marcado como vigente"));
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: ../semestres.php?msg=" . urlencode("Error: " . $e->getMessage()));
}