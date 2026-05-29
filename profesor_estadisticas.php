<?php 
require_once __DIR__ . '/includes/auth_profesor.php';
require_once __DIR__ . '/includes/connection.php';

$id_profesor = $_SESSION['user_id'];

// Cargar nombre del profesor para mostrarlo
$stmt = $pdo->prepare("SELECT Nombres, Apellido1 FROM Profesor WHERE id_profesor = ?");
$stmt->execute([$id_profesor]);
$profesor = $stmt->fetch();

// Reprobados por grupo
$sql_grupos = "
    SELECT 
        g.num_grupo, g.clave_materia, g.id_semestre,
        CONCAT(g.clave_materia, '-', g.num_grupo, ' (', s.nombre, ')') AS etiqueta,
        s.activo AS semestre_activo,
        
        -- Total de inscripciones activas
        (SELECT COUNT(*) FROM Inscripcion i 
         WHERE i.num_grupo = g.num_grupo AND i.id_profesor = g.id_profesor
           AND i.clave_materia = g.clave_materia AND i.id_semestre = g.id_semestre
           AND i.Estado IN ('Activa', 'Aprobada', 'Reprobada')) AS total_inscritos,
        
        -- Alumnos con al menos un examen registrado y mejor calif >= 6
        (SELECT COUNT(DISTINCT i.id_alumno) 
         FROM Inscripcion i
         WHERE i.num_grupo = g.num_grupo AND i.id_profesor = g.id_profesor
           AND i.clave_materia = g.clave_materia AND i.id_semestre = g.id_semestre
           AND i.Estado IN ('Activa', 'Aprobada', 'Reprobada')
           AND (SELECT MAX(c.calificacion) FROM Calificacion c
                WHERE c.id_alumno = i.id_alumno 
                  AND c.num_grupo = i.num_grupo AND c.id_profesor = i.id_profesor
                  AND c.clave_materia = i.clave_materia AND c.id_semestre = i.id_semestre
                  AND c.tipo IN ('EO','EE','ET','ER')) >= 6) AS aprobados,
        
        -- Alumnos con al menos un examen, mejor calif < 6
        (SELECT COUNT(DISTINCT i.id_alumno) 
         FROM Inscripcion i
         WHERE i.num_grupo = g.num_grupo AND i.id_profesor = g.id_profesor
           AND i.clave_materia = g.clave_materia AND i.id_semestre = g.id_semestre
           AND i.Estado IN ('Activa', 'Aprobada', 'Reprobada')
           AND (SELECT MAX(c.calificacion) FROM Calificacion c
                WHERE c.id_alumno = i.id_alumno 
                  AND c.num_grupo = i.num_grupo AND c.id_profesor = i.id_profesor
                  AND c.clave_materia = i.clave_materia AND c.id_semestre = i.id_semestre
                  AND c.tipo IN ('EO','EE','ET','ER')) < 6) AS reprobados
                
    FROM Grupo g
    INNER JOIN Semestre s ON g.id_semestre = s.id_semestre
    WHERE g.id_profesor = ?
    ORDER BY s.activo DESC, g.id_semestre DESC, g.clave_materia
";
$stmt = $pdo->prepare($sql_grupos);
$stmt->execute([$id_profesor]);
$grupos_stats = $stmt->fetchAll();

foreach ($grupos_stats as &$g) {
    $g['sin_calificar'] = $g['total_inscritos'] - $g['aprobados'] - $g['reprobados'];
}
unset($g);

