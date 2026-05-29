<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profesores.php");
    exit();
}

$id = (int) $_POST['id_profesor'];

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM Grupo g
        INNER JOIN Semestre s ON g.id_semestre = s.id_semestre
        WHERE g.id_profesor = ? AND s.activo = TRUE
    ");
    $stmt->execute([$id]);
    $grupos_activos = $stmt->fetchColumn();
    
    if ($grupos_activos > 0) {
        $msg = "No se puede dar de baja: tiene $grupos_activos grupo(s) activo(s) asignado(s)";
    } else {
        $pdo->prepare("UPDATE Profesor SET activo = FALSE WHERE id_profesor = ?")->execute([$id]);
        $msg = "Profesor dado de baja correctamente";
    }
    
    header("Location: ../profesores.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    header("Location: ../profesores.php?msg=" . urlencode("Error: " . $e->getMessage()));
}