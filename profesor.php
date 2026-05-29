<?php 
require_once __DIR__ . '/includes/auth_profesor.php';
require_once __DIR__ . '/includes/connection.php';

$id_profesor = $_SESSION['user_id'];

// Cargar info completa del profesor
$stmt = $pdo->prepare("SELECT * FROM Profesor WHERE id_profesor = ?");
$stmt->execute([$id_profesor]);
$profesor = $stmt->fetch();

// Cargar sus grupos del semestre activo + contadores
$stmt = $pdo->prepare("
    SELECT 
        g.num_grupo, g.id_profesor, g.clave_materia, g.id_semestre,
        g.salon, g.cupo,
        m.nombre AS materia_nombre,
        m.num_parciales,
        s.nombre AS semestre_nombre,
        s.activo AS semestre_activo,
        (SELECT COUNT(*) FROM Inscripcion i 
         WHERE i.num_grupo = g.num_grupo AND i.id_profesor = g.id_profesor
           AND i.clave_materia = g.clave_materia AND i.id_semestre = g.id_semestre
           AND i.Estado = 'Activa') AS inscritos
    FROM Grupo g
    INNER JOIN Materia  m ON g.clave_materia = m.clave_materia
    INNER JOIN Semestre s ON g.id_semestre = s.id_semestre
    WHERE g.id_profesor = ?
    ORDER BY s.activo DESC, g.id_semestre DESC, g.clave_materia
");
$stmt->execute([$id_profesor]);
$grupos = $stmt->fetchAll();

// Separar grupos del semestre activo de los archivados
$grupos_activos = array_filter($grupos, fn($g) => $g['semestre_activo']);
$grupos_archivo = array_filter($grupos, fn($g) => !$g['semestre_activo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Profesor — Kárdex Académico</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Panel</span>
                <span class="txt-academico">Profesor</span>
            </h1>
        </div>
        <nav>
            <ul>
                <li><a href="profesor.php" class="btn-nav">
                    <iconify-icon icon="heroicons:home-solid"></iconify-icon><span>Inicio</span>
                </a></li>
                <li><a href="profesor_estadisticas.php" class="btn-nav">
                    <iconify-icon icon="heroicons:chart-bar-solid"></iconify-icon><span>Estadísticas</span>
                </a></li>
                <li><a href="logout.php" class="btn-nav">
                    <iconify-icon icon="heroicons:arrow-right-on-rectangle-solid"></iconify-icon><span>Salir</span>
                </a></li>
            </ul>
        </nav>
    </header>

    <main class="alumno-contenedor">
        <!-- ============ INFO DEL PROFESOR ============ -->
        <section class="perfil-card">
            <div class="perfil-info">
                <div class="info-grupo">
                    <span class="label">Profesor:</span>
                    <span class="valor"><?= htmlspecialchars("{$profesor['Nombres']} {$profesor['Apellido1']} {$profesor['Apellido2']}") ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">ID:</span>
                    <span class="valor">p<?= $profesor['id_profesor'] ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Grupos vigentes:</span>
                    <span class="valor"><?= count($grupos_activos) ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Historial de grupos:</span>
                    <span class="valor"><?= count($grupos_archivo) ?></span>
                </div>
            </div>
        </section>

        <!-- ============ GRUPOS DEL SEMESTRE ACTIVO ============ -->
        <section class="tabla-scroll">
            <h2 style="margin-bottom: 15px;">Mis grupos vigentes</h2>

            <?php if (count($grupos_activos) === 0): ?>
                <p style="color: #999; padding: 20px; text-align: center;">
                    No tienes grupos asignados en el semestre vigente.
                </p>
            <?php else: ?>
                <table class="tabla-kardex">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Materia</th>
                            <th>Semestre</th>
                            <th>Salón</th>
                            <th>Inscritos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($grupos_activos as $g): 
                        $params = http_build_query([
                            'num_grupo'     => $g['num_grupo'],
                            'clave_materia' => $g['clave_materia'],
                            'id_semestre'   => $g['id_semestre']
                        ]);
                    ?>
                        <tr>
                            <td><strong><?= $g['num_grupo'] ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($g['clave_materia']) ?></strong><br>
                                <small><?= htmlspecialchars($g['materia_nombre']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($g['semestre_nombre']) ?></td>
                            <td><?= htmlspecialchars($g['salon'] ?: '—') ?></td>
                            <td><?= $g['inscritos'] ?> / <?= $g['cupo'] ?></td>
                            <td>
                                <a href="profesor_calificar.php?<?= $params ?>" 
                                   class="btn-tabla" 
                                   title="Capturar calificaciones"
                                   style="background: #7c3aed; color: white;">
                                    <iconify-icon icon="heroicons:document-chart-bar-solid"></iconify-icon>
                                    Calificar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- ============ GRUPOS ARCHIVADOS (HISTORIAL) ============ -->
        <?php if (count($grupos_archivo) > 0): ?>
        <section class="tabla-scroll">
            <h2 style="margin-bottom: 15px;">Historial de grupos</h2>
            <table class="tabla-kardex">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Materia</th>
                        <th>Semestre</th>
                        <th>Inscritos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($grupos_archivo as $g): 
                    $params = http_build_query([
                        'num_grupo'     => $g['num_grupo'],
                        'clave_materia' => $g['clave_materia'],
                        'id_semestre'   => $g['id_semestre']
                    ]);
                ?>
                    <tr class="fila-inactiva">
                        <td><strong><?= $g['num_grupo'] ?></strong></td>
                        <td>
                            <?= htmlspecialchars($g['clave_materia']) ?> — 
                            <?= htmlspecialchars($g['materia_nombre']) ?>
                        </td>
                        <td><?= htmlspecialchars($g['semestre_nombre']) ?></td>
                        <td><?= $g['inscritos'] ?></td>
                        <td>
                            <a href="profesor_calificar.php?<?= $params ?>" 
                               class="btn-tabla" 
                               title="Ver calificaciones (solo lectura)">
                                <iconify-icon icon="heroicons:eye-solid"></iconify-icon>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>
    </main>
</body>
</html>