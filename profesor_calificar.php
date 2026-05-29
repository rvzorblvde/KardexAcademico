<?php 
require_once __DIR__ . '/includes/auth_profesor.php';
require_once __DIR__ . '/includes/connection.php';

$id_profesor = $_SESSION['user_id'];

// PK del grupo
$campos = ['num_grupo', 'clave_materia', 'id_semestre'];
foreach ($campos as $c) {
    if (!isset($_GET[$c]) || $_GET[$c] === '') {
        header("Location: profesor.php?msg=" . urlencode("Falta información del grupo"));
        exit();
    }
}

$num_grupo     = (int) $_GET['num_grupo'];
$clave_materia = $_GET['clave_materia'];
$id_semestre   = $_GET['id_semestre'];

$stmt = $pdo->prepare("
    SELECT g.*, 
           m.nombre AS materia_nombre,
           m.num_parciales,
           s.nombre AS semestre_nombre,
           s.activo AS semestre_activo
    FROM Grupo g
    INNER JOIN Materia  m ON g.clave_materia = m.clave_materia
    INNER JOIN Semestre s ON g.id_semestre = s.id_semestre
    WHERE g.num_grupo = ? AND g.id_profesor = ? 
      AND g.clave_materia = ? AND g.id_semestre = ?
");
$stmt->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
$grupo = $stmt->fetch();

if (!$grupo) {
    header("Location: profesor.php?msg=" . urlencode("Grupo no encontrado o no te pertenece"));
    exit();
}

$num_parciales = (int) $grupo['num_parciales'];
$solo_lectura = !$grupo['semestre_activo'];

$columnas = [];
for ($i = 1; $i <= $num_parciales; $i++) {
    $columnas[] = ['tipo' => 'Parcial', 'num' => $i, 'label' => "P$i"];
}
$columnas[] = ['tipo' => 'EO', 'num' => null, 'label' => 'EO'];
$columnas[] = ['tipo' => 'EE', 'num' => null, 'label' => 'EE'];
$columnas[] = ['tipo' => 'ET', 'num' => null, 'label' => 'ET'];
$columnas[] = ['tipo' => 'ER', 'num' => null, 'label' => 'ER'];

$stmt = $pdo->prepare("
    SELECT i.id_alumno,
           CONCAT(a.Nombres, ' ', a.Apellido1, ' ', COALESCE(a.Apellido2, '')) AS nombre_completo
    FROM Inscripcion i
    INNER JOIN Alumno a ON i.id_alumno = a.id_alumno
    WHERE i.num_grupo = ? AND i.id_profesor = ? 
      AND i.clave_materia = ? AND i.id_semestre = ?
      AND i.Estado = 'Activa'
    ORDER BY a.Apellido1, a.Apellido2
");
$stmt->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
$alumnos = $stmt->fetchAll();

// Calificaciones existentes
$calificaciones_db = [];
$inasistencias_db = [];

if (count($alumnos) > 0) {
    $stmt = $pdo->prepare("
        SELECT id_alumno, tipo, num_parcial, calificacion, inasistencias
        FROM Calificacion
        WHERE num_grupo = ? AND id_profesor = ? 
          AND clave_materia = ? AND id_semestre = ?
    ");
    $stmt->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
    
    foreach ($stmt->fetchAll() as $row) {
        $key = $row['tipo'] . '_' . ($row['num_parcial'] ?? '');
        $calificaciones_db[$row['id_alumno']][$key] = $row['calificacion'];
        $actual = $inasistencias_db[$row['id_alumno']] ?? 0;
        if ($row['inasistencias'] > $actual) {
            $inasistencias_db[$row['id_alumno']] = $row['inasistencias'];
        }
    }
}

$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capturar calificaciones</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex"><?= $solo_lectura ? 'Consultar' : 'Calificar' ?></span>
                <span class="txt-academico">Grupo</span>
            </h1>
        </div>
        <nav><ul>
            <li><a href="profesor.php" class="btn-nav">
                <iconify-icon icon="heroicons:arrow-left-solid"></iconify-icon>
                <span>Volver</span>
            </a></li>
            <li><a href="logout.php" class="btn-nav">
                <iconify-icon icon="heroicons:arrow-right-on-rectangle-solid"></iconify-icon>
                <span>Salir</span>
            </a></li>
        </ul></nav>
    </header>

    <main class="alumno-contenedor">
        <?php if ($msg): ?>
            <div class="alerta-flash"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($solo_lectura): ?>
            <div class="alerta-flash" style="background: #fde68a;">
                <strong>Semestre archivado:</strong> esta vista es de solo consulta. No puedes modificar calificaciones de semestres pasados.
            </div>
        <?php endif; ?>

        <!-- Info del grupo -->
        <section class="perfil-card">
            <div class="perfil-info">
                <div class="info-grupo">
                    <span class="label">Grupo:</span>
                    <span class="valor"><?= $grupo['num_grupo'] ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Materia:</span>
                    <span class="valor"><?= htmlspecialchars($grupo['clave_materia']) ?> — <?= htmlspecialchars($grupo['materia_nombre']) ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Semestre:</span>
                    <span class="valor"><?= htmlspecialchars($grupo['semestre_nombre']) ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Inscritos:</span>
                    <span class="valor"><?= count($alumnos) ?></span>
                </div>
            </div>
        </section>

        <?php if (count($alumnos) === 0): ?>
            <section class="tabla-scroll">
                <p style="color: #999; padding: 20px; text-align: center;">
                    No hay alumnos inscritos en este grupo.
                </p>
            </section>
        <?php else: ?>
            <form action="actions/profesor_calificaciones_guardar.php" method="POST">
                <input type="hidden" name="num_grupo"     value="<?= $num_grupo ?>">
                <input type="hidden" name="clave_materia" value="<?= htmlspecialchars($clave_materia) ?>">
                <input type="hidden" name="id_semestre"   value="<?= htmlspecialchars($id_semestre) ?>">

                <section class="tabla-scroll">
                    <h2 style="margin-bottom: 15px;">
                        <?= $solo_lectura ? 'Calificaciones registradas' : 'Captura de calificaciones' ?>
                    </h2>
                    <?php if (!$solo_lectura): ?>
                        <p style="margin-bottom: 15px; color: #555; font-size: 0.9rem;">
                            Deja vacía cualquier celda que aún no quieras capturar. 
                            Las calificaciones van de 0 a 10.
                        </p>
                    <?php endif; ?>

                    <table class="tabla-kardex tabla-calificaciones">
                        <thead>
                            <tr>
                                <th rowspan="2">Matrícula</th>
                                <th rowspan="2" style="text-align: left;">Nombre</th>
                                <?php if ($num_parciales > 0): ?>
                                    <th colspan="<?= $num_parciales ?>">Parciales</th>
                                <?php endif; ?>
                                <th colspan="4">Exámenes</th>
                                <th rowspan="2">Inasist.</th>
                            </tr>
                            <tr>
                                <?php foreach ($columnas as $col): ?>
                                    <th><?= $col['label'] ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($alumnos as $a): ?>
                            <tr>
                                <td><strong><?= $a['id_alumno'] ?></strong></td>
                                <td style="text-align: left;"><?= htmlspecialchars($a['nombre_completo']) ?></td>
                                
                                <?php foreach ($columnas as $col): 
                                    $key = $col['tipo'] . '_' . ($col['num'] ?? '');
                                    $valor = $calificaciones_db[$a['id_alumno']][$key] ?? '';
                                    $name = "calif[{$a['id_alumno']}][{$col['tipo']}][" . ($col['num'] ?? '0') . "]";
                                ?>
                                    <td>
                                        <input type="number" 
                                               name="<?= $name ?>" 
                                               value="<?= $valor !== '' ? rtrim(rtrim($valor, '0'), '.') : '' ?>"
                                               min="0" max="10" step="0.01"
                                               class="input-calif"
                                               placeholder="—"
                                               <?= $solo_lectura ? 'readonly' : '' ?>>
                                    </td>
                                <?php endforeach; ?>
                                
                                <td>
                                    <input type="number" 
                                           name="inasist[<?= $a['id_alumno'] ?>]" 
                                           value="<?= $inasistencias_db[$a['id_alumno']] ?? 0 ?>"
                                           min="0" max="99"
                                           class="input-calif input-inasist"
                                           <?= $solo_lectura ? 'readonly' : '' ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <?php if (!$solo_lectura): ?>
                    <div class="form-acciones" style="margin-top: 20px;">
                        <button type="submit" class="btn-login">
                            <iconify-icon icon="heroicons:check-circle-solid"></iconify-icon>
                            Guardar calificaciones
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>