<?php 
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/connection.php';

// Modo edición: la PK compuesta viene como 4 parámetros en la URL
$grupo_editar = null;
if (isset($_GET['num_grupo'], $_GET['id_profesor'], $_GET['clave_materia'], $_GET['id_semestre'])) {
    $stmt = $pdo->prepare("
        SELECT * FROM Grupo 
        WHERE num_grupo = ? AND id_profesor = ? 
          AND clave_materia = ? AND id_semestre = ?
    ");
    $stmt->execute([
        (int) $_GET['num_grupo'],
        (int) $_GET['id_profesor'],
        $_GET['clave_materia'],
        $_GET['id_semestre']
    ]);
    $grupo_editar = $stmt->fetch() ?: null;
}

// Listado con JOIN a profesor, materia y semestre + contadores
$grupos = $pdo->query("
    SELECT 
        g.num_grupo, g.id_profesor, g.clave_materia, g.id_semestre,
        g.salon, g.cupo,
        CONCAT(p.Nombres, ' ', p.Apellido1) AS profesor_nombre,
        m.nombre AS materia_nombre,
        s.nombre AS semestre_nombre,
        s.activo AS semestre_activo,
        (SELECT COUNT(*) FROM Inscripcion i 
         WHERE i.num_grupo = g.num_grupo 
           AND i.id_profesor = g.id_profesor
           AND i.clave_materia = g.clave_materia 
           AND i.id_semestre = g.id_semestre
           AND i.Estado = 'Activa') AS inscritos,
        (SELECT COUNT(*) FROM Horario h 
         WHERE h.num_grupo = g.num_grupo 
           AND h.id_profesor = g.id_profesor
           AND h.clave_materia = g.clave_materia 
           AND h.id_semestre = g.id_semestre) AS num_horarios
    FROM Grupo g
    INNER JOIN Profesor p ON g.id_profesor = p.id_profesor
    INNER JOIN Materia  m ON g.clave_materia = m.clave_materia
    INNER JOIN Semestre s ON g.id_semestre = s.id_semestre
    ORDER BY s.activo DESC, g.clave_materia, g.num_grupo
")->fetchAll();

// Catálogos para los selects
$profesores = $pdo->query("
    SELECT id_profesor, Nombres, Apellido1 
    FROM Profesor WHERE activo = TRUE 
    ORDER BY Apellido1
")->fetchAll();

$materias = $pdo->query("
    SELECT clave_materia, nombre 
    FROM Materia 
    ORDER BY clave_materia
")->fetchAll();

$semestres = $pdo->query("
    SELECT id_semestre, nombre, activo 
    FROM Semestre 
    ORDER BY activo DESC, id_semestre DESC
")->fetchAll();

$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Grupos</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Gestor</span>
                <span class="txt-academico">Grupos</span>
            </h1>
        </div>
        <nav><ul>
            <li><a href="../admin.php" class="btn-nav">
                <iconify-icon icon="heroicons:arrow-left-solid"></iconify-icon>
                <span>Volver</span>
            </a></li>
        </ul></nav>
    </header>

    <main class="alumno-contenedor">
        <?php if ($msg): ?>
            <div class="alerta-flash"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- ============ VALIDACIÓN DE PRERREQUISITOS ============ -->
        <?php if (count($profesores) === 0 || count($materias) === 0 || count($semestres) === 0): ?>
            <div class="alerta-flash" style="background: #fca5a5;">
                <strong>Faltan datos para crear grupos:</strong>
                <ul style="margin: 10px 0 0 30px;">
                    <?php if (count($profesores) === 0): ?>
                        <li>No hay profesores activos. <a href="profesores.php">Crear uno →</a></li>
                    <?php endif; ?>
                    <?php if (count($materias) === 0): ?>
                        <li>No hay materias. <a href="materias.php">Crear una →</a></li>
                    <?php endif; ?>
                    <?php if (count($semestres) === 0): ?>
                        <li>No hay semestres. <a href="semestres.php">Crear uno →</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php else: ?>

        <!-- ============ FORMULARIO ============ -->
        <section class="perfil-card">
            <h2><?= $grupo_editar ? 'Editar grupo' : 'Nuevo grupo' ?></h2>

            <form action="actions/grupo_guardar.php" method="POST" class="form-admin">
                <?php if ($grupo_editar): ?>
                    <!-- Si estamos editando, guardamos la PK original para identificar la fila -->
                    <input type="hidden" name="num_grupo_orig"     value="<?= $grupo_editar['num_grupo'] ?>">
                    <input type="hidden" name="id_profesor_orig"   value="<?= $grupo_editar['id_profesor'] ?>">
                    <input type="hidden" name="clave_materia_orig" value="<?= htmlspecialchars($grupo_editar['clave_materia']) ?>">
                    <input type="hidden" name="id_semestre_orig"   value="<?= htmlspecialchars($grupo_editar['id_semestre']) ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="input-grupo">
                        <label>Número de grupo:</label>
                        <input type="number" name="num_grupo" required min="1" max="999"
                               placeholder="Ej. 1, 2, 3..."
                               value="<?= $grupo_editar['num_grupo'] ?? '' ?>">
                    </div>

                    <div class="input-grupo">
                        <label>Semestre:</label>
                        <select name="id_semestre" required>
                            <option value="">— Selecciona un semestre —</option>
                            <?php foreach ($semestres as $s): ?>
                                <option value="<?= htmlspecialchars($s['id_semestre']) ?>"
                                    <?= ($grupo_editar['id_semestre'] ?? '') === $s['id_semestre'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nombre']) ?>
                                    <?= $s['activo'] ? ' (vigente)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-grupo">
                        <label>Materia:</label>
                        <select name="clave_materia" required>
                            <option value="">— Selecciona una materia —</option>
                            <?php foreach ($materias as $m): ?>
                                <option value="<?= htmlspecialchars($m['clave_materia']) ?>"
                                    <?= ($grupo_editar['clave_materia'] ?? '') === $m['clave_materia'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['clave_materia'] . ' — ' . $m['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-grupo">
                        <label>Profesor:</label>
                        <select name="id_profesor" required>
                            <option value="">— Selecciona un profesor —</option>
                            <?php foreach ($profesores as $p): ?>
                                <option value="<?= $p['id_profesor'] ?>"
                                    <?= ($grupo_editar['id_profesor'] ?? null) == $p['id_profesor'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['Nombres'] . ' ' . $p['Apellido1']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-grupo">
                        <label>Salón:</label>
                        <input type="text" name="salon" maxlength="15"
                               placeholder="Ej. L-14, I-04"
                               value="<?= htmlspecialchars($grupo_editar['salon'] ?? '') ?>">
                    </div>

                    <div class="input-grupo">
                        <label>Cupo:</label>
                        <input type="number" name="cupo" required min="1" max="200"
                               value="<?= $grupo_editar['cupo'] ?? 30 ?>">
                    </div>
                </div>

                <div class="form-acciones">
                    <button type="submit" class="btn-login">
                        <?= $grupo_editar ? 'Actualizar' : 'Crear grupo' ?>
                    </button>
                    <?php if ($grupo_editar): ?>
                        <a href="grupos.php" class="btn-cancelar">Cancelar edición</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <?php endif; ?>

        <!-- ============ LISTADO ============ -->
        <section class="tabla-scroll">
            <h2 style="margin-bottom: 15px;">Grupos registrados (<?= count($grupos) ?>)</h2>

            <?php if (count($grupos) === 0): ?>
                <p style="color: #999; padding: 20px; text-align: center;">
                    No hay grupos registrados todavía.
                </p>
            <?php else: ?>
                <table class="tabla-kardex">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Semestre</th>
                            <th>Materia</th>
                            <th>Profesor</th>
                            <th>Salón</th>
                            <th>Inscritos / Cupo</th>
                            <th>Horarios</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($grupos as $g): 
                        $bloquear_borrar = $g['inscritos'] > 0;
                        // Construir query string para identificar este grupo en URLs
                        $params = http_build_query([
                            'num_grupo'     => $g['num_grupo'],
                            'id_profesor'   => $g['id_profesor'],
                            'clave_materia' => $g['clave_materia'],
                            'id_semestre'   => $g['id_semestre']
                        ]);
                    ?>
                        <tr class="<?= $g['semestre_activo'] ? '' : 'fila-inactiva' ?>">
                            <td><strong><?= $g['num_grupo'] ?></strong></td>
                            <td>
                                <?= htmlspecialchars($g['semestre_nombre']) ?>
                                <?php if ($g['semestre_activo']): ?>
                                    <span class="estado-pill activo" style="margin-left:5px;">vigente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($g['clave_materia']) ?></strong><br>
                                <small><?= htmlspecialchars($g['materia_nombre']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($g['profesor_nombre']) ?></td>
                            <td><?= htmlspecialchars($g['salon'] ?: '—') ?></td>
                            <td>
                                <?= $g['inscritos'] ?> / <?= $g['cupo'] ?>
                            </td>
                            <td>
                                <?php if ($g['num_horarios'] > 0): ?>
                                    <span class="estado-pill activo"><?= $g['num_horarios'] ?></span>
                                <?php else: ?>
                                    <span class="estado-pill inactivo">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?<?= $params ?>" class="btn-tabla" title="Editar">
                                    <iconify-icon icon="heroicons:pencil-square-solid"></iconify-icon>
                                </a>
                                <a href="grupo_horarios.php?<?= $params ?>" class="btn-tabla" title="Gestionar horarios" style="background: #1d76db; color: white;">
                                    <iconify-icon icon="heroicons:clock-solid"></iconify-icon>
                                </a>
                                <form action="actions/grupo_eliminar.php" method="POST" style="display:inline" ...>
                                <a href="grupo_inscripciones.php?<?= $params ?>" class="btn-tabla" title="Gestionar inscripciones" style="background: #16a34a; color: white;">
                                    <iconify-icon icon="heroicons:user-plus-solid"></iconify-icon>
                                </a>
                                <a href="grupo_calificaciones.php?<?= $params ?>" class="btn-tabla" title="Capturar calificaciones" style="background: #7c3aed; color: white;">
                                    <iconify-icon icon="heroicons:document-chart-bar-solid"></iconify-icon>
                                </a>
                                <form action="actions/grupo_eliminar.php" method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar el grupo <?= $g['num_grupo'] ?> de <?= htmlspecialchars($g['clave_materia']) ?>? Esta acción es permanente.')">
                                    <input type="hidden" name="num_grupo"     value="<?= $g['num_grupo'] ?>">
                                    <input type="hidden" name="id_profesor"   value="<?= $g['id_profesor'] ?>">
                                    <input type="hidden" name="clave_materia" value="<?= htmlspecialchars($g['clave_materia']) ?>">
                                    <input type="hidden" name="id_semestre"   value="<?= htmlspecialchars($g['id_semestre']) ?>">
                                    <button type="submit" class="btn-tabla btn-rojo" 
                                            title="<?= $bloquear_borrar ? 'No se puede: hay inscripciones' : 'Eliminar' ?>"
                                            <?= $bloquear_borrar ? 'disabled' : '' ?>>
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