<?php 
require_once __DIR__ . '/includes/auth_alumno.php';
require_once __DIR__ . '/includes/connection.php';

$id_alumno = $_SESSION['user_id'];

// ============ DATOS DEL ALUMNO ============
$stmt = $pdo->prepare("
    SELECT a.*, c.Nombre AS carrera_nombre, c.clave_carrera
    FROM Alumno a
    INNER JOIN Carrera c ON a.id_carrera = c.id_carrera
    WHERE a.id_alumno = ?
");
$stmt->execute([$id_alumno]);
$alumno = $stmt->fetch();

if (!$alumno) {
    header("Location: logout.php");
    exit();
}

// ============ SEMESTRE ACTIVO ============
$stmt = $pdo->query("SELECT * FROM Semestre WHERE activo = TRUE LIMIT 1");
$semestre_actual = $stmt->fetch();

// ============ MATERIAS INSCRITAS EN EL SEMESTRE ACTIVO ============
$materias_actuales = [];
if ($semestre_actual) {
    $stmt = $pdo->prepare("
        SELECT 
            i.num_grupo, i.id_profesor, i.clave_materia, i.id_semestre, i.Estado,
            m.nombre AS materia_nombre, m.creditos, m.num_parciales,
            g.salon,
            CONCAT(p.Nombres, ' ', p.Apellido1) AS profesor_nombre
        FROM Inscripcion i
        INNER JOIN Materia  m ON i.clave_materia = m.clave_materia
        INNER JOIN Profesor p ON i.id_profesor   = p.id_profesor
        INNER JOIN Grupo    g ON i.num_grupo     = g.num_grupo 
                              AND i.id_profesor  = g.id_profesor
                              AND i.clave_materia = g.clave_materia 
                              AND i.id_semestre  = g.id_semestre
        WHERE i.id_alumno = ? AND i.id_semestre = ?
          AND i.Estado = 'Activa'
        ORDER BY m.clave_materia
    ");
    $stmt->execute([$id_alumno, $semestre_actual['id_semestre']]);
    $materias_actuales = $stmt->fetchAll();
}

// ============ HORARIO DEL SEMESTRE ACTIVO ============
$horarios_raw = [];
if ($semestre_actual && count($materias_actuales) > 0) {
    $stmt = $pdo->prepare("
        SELECT h.dia, h.hora_inicio, h.hora_fin,
               g.clave_materia, g.salon,
               m.nombre AS materia_nombre,
               CONCAT(p.Nombres, ' ', p.Apellido1) AS profesor_nombre
        FROM Horario h
        INNER JOIN Grupo g ON h.num_grupo = g.num_grupo 
                           AND h.id_profesor = g.id_profesor
                           AND h.clave_materia = g.clave_materia 
                           AND h.id_semestre = g.id_semestre
        INNER JOIN Inscripcion i ON g.num_grupo = i.num_grupo 
                                 AND g.id_profesor = i.id_profesor
                                 AND g.clave_materia = i.clave_materia 
                                 AND g.id_semestre = i.id_semestre
        INNER JOIN Materia m ON g.clave_materia = m.clave_materia
        INNER JOIN Profesor p ON g.id_profesor = p.id_profesor
        WHERE i.id_alumno = ? AND i.id_semestre = ? AND i.Estado = 'Activa'
        ORDER BY FIELD(h.dia, 'Lun','Mar','Mie','Jue','Vie','Sab'), h.hora_inicio
    ");
    $stmt->execute([$id_alumno, $semestre_actual['id_semestre']]);
    $horarios_raw = $stmt->fetchAll();
}

// Organizar horarios en una matriz [hora][dia] para mostrar como tabla
$horario_matriz = [];
$horas_unicas = [];
foreach ($horarios_raw as $h) {
    $key_hora = substr($h['hora_inicio'], 0, 5) . ' - ' . substr($h['hora_fin'], 0, 5);
    $horas_unicas[$key_hora] = true;
    $horario_matriz[$key_hora][$h['dia']] = $h;
}
ksort($horas_unicas);
$horas_unicas = array_keys($horas_unicas);

$dias_orden = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];
$dias_nombre = ['Lun' => 'Lunes', 'Mar' => 'Martes', 'Mie' => 'Miércoles',
                'Jue' => 'Jueves', 'Vie' => 'Viernes', 'Sab' => 'Sábado'];

// Solo mostrar columnas de días que tienen al menos una clase
$dias_con_clase = [];
foreach ($horarios_raw as $h) {
    $dias_con_clase[$h['dia']] = true;
}
$dias_visibles = array_filter($dias_orden, fn($d) => isset($dias_con_clase[$d]));

// ============ ESTADÍSTICAS ============
// Materias cursadas: inscripciones con estado Aprobada o Reprobada (histórico)
// + las del semestre actual con cualquier calif final
$stmt = $pdo->prepare("
    SELECT 
        i.clave_materia, i.id_semestre, i.Estado,
        m.nombre AS materia_nombre, m.creditos,
        (SELECT MAX(c.calificacion) FROM Calificacion c
         WHERE c.id_alumno = i.id_alumno 
           AND c.num_grupo = i.num_grupo AND c.id_profesor = i.id_profesor
           AND c.clave_materia = i.clave_materia AND c.id_semestre = i.id_semestre
           AND c.tipo IN ('EO', 'EE', 'ET', 'ER')) AS calificacion_final
    FROM Inscripcion i
    INNER JOIN Materia m ON i.clave_materia = m.clave_materia
    WHERE i.id_alumno = ?
      AND i.Estado IN ('Activa', 'Aprobada', 'Reprobada')
    ORDER BY i.id_semestre DESC, i.clave_materia
");
$stmt->execute([$id_alumno]);
$todas_inscripciones = $stmt->fetchAll();

// Calcular métricas
$materias_cursadas = 0;
$materias_aprobadas = 0;
$creditos_acumulados = 0;
$suma_calif = 0;
$count_calif = 0;
$calificaciones_finales = []; // para la gráfica de distribución

foreach ($todas_inscripciones as $insc) {
    $calif = $insc['calificacion_final'];
    if ($calif === null) continue; // sin calificar todavía
    
    $materias_cursadas++;
    $calif_num = (float) $calif;
    $suma_calif += $calif_num;
    $count_calif++;
    $calificaciones_finales[] = $calif_num;
    
    if ($calif_num >= 6) {
        $materias_aprobadas++;
        $creditos_acumulados += $insc['creditos'];
    }
}

$promedio_general = $count_calif > 0 ? round($suma_calif / $count_calif, 2) : 0;

// Distribución de calificaciones (buckets 0-10)
$buckets = array_fill(0, 11, 0);
foreach ($calificaciones_finales as $c) {
    $b = (int) floor($c);
    if ($b > 10) $b = 10;
    $buckets[$b]++;
}

$data_dist = [
    'labels'  => ['0-1','1-2','2-3','3-4','4-5','5-6','6-7','7-8','8-9','9-10','10'],
    'valores' => array_values($buckets)
];

$tiene_calificaciones = count($calificaciones_finales) > 0;
$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Kárdex — <?= htmlspecialchars($alumno['Nombres']) ?></title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="styles/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Mi</span>
                <span class="txt-academico">Kárdex</span>
            </h1>
        </div>
        <nav>
            <ul>
                <li><a href="alumno.php" class="btn-nav">
                    <iconify-icon icon="heroicons:home-solid"></iconify-icon><span>Inicio</span>
                </a></li>
                <li><a href="horarios.php" class="btn-nav">
                    <iconify-icon icon="heroicons:academic-cap-solid"></iconify-icon><span>Horarios</span>
                </a></li>
                <li><a href="logout.php" class="btn-nav">
                    <iconify-icon icon="heroicons:arrow-right-on-rectangle-solid"></iconify-icon><span>Salir</span>
                </a></li>
            </ul>
        </nav>
    </header>

    <main class="alumno-contenedor">
        <?php if ($msg): ?>
            <div class="alerta-flash"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- ============ TARJETA DE PERFIL ============ -->
        <section class="perfil-card">
            <div class="perfil-info">
                <div class="info-grupo">
                    <span class="label">Alumno:</span>
                    <span class="valor"><?= htmlspecialchars("{$alumno['Nombres']} {$alumno['Apellido1']} {$alumno['Apellido2']}") ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Matrícula:</span>
                    <span class="valor">a<?= $alumno['id_alumno'] ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Carrera:</span>
                    <span class="valor"><?= htmlspecialchars($alumno['carrera_nombre']) ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Semestre vigente:</span>
                    <span class="valor"><?= $semestre_actual ? htmlspecialchars($semestre_actual['nombre']) : '— ningún semestre vigente —' ?></span>
                </div>
            </div>
        </section>

        <!-- ============ TABS DE NAVEGACIÓN ============ -->
        <nav class="alumno-nav">
            <button class="tab-btn active" data-target="tab-horario">
                <iconify-icon icon="heroicons:calendar-days-solid"></iconify-icon> Mi horario
            </button>
            <button class="tab-btn" data-target="tab-materias">
                <iconify-icon icon="heroicons:book-open-solid"></iconify-icon> Materias
            </button>
            <button class="tab-btn" data-target="tab-estadisticas">
                <iconify-icon icon="heroicons:chart-bar-solid"></iconify-icon> Estadísticas
            </button>
        </nav>

        <!-- ============ TAB 1: HORARIO ============ -->
        <section id="tab-horario" class="content-item active">
            <div class="tabla-scroll">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2>Horario del semestre <?= $semestre_actual ? htmlspecialchars($semestre_actual['nombre']) : '' ?></h2>
                    <?php if (count($horarios_raw) > 0): ?>
                        <button onclick="imprimirHorario()" class="btn-tabla" style="background: var(--btn-normalf); padding: 10px 20px;">
                            <iconify-icon icon="heroicons:printer-solid"></iconify-icon>
                            Imprimir PDF
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (count($horarios_raw) === 0): ?>
                    <p style="color: #999; padding: 20px; text-align: center;">
                        No tienes clases asignadas en el semestre vigente.
                    </p>
                <?php else: ?>
                    <div id="horario-imprimible">
                        <table class="tabla-kardex tabla-horario">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <?php foreach ($dias_visibles as $dia): ?>
                                        <th><?= $dias_nombre[$dia] ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($horas_unicas as $hora): ?>
                                <tr>
                                    <td class="celda-hora"><strong><?= $hora ?></strong></td>
                                    <?php foreach ($dias_visibles as $dia): 
                                        $clase = $horario_matriz[$hora][$dia] ?? null;
                                    ?>
                                        <td class="<?= $clase ? 'celda-clase' : '' ?>">
                                            <?php if ($clase): ?>
                                                <strong><?= htmlspecialchars($clase['clave_materia']) ?></strong><br>
                                                <small><?= htmlspecialchars($clase['materia_nombre']) ?></small><br>
                                                <small style="color: #555;">
                                                    <iconify-icon icon="heroicons:map-pin-solid"></iconify-icon>
                                                    <?= htmlspecialchars($clase['salon'] ?: '—') ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

 <!-- ============ LIBRERÍAS EXTERNAS ============ -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- ============ SCRIPT INLINE DE LA PÁGINA ============ -->
    <script>
        // ===== DATOS DESDE PHP =====
        const dataDist = <?= json_encode($data_dist) ?>;
        const datosAlumno = {
            id:       <?= json_encode($alumno['id_alumno']) ?>,
            nombre:   <?= json_encode("{$alumno['Nombres']} {$alumno['Apellido1']} {$alumno['Apellido2']}") ?>,
            carrera:  <?= json_encode($alumno['carrera_nombre']) ?>,
            semestre: <?= json_encode($semestre_actual['nombre'] ?? '') ?>
        };

        // ===== TABS =====
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.content-item').forEach(c => c.classList.remove('active'));
                
                btn.classList.add('active');
                const target = btn.dataset.target;
                if (target) {
                    document.getElementById(target).classList.add('active');
                }
            });
        });

        // ===== GRÁFICA =====
        <?php if ($tiene_calificaciones): ?>
        new Chart(document.getElementById('chart_distribucion'), {
            type: 'bar',
            data: {
                labels: dataDist.labels,
                datasets: [{
                    label: 'Calificaciones',
                    data: dataDist.valores,
                    backgroundColor: dataDist.labels.map((_, i) => 
                        i < 6 ? '#dc2626' : '#769127'
                    ),
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
        <?php endif; ?>

        // ===== IMPRESIÓN A PDF =====
        async function imprimirHorario() {
            const { jsPDF } = window.jspdf;
            const elemento = document.getElementById('horario-imprimible');
            
            if (!elemento) {
                alert('No hay horario para imprimir.');
                return;
            }

            const canvas = await html2canvas(elemento, {
                scale: 2,
                backgroundColor: '#ffffff',
                useCORS: true
            });

            const imgData = canvas.toDataURL('image/png');
            
            const pdf = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'letter'
            });

            // Encabezado con datos del alumno
            pdf.setFontSize(18);
            pdf.setFont('helvetica', 'bold');
            pdf.text('Horario de clases', 15, 18);

            pdf.setFontSize(11);
            pdf.setFont('helvetica', 'normal');
            pdf.text(`Alumno: ${datosAlumno.nombre}`, 15, 28);
            pdf.text(`Matrícula: a${datosAlumno.id}`, 15, 34);
            pdf.text(`Carrera: ${datosAlumno.carrera}`, 15, 40);
            pdf.text(`Semestre: ${datosAlumno.semestre}`, 15, 46);

            // Fecha de impresión
            const ahora = new Date().toLocaleString('es-MX', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit'
            });
            pdf.text(`Generado: ${ahora}`, 200, 28);

            // Imagen del horario
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth() - 30;
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

            pdf.addImage(imgData, 'PNG', 15, 55, pdfWidth, pdfHeight);

            // Pie
            pdf.setFontSize(8);
            pdf.setTextColor(120);
            pdf.text('Kárdex Académico — Documento informativo', 15, pdf.internal.pageSize.getHeight() - 10);

            pdf.save(`horario_a${datosAlumno.id}_${datosAlumno.semestre}.pdf`);
        }
    </script>

        <!-- ============ TAB 2: MATERIAS INSCRITAS ============ -->
        <section id="tab-materias" class="content-item">
            <div class="tabla-scroll">
                <h2 style="margin-bottom: 15px;">
                    Materias inscritas (<?= count($materias_actuales) ?>)
                </h2>

                <?php if (count($materias_actuales) === 0): ?>
                    <p style="color: #999; padding: 20px; text-align: center;">
                        No tienes materias inscritas en el semestre vigente.
                    </p>
                <?php else: ?>
                    <table class="tabla-kardex">
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Materia</th>
                                <th>Profesor</th>
                                <th>Grupo</th>
                                <th>Salón</th>
                                <th>Créditos</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($materias_actuales as $m): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($m['clave_materia']) ?></strong></td>
                                <td><?= htmlspecialchars($m['materia_nombre']) ?></td>
                                <td><?= htmlspecialchars($m['profesor_nombre']) ?></td>
                                <td><?= $m['num_grupo'] ?></td>
                                <td><?= htmlspecialchars($m['salon'] ?: '—') ?></td>
                                <td><?= $m['creditos'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>

        <!-- ============ TAB 3: ESTADÍSTICAS ============ -->
        <section id="tab-estadisticas" class="content-item">
            <!-- KPIs -->
            <div class="kpis-grid">
                <div class="kpi-card">
                    <div class="kpi-icono"><iconify-icon icon="heroicons:book-open-solid"></iconify-icon></div>
                    <div class="kpi-numero"><?= $materias_cursadas ?></div>
                    <div class="kpi-label">Materias cursadas</div>
                </div>
                <div class="kpi-card kpi-verde">
                    <div class="kpi-icono"><iconify-icon icon="heroicons:check-circle-solid"></iconify-icon></div>
                    <div class="kpi-numero"><?= $materias_aprobadas ?></div>
                    <div class="kpi-label">Materias aprobadas</div>
                </div>
                <div class="kpi-card kpi-azul">
                    <div class="kpi-icono"><iconify-icon icon="heroicons:academic-cap-solid"></iconify-icon></div>
                    <div class="kpi-numero"><?= $creditos_acumulados ?></div>
                    <div class="kpi-label">Créditos acumulados</div>
                </div>
                <div class="kpi-card kpi-naranja">
                    <div class="kpi-icono"><iconify-icon icon="heroicons:chart-bar-solid"></iconify-icon></div>
                    <div class="kpi-numero"><?= number_format($promedio_general, 2) ?></div>
                    <div class="kpi-label">Promedio general</div>
                </div>
            </div>

            <!-- Gráfica de distribución -->
            <section class="chart-card" style="margin-top: 25px;">
                <h2>Distribución de mis calificaciones</h2>
                <p style="color: #666; margin-bottom: 15px; font-size: 0.9rem;">
                    Cuántas calificaciones tienes en cada rango.
                </p>
                <?php if ($tiene_calificaciones): ?>
                    <div class="chart-container">
                        <canvas id="chart_distribucion"></canvas>
                    </div>
                <?php else: ?>
                    <p class="chart-empty">Aún no tienes calificaciones finales registradas.</p>
                <?php endif; ?>
            </section>
        </section>
    </main>

    <!-- Datos del alumno y horario para JS (PDF e interacciones) -->
    <script>
        // ============ DATOS PARA JS ============
        const dataDist = <?= json_encode($data_dist) ?>;
        const datosAlumno = {
            id:      <?= json_encode($alumno['id_alumno']) ?>,
            nombre:  <?= json_encode("{$alumno['Nombres']} {$alumno['Apellido1']} {$alumno['Apellido2']}") ?>,
            carrera: <?= json_encode($alumno['carrera_nombre']) ?>,
            semestre: <?= json_encode($semestre_actual['nombre'] ?? '') ?>
        };

        // ============ TABS ============
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.content-item').forEach(c => c.classList.remove('active'));
                
                btn.classList.add('active');
                const target = btn.dataset.target;
                if (target) {
                    document.getElementById(target).classList.add('active');
                }
            });
        });

        // ============ GRÁFICA ============
        <?php if ($tiene_calificaciones): ?>
        new Chart(document.getElementById('chart_distribucion'), {
            type: 'bar',
            data: {
                labels: dataDist.labels,
                datasets: [{
                    label: 'Calificaciones',
                    data: dataDist.valores,
                    backgroundColor: dataDist.labels.map((_, i) => 
                        i < 6 ? '#dc2626' : '#769127'
                    ),
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
        <?php endif; ?>

        // ============ IMPRESIÓN (placeholder, lo implementamos en Parte C) ============
        async function imprimirHorario() {
            alert('La función de PDF se agregará en el siguiente paso.');
        }
    </script>
</body>
</html>