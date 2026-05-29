<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../carreras.php");
    exit();
}

$clave_carrera = strtoupper(trim($_POST['clave_carrera']));
$nombre        = trim($_POST['nombre']);
$id_carrera    = $_POST['id_carrera'] ?? null;

// Validación
if ($clave_carrera === '' || strlen($clave_carrera) > 3) {
    header("Location: ../carreras.php?msg=" . urlencode("Clave inválida (máx 3 caracteres)"));
    exit();
}
if ($nombre === '') {
    header("Location: ../carreras.php?msg=" . urlencode("El nombre es obligatorio"));
    exit();
}

try {
    if ($id_carrera) {
        // actualizaar
        $sql = "UPDATE Carrera SET clave_carrera = ?, Nombre = ? WHERE id_carrera = ?";
        $pdo->prepare($sql)->execute([$clave_carrera, $nombre, (int) $id_carrera]);
        $msg = "Carrera actualizada correctamente";
    } else {
        // insertar
        $sql = "INSERT INTO Carrera (clave_carrera, Nombre) VALUES (?, ?)";
        $pdo->prepare($sql)->execute([$clave_carrera, $nombre]);
        $msg = "Carrera creada correctamente";
    }

    header("Location: ../carreras.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    $errorMsg = $e->getCode() === '23000'
        ? "Ya existe una carrera con esa clave"
        : "Error: " . $e->getMessage();
    header("Location: ../carreras.php?msg=" . urlencode($errorMsg));
}