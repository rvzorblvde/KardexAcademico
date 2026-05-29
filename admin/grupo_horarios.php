<?php 
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/connection.php';

$campos_requeridos = ['num_grupo', 'id_profesor', 'clave_materia', 'id_semestre'];
foreach ($campos_requeridos as $campo) {
    if (!isset($_GET[$campo]) || $_GET[$campo] === '') {
        header("Location: grupos.php?msg=" . urlencode("Falta información del grupo"));
        exit();
    }
}

$num_grupo     = (int) $_GET['num_grupo'];
$id_profesor   = (int) $_GET['id_profesor'];
$clave_materia = $_GET['clave_materia'];
$id_semestre   = $_GET['id_semestre'];

$stmt = $pdo->prepare("
    SELECT g.*, 
           CONCAT(p.Nombres, ' ', p.Apellido1) AS profesor_nombre,
           m.nombre AS materia_nombre,
           s.nombre AS semestre_nombre
    FROM Grupo g
    INNER JOIN Profesor p ON g.id_profesor = p.id_profesor
    INNER JOIN Materia  m ON g.clave_materia = m.clave_materia
    INNER JOIN Semestre s ON g.id_semestre = s.id_semestre
    WHERE g.num_grupo = ? AND g.id_profesor = ? 
      AND g.clave_materia = ? AND g.id_semestre = ?
");
$stmt->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
$grupo = $stmt->fetch();

if (!$grupo) {
    header("Location: grupos.php?msg=" . urlencode("Grupo no encontrado"));
    exit();
}

// Cargar horarios existentes del grupo
$stmt = $pdo->prepare("
    SELECT id_horario, dia, hora_inicio, hora_fin
    FROM Horario
    WHERE num_grupo = ? AND id_profesor = ? 
      AND clave_materia = ? AND id_semestre = ?
    ORDER BY 
        FIELD(dia, 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'),
        hora_inicio
");
$stmt->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
$horarios = $stmt->fetchAll();

$params_grupo = http_build_query([
    'num_grupo'     => $num_grupo,
    'id_profesor'   => $id_profesor,
    'clave_materia' => $clave_materia,
    'id_semestre'   => $id_semestre
]);

$msg = $_GET['msg'] ?? null;

$dias = ['Lun' => 'Lunes', 'Mar' => 'Martes', 'Mie' => 'Miércoles', 
         'Jue' => 'Jueves', 'Vie' => 'Viernes', 'Sab' => 'Sábado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios del grupo</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="icon" type="image/svg+xml" href="assets/icons/favicon.svg">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Horarios</span>
                <span class="txt-academico">del Grupo</span>
            </h1>
        </div>
        <nav><ul>
            <li><a href="grupos.php" class="btn-nav">
                <iconify-icon icon="heroicons:arrow-left-solid"></iconify-icon>
                <span>Volver a grupos</span>
            </a></li>
        </ul></nav>
    </header>

    <main class="alumno-contenedor">
        <?php if ($msg): ?>
            <div class="alerta-flash"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- info del grupo -->
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
                    <span class="label">Profesor:</span>
                    <span class="valor"><?= htmlspecialchars($grupo['profesor_nombre']) ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Semestre:</span>
                    <span class="valor"><?= htmlspecialchars($grupo['semestre_nombre']) ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Salón:</span>
                    <span class="valor"><?= htmlspecialchars($grupo['salon'] ?: '—') ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Cupo:</span>
                    <span class="valor"><?= $grupo['cupo'] ?></span>
                </div>
            </div>
        </section>

        <!-- agregar horario -->
        <section class="perfil-card">
            <h2>Agregar horario</h2>

            <form action="actions/horario_guardar.php" method="POST" class="form-admin">
                <!-- PK del grupo, viaja oculta -->
                <input type="hidden" name="num_grupo"     value="<?= $num_grupo ?>">
                <input type="hidden" name="id_profesor"   value="<?= $id_profesor ?>">
                <input type="hidden" name="clave_materia" value="<?= htmlspecialchars($clave_materia) ?>">
                <input type="hidden" name="id_semestre"   value="<?= htmlspecialchars($id_semestre) ?>">

                <div class="form-grid">
                    <div class="input-grupo">
                        <label>Día:</label>
                        <select name="dia" required>
                            <option value="">— Selecciona un día —</option>
                            <?php foreach ($dias as $abr => $nombre): ?>
                                <option value="<?= $abr ?>"><?= $nombre ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-grupo">
                        <label>Hora de inicio:</label>
                        <input type="time" name="hora_inicio" required>
                    </div>

                    <div class="input-grupo">
                        <label>Hora de fin:</label>
                        <input type="time" name="hora_fin" required>
                    </div>
                </div>

                <div class="form-acciones">
                    <button type="submit" class="btn-login">Agregar horario</button>
                </div>
            </form>
        </section>

        <!-- horarios -->
        <section class="tabla-scroll">
            <h2 style="margin-bottom: 15px;">Horarios definidos (<?= count($horarios) ?>)</h2>

            <?php if (count($horarios) === 0): ?>
                <p style="color: #999; padding: 20px; text-align: center;">
                    Aún no hay horarios definidos para este grupo.
                </p>
            <?php else: ?>
                <table class="tabla-kardex">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Duración</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($horarios as $h): 
                        $inicio_t = strtotime($h['hora_inicio']);
                        $fin_t = strtotime($h['hora_fin']);
                        $duracion_min = ($fin_t - $inicio_t) / 60;
                        $duracion = floor($duracion_min / 60) . 'h ' . ($duracion_min % 60) . 'min';
                    ?>
                        <tr>
                            <td><strong><?= $dias[$h['dia']] ?? $h['dia'] ?></strong></td>
                            <td><?= substr($h['hora_inicio'], 0, 5) ?></td>
                            <td><?= substr($h['hora_fin'], 0, 5) ?></td>
                            <td><?= $duracion ?></td>
                            <td>
                                <form action="actions/horario_eliminar.php" method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar este horario?')">
                                    <input type="hidden" name="id_horario" value="<?= $h['id_horario'] ?>">
                                    <input type="hidden" name="num_grupo"     value="<?= $num_grupo ?>">
                                    <input type="hidden" name="id_profesor"   value="<?= $id_profesor ?>">
                                    <input type="hidden" name="clave_materia" value="<?= htmlspecialchars($clave_materia) ?>">
                                    <input type="hidden" name="id_semestre"   value="<?= htmlspecialchars($id_semestre) ?>">
                                    <button type="submit" class="btn-tabla btn-rojo" title="Eliminar">
                                        <iconify-icon icon="heroicons:trash-solid"></iconify-icon>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>