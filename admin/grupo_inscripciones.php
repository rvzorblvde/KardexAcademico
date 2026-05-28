<?php 
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/connection.php';

// Validar PK del grupo
$campos = ['num_grupo', 'id_profesor', 'clave_materia', 'id_semestre'];
foreach ($campos as $c) {
    if (!isset($_GET[$c]) || $_GET[$c] === '') {
        header("Location: grupos.php?msg=" . urlencode("Falta información del grupo"));
        exit();
    }
}

$num_grupo     = (int) $_GET['num_grupo'];
$id_profesor   = (int) $_GET['id_profesor'];
$clave_materia = $_GET['clave_materia'];
$id_semestre   = $_GET['id_semestre'];

// Info del grupo
$stmt = $pdo->prepare("
    SELECT g.*, 
           CONCAT(p.Nombres, ' ', p.Apellido1) AS profesor_nombre,
           m.nombre AS materia_nombre,
           m.id_carrera AS materia_carrera,
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

// Inscritos actuales
$stmt = $pdo->prepare("
    SELECT i.id_alumno, i.Estado,
           CONCAT(a.Nombres, ' ', a.Apellido1, ' ', COALESCE(a.Apellido2, '')) AS nombre_completo
    FROM Inscripcion i
    INNER JOIN Alumno a ON i.id_alumno = a.id_alumno
    WHERE i.num_grupo = ? AND i.id_profesor = ? 
      AND i.clave_materia = ? AND i.id_semestre = ?
    ORDER BY a.Apellido1, a.Apellido2
");
$stmt->execute([$num_grupo, $id_profesor, $clave_materia, $id_semestre]);
$inscritos = $stmt->fetchAll();

// Alumnos disponibles para inscribir (activos, de la misma carrera de la materia, no inscritos ya en este grupo)
$stmt = $pdo->prepare("
    SELECT a.id_alumno, 
           CONCAT(a.Nombres, ' ', a.Apellido1, ' ', COALESCE(a.Apellido2, '')) AS nombre_completo
    FROM Alumno a
    WHERE a.activo = TRUE
      AND a.id_carrera = ?
      AND a.id_alumno NOT IN (
          SELECT id_alumno FROM Inscripcion 
          WHERE num_grupo = ? AND id_profesor = ? 
            AND clave_materia = ? AND id_semestre = ?
      )
    ORDER BY a.Apellido1, a.Apellido2
");
$stmt->execute([
    $grupo['materia_carrera'],
    $num_grupo, $id_profesor, $clave_materia, $id_semestre
]);
$disponibles = $stmt->fetchAll();

$cupo_disponible = $grupo['cupo'] - count(array_filter($inscritos, fn($i) => $i['Estado'] === 'Activa'));
$msg = $_GET['msg'] ?? null;

$params_grupo = http_build_query([
    'num_grupo'     => $num_grupo,
    'id_profesor'   => $id_profesor,
    'clave_materia' => $clave_materia,
    'id_semestre'   => $id_semestre
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripciones del grupo</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Inscripciones</span>
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

        <!-- ============ INFO DEL GRUPO ============ -->
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
                    <span class="label">Cupo:</span>
                    <span class="valor"><?= count(array_filter($inscritos, fn($i) => $i['Estado'] === 'Activa')) ?> / <?= $grupo['cupo'] ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Disponible:</span>
                    <span class="valor"><?= $cupo_disponible ?> lugar(es)</span>
                </div>
            </div>
        </section>

        <!-- ============ INSCRIBIR NUEVO ALUMNO ============ -->
        <section class="perfil-card">
            <h2>Inscribir alumno</h2>

            <?php if (count($disponibles) === 0): ?>
                <p style="color: #999; padding: 10px;">
                    No hay alumnos disponibles para inscribir (los activos de esta carrera ya están inscritos).
                </p>
            <?php elseif ($cupo_disponible <= 0): ?>
                <p style="color: #b91c1c; padding: 10px; font-weight: 700;">
                    El grupo ha alcanzado su cupo máximo. Da de baja a algún alumno o aumenta el cupo desde el módulo de grupos.
                </p>
            <?php else: ?>
                <form action="actions/inscripcion_guardar.php" method="POST" class="form-admin">
                    <input type="hidden" name="num_grupo"     value="<?= $num_grupo ?>">
                    <input type="hidden" name="id_profesor"   value="<?= $id_profesor ?>">
                    <input type="hidden" name="clave_materia" value="<?= htmlspecialchars($clave_materia) ?>">
                    <input type="hidden" name="id_semestre"   value="<?= htmlspecialchars($id_semestre) ?>">

                    <div class="form-grid" style="grid-template-columns: 2fr 1fr;">
                        <div class="input-grupo">
                            <label>Alumno:</label>
                            <select name="id_alumno" required>
                                <option value="">— Selecciona un alumno —</option>
                                <?php foreach ($disponibles as $a): ?>
                                    <option value="<?= $a['id_alumno'] ?>">
                                        <?= $a['id_alumno'] ?> — <?= htmlspecialchars($a['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-acciones">
                        <button type="submit" class="btn-login">Inscribir</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <!-- ============ LISTADO DE INSCRITOS ============ -->
        <section class="tabla-scroll">
            <h2 style="margin-bottom: 15px;">Inscritos (<?= count($inscritos) ?>)</h2>

            <?php if (count($inscritos) === 0): ?>
                <p style="color: #999; padding: 20px; text-align: center;">
                    Aún no hay alumnos inscritos en este grupo.
                </p>
            <?php else: ?>
                <table class="tabla-kardex">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Nombre completo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($inscritos as $i): ?>
                        <tr class="<?= $i['Estado'] === 'Activa' ? '' : 'fila-inactiva' ?>">
                            <td><strong><?= $i['id_alumno'] ?></strong></td>
                            <td><?= htmlspecialchars($i['nombre_completo']) ?></td>
                            <td>
                                <span class="estado-pill <?= $i['Estado'] === 'Activa' ? 'activo' : 'inactivo' ?>">
                                    <?= $i['Estado'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($i['Estado'] === 'Activa'): ?>
                                    <form action="actions/inscripcion_baja.php" method="POST" style="display:inline"
                                          onsubmit="return confirm('¿Dar de baja al alumno <?= $i['id_alumno'] ?>?')">
                                        <input type="hidden" name="id_alumno"     value="<?= $i['id_alumno'] ?>">
                                        <input type="hidden" name="num_grupo"     value="<?= $num_grupo ?>">
                                        <input type="hidden" name="id_profesor"   value="<?= $id_profesor ?>">
                                        <input type="hidden" name="clave_materia" value="<?= htmlspecialchars($clave_materia) ?>">
                                        <input type="hidden" name="id_semestre"   value="<?= htmlspecialchars($id_semestre) ?>">
                                        <button type="submit" class="btn-tabla btn-rojo" title="Dar de baja">
                                            <iconify-icon icon="heroicons:user-minus-solid"></iconify-icon>
                                        </button>
                                    </form>
                                <?php endif; ?>
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