<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../materias.php");
    exit();
}

$clave = trim($_POST['clave_materia']);

if ($clave === '') {
    header("Location: ../materias.php?msg=" . urlencode("Clave inválida"));
    exit();
}

try {
    // Validar dependencias antes de eliminar
    $check = $pdo->prepare("SELECT COUNT(*) FROM Grupo WHERE clave_materia = ?");
    $check->execute([$clave]);
    $num_grupos = $check->fetchColumn();
    
    if ($num_grupos > 0) {
        $msg = "No se puede eliminar: la materia tiene $num_grupos grupo(s) asociado(s). " .
               "Elimina primero los grupos.";
    } else {
        $pdo->prepare("DELETE FROM Materia WHERE clave_materia = ?")->execute([$clave]);
        $msg = "Materia eliminada correctamente";
    }
    
    header("Location: ../materias.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    header("Location: ../materias.php?msg=" . urlencode("Error: " . $e->getMessage()));
}