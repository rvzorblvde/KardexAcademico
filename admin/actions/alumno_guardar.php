<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';
require_once __DIR__ . '/../../includes/upload_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../alumnos.php");
    exit();
}

// ============ RECIBIR Y LIMPIAR ============
$id_alumno        = (int) $_POST['id_alumno'];
$nombres          = trim($_POST['nombres']);
$apellido1        = trim($_POST['apellido1']);
$apellido2        = trim($_POST['apellido2']) ?: null;
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$id_carrera       = (int) $_POST['id_carrera'];
$password         = $_POST['password'] ?? '';
$id_original      = $_POST['id_alumno_original'] ?? null;

// ============ PROCESAR FOTO (común a INSERT y UPDATE) ============
$nombre_foto = null;
try {
    $nombre_foto = UploadHelper::subirFoto(
        $_FILES['foto'] ?? null,
        'alumnos',
        $id_alumno
    );
} catch (Exception $e) {
    header("Location: ../alumnos.php?msg=" . urlencode("Foto: " . $e->getMessage()));
    exit();
}

try {
    if ($id_original) {
        // ===== ACTUALIZAR =====
        // Construir el SQL dinámicamente según si hay password nuevo y/o foto nueva
        $campos = [
            'id_alumno = ?',
            'Nombres = ?',
            'Apellido1 = ?',
            'Apellido2 = ?',
            'fecha_nacimiento = ?',
            'id_carrera = ?'
        ];
        $params = [
            $id_alumno, $nombres, $apellido1, $apellido2,
            $fecha_nacimiento, $id_carrera
        ];

        if ($password !== '') {
            $campos[] = 'password_hash = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($nombre_foto !== null) {
            $campos[] = 'foto = ?';
            $params[] = $nombre_foto;
        }

        $params[] = $id_original;
        $sql = "UPDATE Alumno SET " . implode(', ', $campos) . " WHERE id_alumno = ?";
        $pdo->prepare($sql)->execute($params);

        $msg = "Alumno actualizado correctamente";
    } else {
        // ===== INSERTAR =====
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO Alumno (id_alumno, Nombres, Apellido1, Apellido2, 
                                    fecha_nacimiento, id_carrera, password_hash, foto) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $id_alumno, $nombres, $apellido1, $apellido2,
            $fecha_nacimiento, $id_carrera, $hash, $nombre_foto
        ]);

        $msg = "Alumno creado correctamente";
    }

    header("Location: ../alumnos.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    header("Location: ../alumnos.php?msg=" . urlencode("Error: " . $e->getMessage()));
}