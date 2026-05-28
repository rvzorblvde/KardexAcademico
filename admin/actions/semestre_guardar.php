<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../semestres.php");
    exit();
}

$id_semestre = trim($_POST['id_semestre']);
$nombre      = trim($_POST['nombre']);
$id_original = $_POST['id_original'] ?? null;

// Validaciones
if ($id_semestre === '' || strlen($id_semestre) > 10) {
    header("Location: ../semestres.php?msg=" . urlencode("ID inválido (máx 10 caracteres)"));
    exit();
}
if ($nombre === '' || strlen($nombre) > 30) {
    header("Location: ../semestres.php?msg=" . urlencode("Nombre inválido (máx 30 caracteres)"));
    exit();
}

try {
    if ($id_original) {
        // ===== ACTUALIZAR =====
        // Si cambia el ID y hay grupos asociados, bloqueamos
        if ($id_original !== $id_semestre) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM Grupo WHERE id_semestre = ?");
            $check->execute([$id_original]);
            if ($check->fetchColumn() > 0) {
                header("Location: ../semestres.php?msg=" . 
                    urlencode("No se puede cambiar el ID: hay grupos asociados"));
                exit();
            }
        }
        
        $sql = "UPDATE Semestre SET id_semestre = ?, nombre = ? WHERE id_semestre = ?";
        $pdo->prepare($sql)->execute([$id_semestre, $nombre, $id_original]);
        $msg = "Semestre actualizado correctamente";
    } else {
        // ===== INSERTAR =====
        // Los semestres nuevos se crean siempre inactivos
        $sql = "INSERT INTO Semestre (id_semestre, nombre, activo) VALUES (?, ?, FALSE)";
        $pdo->prepare($sql)->execute([$id_semestre, $nombre]);
        $msg = "Semestre creado. Recuerda activarlo si es el vigente.";
    }

    header("Location: ../semestres.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    $errorMsg = $e->getCode() === '23000'
        ? "Ya existe un semestre con ese ID o nombre"
        : "Error: " . $e->getMessage();
    header("Location: ../semestres.php?msg=" . urlencode($errorMsg));
}