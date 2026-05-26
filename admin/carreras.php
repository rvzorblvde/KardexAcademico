<?php 
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/connection.php';

// Modo edición
$carrera_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM Carrera WHERE id_carrera = ?");
    $stmt->execute([(int) $_GET['editar']]);
    $carrera_editar = $stmt->fetch();
}

// Listado con conteo de dependencias (alumnos y materias)
$carreras = $pdo->query("
    SELECT c.id_carrera, c.clave_carrera, c.Nombre,
           (SELECT COUNT(*) FROM Alumno  a WHERE a.id_carrera = c.id_carrera) AS num_alumnos,
           (SELECT COUNT(*) FROM Materia m WHERE m.id_carrera = c.id_carrera) AS num_materias
    FROM Carrera c
    ORDER BY c.clave_carrera
")->fetchAll();

$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Carreras</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Gestor</span>
                <span class="txt-academico">Carreras</span>
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

        <!-- ============ FORMULARIO ============ -->
        <section class="perfil-card">
            <h2><?= $carrera_editar ? 'Editar carrera' : 'Nueva carrera' ?></h2>

            <form action="actions/carrera_guardar.php" method="POST" class="form-admin">
                <?php if ($carrera_editar): ?>
                    <input type="hidden" name="id_carrera" 
                           value="<?= $carrera_editar['id_carrera'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="input-grupo">
                        <label>Clave (3 letras):</label>
                        <input type="text" name="clave_carrera" required 
                               maxlength="3" minlength="2"
                               style="text-transform: uppercase;"
                               placeholder="Ej. IC, ICA, IM"
                               value="<?= htmlspecialchars($carrera_editar['clave_carrera'] ?? '') ?>">
                    </div>

                    <div class="input-grupo">
                        <label>Nombre completo:</label>
                        <input type="text" name="nombre" required maxlength="80"
                               placeholder="Ej. Ingeniería en Computación"
                               value="<?= htmlspecialchars($carrera_editar['Nombre'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-acciones">
                    <button type="submit" class="btn-login">
                        <?= $carrera_editar ? 'Actualizar' : 'Crear carrera' ?>
                    </button>
                    <?php if ($carrera_editar): ?>
                        <a href="carreras.php" class="btn-cancelar">Cancelar edición</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- ============ LISTADO ============ -->
        <section class="tabla-scroll">
            <h2 style="margin-bottom: 15px;">Carreras registradas (<?= count($carreras) ?>)</h2>
            <table class="tabla-kardex">
                <thead>
                    <tr>
                        <th>Clave</th>
                        <th>Nombre</th>
                        <th>Alumnos</th>
                        <th>Materias</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($carreras as $c): 
                    $tiene_dependencias = $c['num_alumnos'] > 0 || $c['num_materias'] > 0;
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['clave_carrera']) ?></strong></td>
                        <td><?= htmlspecialchars($c['Nombre']) ?></td>
                        <td>
                            <?php if ($c['num_alumnos'] > 0): ?>
                                <span class="estado-pill activo"><?= $c['num_alumnos'] ?></span>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['num_materias'] > 0): ?>
                                <span class="estado-pill activo"><?= $c['num_materias'] ?></span>
                            <?php else: ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?editar=<?= $c['id_carrera'] ?>" 
                               class="btn-tabla" title="Editar">
                                <iconify-icon icon="heroicons:pencil-square-solid"></iconify-icon>
                            </a>
                            <form action="actions/carrera_eliminar.php" method="POST" style="display:inline"
                                  onsubmit="return confirm('¿Eliminar la carrera <?= htmlspecialchars($c['clave_carrera']) ?>? Esta acción es permanente.')">
                                <input type="hidden" name="id_carrera" value="<?= $c['id_carrera'] ?>">
                                <button type="submit" class="btn-tabla btn-rojo" 
                                        title="<?= $tiene_dependencias ? 'No se puede: hay dependencias' : 'Eliminar' ?>"
                                        <?= $tiene_dependencias ? 'disabled' : '' ?>>
                                    <iconify-icon icon="heroicons:trash-solid"></iconify-icon>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>