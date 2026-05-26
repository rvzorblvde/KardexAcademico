<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../alumnos.php");
    exit();
}

// Recibir y limpiar
$id_alumno        = (int) $_POST['id_alumno'];
$nombres          = trim($_POST['nombres']);
$apellido1        = trim($_POST['apellido1']);
$apellido2        = trim($_POST['apellido2']) ?: null;
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$id_carrera       = (int) $_POST['id_carrera'];
$password         = $_POST['password'] ?? '';
$id_original      = $_POST['id_alumno_original'] ?? null;

try {
    if ($id_original) {
        // ===== ACTUALIZAR =====
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE Alumno SET id_alumno=?, Nombres=?, Apellido1=?, Apellido2=?, 
                    fecha_nacimiento=?, id_carrera=?, password_hash=? WHERE id_alumno=?";
            $params = [$id_alumno, $nombres, $apellido1, $apellido2, 
                       $fecha_nacimiento, $id_carrera, $hash, $id_original];
        } else {
            $sql = "UPDATE Alumno SET id_alumno=?, Nombres=?, Apellido1=?, Apellido2=?, 
                    fecha_nacimiento=?, id_carrera=? WHERE id_alumno=?";
            $params = [$id_alumno, $nombres, $apellido1, $apellido2, 
                       $fecha_nacimiento, $id_carrera, $id_original];
        }
        $pdo->prepare($sql)->execute($params);
        $msg = "Alumno actualizado correctamente";
    } else {
        // ===== INSERTAR =====
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO Alumno (id_alumno, Nombres, Apellido1, Apellido2, 
                fecha_nacimiento, id_carrera, password_hash) VALUES (?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([$id_alumno, $nombres, $apellido1, $apellido2,
                                       $fecha_nacimiento, $id_carrera, $hash]);
        $msg = "Alumno creado correctamente";
    }

    header("Location: ../alumnos.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    header("Location: ../alumnos.php?msg=" . urlencode("Error: " . $e->getMessage()));
}