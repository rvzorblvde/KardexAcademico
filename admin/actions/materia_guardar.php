<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../materias.php");
    exit();
}

$clave_materia  = trim($_POST['clave_materia']);
$nombre         = trim($_POST['nombre']);
$id_carrera     = (int) $_POST['id_carrera'];
$creditos       = (int) $_POST['creditos'];
$num_parciales  = (int) $_POST['num_parciales'];
$clave_original = $_POST['clave_original'] ?? null;

// Validaciones de servidor
if ($clave_materia === '' || strlen($clave_materia) > 10) {
    header("Location: ../materias.php?msg=" . urlencode("Clave inválida (máx 10 caracteres)"));
    exit();
}
if ($nombre === '') {
    header("Location: ../materias.php?msg=" . urlencode("El nombre es obligatorio"));
    exit();
}
if ($id_carrera < 1) {
    header("Location: ../materias.php?msg=" . urlencode("Selecciona una carrera"));
    exit();
}
if ($num_parciales < 3 || $num_parciales > 4) {
    header("Location: ../materias.php?msg=" . urlencode("Los parciales deben ser 3 o 4"));
    exit();
}
if ($creditos < 1) {
    header("Location: ../materias.php?msg=" . urlencode("Los créditos deben ser mayor a 0"));
    exit();
}

try {
    if ($clave_original) {
        // ===== ACTUALIZAR =====
        // Si cambia la clave y hay grupos asociados, bloqueamos
        if ($clave_original !== $clave_materia) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM Grupo WHERE clave_materia = ?");
            $check->execute([$clave_original]);
            if ($check->fetchColumn() > 0) {
                header("Location: ../materias.php?msg=" . 
                    urlencode("No se puede cambiar la clave: hay grupos asociados a la clave anterior"));
                exit();
            }
        }
        
        $sql = "UPDATE Materia 
                SET clave_materia = ?, nombre = ?, id_carrera = ?, creditos = ?, num_parciales = ? 
                WHERE clave_materia = ?";
        $pdo->prepare($sql)->execute([
            $clave_materia, $nombre, $id_carrera, $creditos, $num_parciales, $clave_original
        ]);
        $msg = "Materia actualizada correctamente";
    } else {
        // ===== INSERTAR =====
        $sql = "INSERT INTO Materia (clave_materia, nombre, id_carrera, creditos, num_parciales) 
                VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $clave_materia, $nombre, $id_carrera, $creditos, $num_parciales
        ]);
        $msg = "Materia creada correctamente";
    }

    header("Location: ../materias.php?msg=" . urlencode($msg));
} catch (PDOException $e) {
    $errorMsg = $e->getCode() === '23000'
        ? "Ya existe una materia con esa clave"
        : "Error: " . $e->getMessage();
    header("Location: ../materias.php?msg=" . urlencode($errorMsg));
}