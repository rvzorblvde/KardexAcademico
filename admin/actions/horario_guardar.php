<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../grupos.php");
    exit();
}

// PK del grupo
$num_grupo     = (int) $_POST['num_grupo'];
$id_profesor   = (int) $_POST['id_profesor'];
$clave_materia = trim($_POST['clave_materia']);
$id_semestre   = trim($_POST['id_semestre']);

// Datos del horario
$dia         = trim($_POST['dia']);
$hora_inicio = $_POST['hora_inicio'];
$hora_fin    = $_POST['hora_fin'];

// Querystring para redirigir de vuelta al mismo grupo
$params_grupo = http_build_query([
    'num_grupo'     => $num_grupo,
    'id_profesor'   => $id_profesor,
    'clave_materia' => $clave_materia,
    'id_semestre'   => $id_semestre
]);
$volver = "../grupo_horarios.php?$params_grupo";

// Validar día permitido
$dias_validos = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];
if (!in_array($dia, $dias_validos)) {
    header("Location: $volver&msg=" . urlencode("Día inválido"));
    exit();
}

// Validar que hora_fin > hora_inicio
if ($hora_fin <= $hora_inicio) {
    header("Location: $volver&msg=" . urlencode("La hora de fin debe ser después de la hora de inicio"));
    exit();
}

try {
    // Validar que no haya traslape con otro horario del mismo grupo en el mismo día
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM Horario
        WHERE num_grupo = ? AND id_profesor = ? 
          AND clave_materia = ? AND id_semestre = ?
          AND dia = ?
          AND (
              (hora_inicio < ? AND hora_fin > ?) OR
              (hora_inicio < ? AND hora_fin > ?) OR
              (hora_inicio >= ? AND hora_fin <= ?)
          )
    ");
    $check->execute([
        $num_grupo, $id_profesor, $clave_materia, $id_semestre, $dia,
        $hora_fin, $hora_inicio,        // El nuevo termina dentro de uno existente
        $hora_inicio, $hora_inicio,     // El nuevo empieza durante uno existente
        $hora_inicio, $hora_fin         // El nuevo contiene a uno existente
    ]);
    
    if ($check->fetchColumn() > 0) {
        header("Location: $volver&msg=" . 
            urlencode("Ese horario se traslapa con otro ya definido para el mismo día"));
        exit();
    }

    // Insertar
    $sql = "INSERT INTO Horario (num_grupo, id_profesor, clave_materia, id_semestre, 
                                 dia, hora_inicio, hora_fin)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([
        $num_grupo, $id_profesor, $clave_materia, $id_semestre,
        $dia, $hora_inicio, $hora_fin
    ]);

    header("Location: $volver&msg=" . urlencode("Horario agregado correctamente"));
} catch (PDOException $e) {
    header("Location: $volver&msg=" . urlencode("Error: " . $e->getMessage()));
}