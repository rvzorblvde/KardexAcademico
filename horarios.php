<?php 
// VISTA PÚBLICA — no requiere login
require_once __DIR__ . '/includes/connection.php';

// Semestre activo
$stmt = $pdo->query("SELECT * FROM Semestre WHERE activo = TRUE LIMIT 1");
$semestre_actual = $stmt->fetch();

// Si hay sesión activa, lo sabemos para mostrar/ocultar botones
session_start();
$logueado = isset($_SESSION['user_id']);

// Cargar todos los grupos del semestre activo con sus horarios
$grupos = [];
if ($semestre_actual) {
    $stmt = $pdo->prepare("
        SELECT 
            g.num_grupo, g.clave_materia, g.id_semestre, g.salon, g.cupo,
            m.nombre AS materia_nombre,
            m.id_carrera,
            c.Nombre AS carrera_nombre,
            c.clave_carrera,
            CONCAT(p.Nombres, ' ', p.Apellido1, ' ', COALESCE(p.Apellido2, '')) AS profesor_nombre,
            (SELECT COUNT(*) FROM Inscripcion i
             WHERE i.num_grupo = g.num_grupo AND i.id_profesor = g.id_profesor
               AND i.clave_materia = g.clave_materia AND i.id_semestre = g.id_semestre
               AND i.Estado = 'Activa') AS inscritos,
            -- Concatenar todos los horarios del grupo en un solo campo
            (SELECT GROUP_CONCAT(
                CONCAT(h.dia, ' ', SUBSTR(h.hora_inicio, 1, 5), '-', SUBSTR(h.hora_fin, 1, 5))
                ORDER BY FIELD(h.dia, 'Lun','Mar','Mie','Jue','Vie','Sab'), h.hora_inicio
                SEPARATOR ' | '
             ) FROM Horario h 
             WHERE h.num_grupo = g.num_grupo AND h.id_profesor = g.id_profesor
               AND h.clave_materia = g.clave_materia AND h.id_semestre = g.id_semestre
            ) AS horario_str
        FROM Grupo g
        INNER JOIN Materia m  ON g.clave_materia = m.clave_materia
        INNER JOIN Carrera c  ON m.id_carrera = c.id_carrera
        INNER JOIN Profesor p ON g.id_profesor = p.id_profesor
        WHERE g.id_semestre = ?
        ORDER BY c.clave_carrera, m.clave_materia, g.num_grupo
    ");
    $stmt->execute([$semestre_actual['id_semestre']]);
    $grupos = $stmt->fetchAll();
}

// Catálogo de carreras (solo las que tienen al menos un grupo en el semestre actual)
$carreras_con_grupos = [];
foreach ($grupos as $g) {
    $carreras_con_grupos[$g['clave_carrera']] = $g['carrera_nombre'];
}
ksort($carreras_con_grupos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios — Kárdex Académico</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Kárdex</span>
                <span class="txt-academico">Académico</span>
            </h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.html" class="btn-nav">
                    <iconify-icon icon="heroicons:home-solid"></iconify-icon><span>Inicio</span>
                </a></li>
                <li><a href="horarios.php" class="btn-nav">
                    <iconify-icon icon="heroicons:academic-cap-solid"></iconify-icon><span>Horarios</span>
                </a></li>
                <?php if ($logueado): ?>
                    <li><a href="logout.php" class="btn-nav">
                        <iconify-icon icon="heroicons:arrow-right-on-rectangle-solid"></iconify-icon>
                        <span>Salir</span>
                    </a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-nav">
                        <iconify-icon icon="heroicons:user-solid"></iconify-icon>
                        <span>Entrar</span>
                    </a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="horarios-publica">
        <!-- ============ ENCABEZADO ============ -->
        <section class="perfil-card">
            <div class="perfil-info">
                <div class="info-grupo">
                    <span class="label">Oferta académica del semestre:</span>
                    <span class="valor">
                        <?= $semestre_actual ? htmlspecialchars($semestre_actual['nombre']) : 'Sin semestre vigente' ?>
                    </span>
                </div>
                <div class="info-grupo">
                    <span class="label">Total de grupos:</span>
                    <span class="valor" id="contador-grupos"><?= count($grupos) ?></span>
                </div>
            </div>
        </section>

        <?php if (!$semestre_actual): ?>
            <section class="tabla-scroll">
                <p style="color: #999; padding: 40px; text-align: center;">
                    No hay un semestre vigente en este momento.
                </p>
            </section>
        <?php elseif (count($grupos) === 0): ?>
            <section class="tabla-scroll">
                <p style="color: #999; padding: 40px; text-align: center;">
                    Aún no hay grupos registrados para el semestre vigente.
                </p>
            </section>
        <?php else: ?>

        <!-- ============ FILTROS ============ -->
        <section class="filtros-card">
            <h2 style="margin-bottom: 15px;">
                <iconify-icon icon="heroicons:funnel-solid"></iconify-icon>
                Filtros de búsqueda
            </h2>
            
            <div class="filtros-grid">
                <div class="input-grupo">
                    <label>Carrera:</label>
                    <select id="filtro-carrera">
                        <option value="">Todas las carreras</option>
                        <?php foreach ($carreras_con_grupos as $clave => $nombre): ?>
                            <option value="<?= htmlspecialchars($clave) ?>">
                                <?= htmlspecialchars($clave) ?> — <?= htmlspecialchars($nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-grupo">
                    <label>Buscar por materia:</label>
                    <div class="input-icon">
                        <iconify-icon icon="heroicons:magnifying-glass-solid"></iconify-icon>
                        <input type="text" id="filtro-materia" placeholder="Ej. Cálculo, Programación...">
                    </div>
                </div>

                <div class="input-grupo">
                    <label>Buscar por profesor:</label>
                    <div class="input-icon">
                        <iconify-icon icon="heroicons:user-solid"></iconify-icon>
                        <input type="text" id="filtro-profesor" placeholder="Nombre o apellido">
                    </div>
                </div>

                <div class="input-grupo">
                    <label>&nbsp;</label>
                    <button id="btn-limpiar" class="btn-tabla" style="padding: 12px 20px;">
                        <iconify-icon icon="heroicons:x-circle-solid"></iconify-icon>
                        Limpiar filtros
                    </button>
                </div>
            </div>
            
            <p style="color: #555; font-size: 0.9rem; margin-top: 10px;">
                Mostrando <span id="contador-visible"><?= count($grupos) ?></span> de <?= count($grupos) ?> grupos
            </p>
        </section>

        <!-- ============ TABLA DE GRUPOS ============ -->
        <section class="tabla-scroll">
            <table class="tabla-kardex" id="tabla-grupos">
                <thead>
                    <tr>
                        <th>Carrera</th>
                        <th>Clave</th>
                        <th>Materia</th>
                        <th>Grupo</th>
                        <th>Profesor</th>
                        <th>Horario</th>
                        <th>Salón</th>
                        <th>Cupo</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-tabla">
                    <!-- Las filas se renderizan con JS -->
                </tbody>
            </table>
            
            <p id="sin-resultados" style="display: none; color: #999; padding: 40px; text-align: center;">
                No hay grupos que coincidan con los filtros aplicados.
            </p>
        </section>

        <?php endif; ?>
    </main>

    <script>
    // ============ DATOS DESDE PHP ============
    const todosLosGrupos = <?= json_encode($grupos) ?>;
    
    // ============ REFERENCIAS DOM ============
    const filtroCarrera  = document.getElementById('filtro-carrera');
    const filtroMateria  = document.getElementById('filtro-materia');
    const filtroProfesor = document.getElementById('filtro-profesor');
    const btnLimpiar     = document.getElementById('btn-limpiar');
    const cuerpoTabla    = document.getElementById('cuerpo-tabla');
    const contadorVisible = document.getElementById('contador-visible');
    const sinResultados  = document.getElementById('sin-resultados');
    const tablaGrupos    = document.getElementById('tabla-grupos');

    // ============ NORMALIZACIÓN DE TEXTO ============
    // Quitar acentos y bajar a minúsculas para búsquedas tolerantes
    function normalizar(texto) {
        return (texto || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    // ============ FILTRADO ============
    function filtrar() {
        const carrera  = filtroCarrera ? filtroCarrera.value : '';
        const materia  = normalizar(filtroMateria.value.trim());
        const profesor = normalizar(filtroProfesor.value.trim());

        const filtrados = todosLosGrupos.filter(g => {
            if (carrera && g.clave_carrera !== carrera) return false;
            
            if (materia) {
                const haystack = normalizar(g.clave_materia + ' ' + g.materia_nombre);
                if (!haystack.includes(materia)) return false;
            }
            
            if (profesor) {
                if (!normalizar(g.profesor_nombre).includes(profesor)) return false;
            }
            
            return true;
        });

        renderizar(filtrados);
    }

    // ============ RENDERIZADO ============
    function renderizar(grupos) {
        cuerpoTabla.innerHTML = '';

        if (grupos.length === 0) {
            tablaGrupos.style.display = 'none';
            sinResultados.style.display = 'block';
        } else {
            tablaGrupos.style.display = '';
            sinResultados.style.display = 'none';

            const fragment = document.createDocumentFragment();
            
            grupos.forEach(g => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${escapar(g.clave_carrera)}</strong></td>
                    <td>${escapar(g.clave_materia)}</td>
                    <td>${escapar(g.materia_nombre)}</td>
                    <td>${escapar(g.num_grupo)}</td>
                    <td>${escapar(g.profesor_nombre)}</td>
                    <td><small>${escapar(g.horario_str || 'Sin horario definido')}</small></td>
                    <td>${escapar(g.salon || '—')}</td>
                    <td>${g.inscritos} / ${g.cupo}</td>
                `;
                fragment.appendChild(tr);
            });
            
            cuerpoTabla.appendChild(fragment);
        }

        contadorVisible.textContent = grupos.length;
    }

    // ============ ESCAPE DE HTML ============
    // Previene XSS si algún dato de la BD trajera tags HTML por accidente
    function escapar(texto) {
        if (texto === null || texto === undefined) return '';
        const div = document.createElement('div');
        div.textContent = texto.toString();
        return div.innerHTML;
    }

    // ============ EVENTOS ============
    if (filtroCarrera)  filtroCarrera.addEventListener('change', filtrar);
    if (filtroMateria)  filtroMateria.addEventListener('input', filtrar);
    if (filtroProfesor) filtroProfesor.addEventListener('input', filtrar);
    
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            if (filtroCarrera)  filtroCarrera.value  = '';
            if (filtroMateria)  filtroMateria.value  = '';
            if (filtroProfesor) filtroProfesor.value = '';
            filtrar();
        });
    }

    // ============ RENDERIZADO INICIAL ============
    if (cuerpoTabla) {
        renderizar(todosLosGrupos);
    }
    </script>
</body>
</html>