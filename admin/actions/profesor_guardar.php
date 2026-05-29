<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profesores.php");
    exit();
}

$id_profesor      = (int) $_POST['id_profesor'];
$nombres          = trim($_POST['nombres']);
$apellido1        = trim($_POST['apellido1']);
$apellido2        = trim($_POST['apellido2']) ?: null;
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$password         = $_POST['password'] ?? '';
$id_original      = $_POST['id_profesor_original'] ?? null;

// Validación de servidor
if ($id_profesor < 10000 || $id_profesor > 99999) {
    header("Location: ../profesores.php?msg=" . urlencode("ID inválido: debe tener 5 dígitos"));
    exit();
}
if ($nombres === '' || $apellido1 === '') {
    header("Location: ../profesores.php?msg=" . urlencode("Nombre y apellido paterno son obligatorios"));
    exit();
}
if (!$id_original && strlen($password) < 6) {
    header("Location: ../profesores.php?msg=" . urlencode("La contraseña debe tener al menos 6 caracteres"));
    exit();
}

try {
    if ($id_original) {
        // actualizar
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE Profesor 
                    SET id_profesor = ?, Nombres = ?, Apellido1 = ?, Apellido2 = ?, 
                        fecha_nacimiento = ?, password_hash = ? 
                    WHERE id_profesor = ?";
            $params = [$id_profesor, $nombres, $apellido1, $apellido2,
                       $fecha_nacimiento, $hash, $id_original];
        } else {
            $sql = "UPDATE Profesor 
                    SET id_profesor = ?, Nombres = ?, Apellido1 = ?, Apellido2 = ?, fecha_nacimiento = ? 
                    WHERE id_profesor = ?";
            $params = [$id_profesor, $nombres, $apellido1, $apellido2,
                       $fecha_nacimiento, $id_original];
        }
        $pdo->prepare($sql)->execute($params);
        $msg = "Profesor actualizado correctamente";
    } else {
        // insertar
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO Profesor (id_profesor, Nombres, Apellido1, Apellido2, 
                                      fecha_nacimiento, password_hash) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $id_profesor, $nombres, $apellido1, $apellido2, $fecha_nacimiento, $hash
        ]);
        $msg = "Profesor creado correctamente";
    }

    header("Location: ../profesores.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    $errorMsg = $e->getCode() === '23000' 
        ? "Ya existe un profesor con ese ID"
        : "Error: " . $e->getMessage();
    header("Location: ../profesores.php?msg=" . urlencode($errorMsg));
}