// Reprobados por materia
$sql_materias = "
    SELECT 
        g.clave_materia,
        m.nombre AS materia_nombre,
        
        (SELECT COUNT(DISTINCT CONCAT(i.id_alumno, '-', i.num_grupo, '-', i.id_semestre))
         FROM Inscripcion i
         INNER JOIN Grupo g2 ON i.num_grupo = g2.num_grupo 
           AND i.id_profesor = g2.id_profesor
           AND i.clave_materia = g2.clave_materia 
           AND i.id_semestre = g2.id_semestre
         WHERE g2.clave_materia = g.clave_materia
           AND g2.id_profesor = ?
           AND i.Estado IN ('Activa', 'Aprobada', 'Reprobada')
           AND (SELECT MAX(c.calificacion) FROM Calificacion c
                WHERE c.id_alumno = i.id_alumno 
                  AND c.num_grupo = i.num_grupo AND c.id_profesor = i.id_profesor
                  AND c.clave_materia = i.clave_materia AND c.id_semestre = i.id_semestre
                  AND c.tipo IN ('EO','EE','ET','ER')) >= 6) AS aprobados,
        
        (SELECT COUNT(DISTINCT CONCAT(i.id_alumno, '-', i.num_grupo, '-', i.id_semestre))
         FROM Inscripcion i
         INNER JOIN Grupo g2 ON i.num_grupo = g2.num_grupo 
           AND i.id_profesor = g2.id_profesor
           AND i.clave_materia = g2.clave_materia 
           AND i.id_semestre = g2.id_semestre
         WHERE g2.clave_materia = g.clave_materia
           AND g2.id_profesor = ?
           AND i.Estado IN ('Activa', 'Aprobada', 'Reprobada')
           AND (SELECT MAX(c.calificacion) FROM Calificacion c
                WHERE c.id_alumno = i.id_alumno 
                  AND c.num_grupo = i.num_grupo AND c.id_profesor = i.id_profesor
                  AND c.clave_materia = i.clave_materia AND c.id_semestre = i.id_semestre
                  AND c.tipo IN ('EO','EE','ET','ER')) < 6) AS reprobados
    
    FROM Grupo g
    INNER JOIN Materia m ON g.clave_materia = m.clave_materia
    WHERE g.id_profesor = ?
    GROUP BY g.clave_materia, m.nombre
    ORDER BY g.clave_materia
";
$stmt = $pdo->prepare($sql_materias);
$stmt->execute([$id_profesor, $id_profesor, $id_profesor]);
$materias_stats = $stmt->fetchAll();

// Totales globales para el pastel
$total_aprobados_global = array_sum(array_column($materias_stats, 'aprobados'));
$total_reprobados_global = array_sum(array_column($materias_stats, 'reprobados'));

// Distribución de calififaciones
$sql_dist = "
    SELECT calificacion
    FROM Calificacion
    WHERE id_profesor = ?
      AND tipo IN ('EO', 'EE', 'ET', 'ER')
";
$stmt = $pdo->prepare($sql_dist);
$stmt->execute([$id_profesor]);
$todas_calificaciones = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Inicializar cals 0-10
$buckets = array_fill(0, 11, 0);
foreach ($todas_calificaciones as $c) {
    $bucket = (int) floor($c);
    if ($bucket > 10) $bucket = 10; // por si hay una 10.0 exacta
    $buckets[$bucket]++;
}

// Preparar datos para JS
$data_grupos = [
    'labels'        => array_column($grupos_stats, 'etiqueta'),
    'aprobados'     => array_map('intval', array_column($grupos_stats, 'aprobados')),
    'reprobados'    => array_map('intval', array_column($grupos_stats, 'reprobados')),
    'sin_calificar' => array_map('intval', array_column($grupos_stats, 'sin_calificar'))
];

$data_pastel = [
    'aprobados'  => (int) $total_aprobados_global,
    'reprobados' => (int) $total_reprobados_global
];

$data_dist = [
    'labels'   => ['0-1', '1-2', '2-3', '3-4', '4-5', '5-6', '6-7', '7-8', '8-9', '9-10', '10'],
    'valores'  => array_values($buckets)
];

