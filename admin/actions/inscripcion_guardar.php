<?php
require_once __DIR__ . '/../../includes/auth_admin.php';
require_once __DIR__ . '/../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../grupos.php");
    exit();
}

$id_alumno     = (int) $_POST['id_alumno'];
$num_grupo     = (int) $_POST['num_grupo'];
$id_profesor   = (int) $_POST['id_profesor'];
$clave_materia = trim($_POST['clave_materia']);
$id_semestre   = trim($_POST['id_semestre']);

$params = http_build_query([
    'num_grupo'     => $num_grupo,
    'id_profesor'   => $id_profesor,
    'clave_materia' => $clave_materia,
    'id_semestre'   => $id_semestre
]);
$volver = "../grupo_inscripciones.php?$params";

// Validar cupo disponible
$stmt = $pdo->prepare("
    SELECT g.cupo, 
           (SELECT COUNT(*) FROM Inscripcion 
            WHERE num_grupo = g.num_grupo AND id_profesor = g.id_profesor
              AND clave_materia = g.clave_materia AND id_semestre = g.id_semestre
              AND Estado = 'Activa') AS inscritos_activos
    FROM Grupo g
    WHERE g.num_grupo = ? AND g.id_profesor = ? 
      AND g.clave_materia = ? AND g.id_semestre = ?
");
$stmt->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
$datos = $stmt->fetch();

if (!$datos) {
    header("Location: ../grupos.php?msg=" . urlencode("Grupo no encontrado"));
    exit();
}

if ($datos['inscritos_activos'] >= $datos['cupo']) {
    header("Location: $volver&msg=" . urlencode("El grupo ya alcanzó su cupo máximo"));
    exit();
}

try {
    $sql = "INSERT INTO Inscripcion (id_alumno, num_grupo, id_profesor, clave_materia, id_semestre, Estado) 
            VALUES (?, ?, ?, ?, ?, 'Activa')";
    $pdo->prepare($sql)->execute([
        $id_alumno, $num_grupo, $id_profesor, $clave_materia, $id_semestre
    ]);
    
    header("Location: $volver&msg=" . urlencode("Alumno inscrito correctamente"));
} catch (PDOException $e) {
    $errorMsg = $e->getCode() === '23000'
        ? "Ese alumno ya está inscrito en este grupo"
        : "Error: " . $e->getMessage();
    header("Location: $volver&msg=" . urlencode($errorMsg));
}