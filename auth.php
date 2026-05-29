<?php
require_once __DIR__ . '/includes/connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInput = trim($_POST['usuario']); 
    $password = trim($_POST['contrasena']); 

    $tabla = ""; $columnaId = ""; $idLimpio = ""; $redirect = "";

    // Identificación de tipo
    if (str_starts_with($usuarioInput, 'a')) {
        $tabla = "Alumno"; 
        $columnaId = "id_alumno"; 
        $idLimpio = substr($usuarioInput, 1); 
        $redirect = "alumno.php";
    } elseif (str_starts_with($usuarioInput, 'p')) {
        $tabla = "Profesor"; 
        $columnaId = "id_profesor"; 
        $idLimpio = substr($usuarioInput, 1); 
        $redirect = "profesor.php";
    } elseif (str_starts_with($usuarioInput, 'sysadmin')) {
        $tabla = "Administrador"; 
        $columnaId = "id_admin"; 
        $idLimpio = $usuarioInput;
        $redirect = "admin.php";
    } else {
        header("Location: login.php?error=formato");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM $tabla WHERE $columnaId = ?");
        $stmt->execute([$idLimpio]);
        $user = $stmt->fetch();

        // Verificación
        if ($user && password_verify($password, $user['password_hash'])) {
            // Guardar datos en la sesión
            $_SESSION['user_id'] = $idLimpio;
            $_SESSION['rol'] = $tabla;
            $_SESSION['nombre'] = $user['Nombres'];

            // Redirigir
            header("Location: $redirect");
            exit;
        } else {
            // Error de lgin
            header("Location: login.php?error=1");
            exit;
        }

    } catch (PDOException $e) {
        // En caso de error de BD no mostrar detalles
        header("Location: login.php?error=db");
        exit;
    }
}