$hay_datos_grupos   = count($grupos_stats) > 0;
$hay_datos_pastel   = ($total_aprobados_global + $total_reprobados_global) > 0;
$hay_datos_dist     = count($todas_calificaciones) > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas — Panel Profesor</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="icon" type="image/svg+xml" href="assets/icons/favicon.svg">
    <!-- Chart.js  -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Estadísticas</span>
                <span class="txt-academico">Profesor</span>
            </h1>
        </div>
        <nav>
            <ul>
                <li><a href="profesor.php" class="btn-nav">
                    <iconify-icon icon="heroicons:home-solid"></iconify-icon><span>Inicio</span>
                </a></li>
                <li><a href="logout.php" class="btn-nav">
                    <iconify-icon icon="heroicons:arrow-right-on-rectangle-solid"></iconify-icon><span>Salir</span>
                </a></li>
            </ul>
        </nav>
    </header>

    <main class="alumno-contenedor">
        <section class="perfil-card">
            <div class="perfil-info">
                <div class="info-grupo">
                    <span class="label">Profesor:</span>
                    <span class="valor"><?= htmlspecialchars("{$profesor['Nombres']} {$profesor['Apellido1']}") ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Calificaciones registradas:</span>
                    <span class="valor"><?= count($todas_calificaciones) ?></span>
                </div>
            </div>
        </section>

        <!-- Barras por grupo -->
        <section class="chart-card">
            <h2>Reprobación por grupo</h2>
            <p style="color: #666; margin-bottom: 15px; font-size: 0.9rem;">
                Aprobados, reprobados y sin calificar de cada grupo que impartes.
            </p>
            <?php if ($hay_datos_grupos): ?>
                <div class="chart-container">
                    <canvas id="chart_grupos"></canvas>
                </div>
            <?php else: ?>
                <p class="chart-empty">No tienes grupos asignados todavía.</p>
            <?php endif; ?>
        </section>

        <!-- Pastel general -->
        <section class="chart-card">
            <h2>Reprobación global (todas tus materias)</h2>
            <p style="color: #666; margin-bottom: 15px; font-size: 0.9rem;">
                Proporción global de aprobados vs reprobados en todos los grupos que has impartido.
            </p>
            <?php if ($hay_datos_pastel): ?>
                <div class="chart-container chart-container-small">
                    <canvas id="chart_pastel"></canvas>
                </div>
            <?php else: ?>
                <p class="chart-empty">Aún no hay suficientes calificaciones finales registradas.</p>
            <?php endif; ?>
        </section>

        <!-- Distribución -->
        <section class="chart-card">
            <h2>Distribución de calificaciones</h2>
            <p style="color: #666; margin-bottom: 15px; font-size: 0.9rem;">
                Histograma de todas las calificaciones de exámenes (EO/EE/ET/ER) que has asignado.
            </p>
            <?php if ($hay_datos_dist): ?>
                <div class="chart-container">
                    <canvas id="chart_dist"></canvas>
                </div>
            <?php else: ?>
                <p class="chart-empty">Aún no has registrado calificaciones de exámenes.</p>
            <?php endif; ?>
        </section>
    </main>

    <script>
    // DATOS DESDE PHP 
    const dataGrupos = <?= json_encode($data_grupos) ?>;
    const dataPastel = <?= json_encode($data_pastel) ?>;
    const dataDist   = <?= json_encode($data_dist) ?>;

    // Colores
    const colores = {
        aprobado: '#769127',      // verde tipo --btn-activof
        reprobado: '#dc2626',     // rojo
        sin_calificar: '#A3A3A3', // gris
        bar: '#6DB1BF',           // azul tipo --azul1
        bar_border: '#114B5F'     // azul oscuro
    };

    // Barras por grupo
    <?php if ($hay_datos_grupos): ?>
    new Chart(document.getElementById('chart_grupos'), {
        type: 'bar',
        data: {
            labels: dataGrupos.labels,
            datasets: [
                {
                    label: 'Aprobados',
                    data: dataGrupos.aprobados,
                    backgroundColor: colores.aprobado
                },
                {
                    label: 'Reprobados',
                    data: dataGrupos.reprobados,
                    backgroundColor: colores.reprobado
                },
                {
                    label: 'Sin calificar',
                    data: dataGrupos.sin_calificar,
                    backgroundColor: colores.sin_calificar
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                title: { display: false }
            },
            scales: {
                x: { stacked: true },
                y: { 
                    stacked: true, 
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
    <?php endif; ?>

    // Pastel 
    <?php if ($hay_datos_pastel): ?>
    new Chart(document.getElementById('chart_pastel'), {
        type: 'pie',
        data: {
            labels: ['Aprobados', 'Reprobados'],
            datasets: [{
                data: [dataPastel.aprobados, dataPastel.reprobados],
                backgroundColor: [colores.aprobado, colores.reprobado],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                            return `${ctx.label}: ${ctx.parsed} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Histograma
    <?php if ($hay_datos_dist): ?>
    new Chart(document.getElementById('chart_dist'), {
        type: 'bar',
        data: {
            labels: dataDist.labels,
            datasets: [{
                label: 'Cantidad de calificaciones',
                data: dataDist.valores,
                backgroundColor: dataDist.labels.map((_, i) => 
                    i < 6 ? colores.reprobado : colores.aprobado
                ),
                borderWidth: 1,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { 
                    title: { display: true, text: 'Rango de calificación' } 
                },
                y: { 
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    title: { display: true, text: 'Número de calificaciones' }
                }
            }
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>