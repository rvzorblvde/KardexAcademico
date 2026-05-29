<?php 
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/connection.php';

// Modo edición
$materia_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM Materia WHERE clave_materia = ?");
    $stmt->execute([$_GET['editar']]);
    $materia_editar = $stmt->fetch() ?: null;
}

// Listado con carrera y conteo de grupos
$materias = $pdo->query("
    SELECT m.clave_materia, m.nombre, m.creditos, m.num_parciales, m.id_carrera,
           c.Nombre AS carrera,
           (SELECT COUNT(*) FROM Grupo g WHERE g.clave_materia = m.clave_materia) AS num_grupos
    FROM Materia m
    INNER JOIN Carrera c ON m.id_carrera = c.id_carrera
    ORDER BY m.clave_materia
")->fetchAll();

// Catálogo de carreras 
$carreras = $pdo->query("SELECT id_carrera, Nombre FROM Carrera ORDER BY Nombre")->fetchAll();

$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Materias</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="icon" type="image/svg+xml" href="assets/icons/favicon.svg">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Gestor</span>
                <span class="txt-academico">Materias</span>
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

        <section class="perfil-card">
            <h2><?= $materia_editar ? 'Editar materia' : 'Nueva materia' ?></h2>

            <form action="actions/materia_guardar.php" method="POST" class="form-admin">
                <?php if ($materia_editar): ?>
                    <input type="hidden" name="clave_original" 
                           value="<?= htmlspecialchars($materia_editar['clave_materia']) ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="input-grupo">
                        <label>Clave de materia:</label>
                        <input type="text" name="clave_materia" required maxlength="10"
                               placeholder="Ej. COMP-101"
                               value="<?= htmlspecialchars($materia_editar['clave_materia'] ?? '') ?>">
                    </div>

                    <div class="input-grupo">
                        <label>Nombre:</label>
                        <input type="text" name="nombre" required maxlength="100"
                               placeholder="Ej. Programación Web"
                               value="<?= htmlspecialchars($materia_editar['nombre'] ?? '') ?>">
                    </div>

                    <div class="input-grupo">
                        <label>Carrera:</label>
                        <select name="id_carrera" required>
                            <option value="">— Selecciona una carrera —</option>
                            <?php foreach ($carreras as $c): ?>
                                <option value="<?= $c['id_carrera'] ?>"
                                    <?= ($materia_editar['id_carrera'] ?? null) == $c['id_carrera'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['Nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-grupo">
                        <label>Créditos:</label>
                        <input type="number" name="creditos" required min="1" max="20"
                               value="<?= $materia_editar['creditos'] ?? 8 ?>">
                    </div>

                    <div class="input-grupo">
                        <label>Número de parciales:</label>
                        <select name="num_parciales" required>
                            <option value="3" <?= ($materia_editar['num_parciales'] ?? 3) == 3 ? 'selected' : '' ?>>3 parciales</option>
                            <option value="4" <?= ($materia_editar['num_parciales'] ?? null) == 4 ? 'selected' : '' ?>>4 parciales</option>
                        </select>
                    </div>
                </div>

                <div class="form-acciones">
                    <button type="submit" class="btn-login">
                        <?= $materia_editar ? 'Actualizar' : 'Crear materia' ?>
                    </button>
                    <?php if ($materia_editar): ?>
                        <a href="materias.php" class="btn-cancelar">Cancelar edición</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="tabla-scroll">
            <h2 style="margin-bottom: 15px;">Materias registradas (<?= count($materias) ?>)</h2>
            
            <?php if (count($carreras) === 0): ?>
                <p style="color: #999; padding: 20px; text-align: center;">
                    Aún no hay carreras registradas. Crea una carrera primero.
                </p>
            <?php elseif (count($materias) === 0): ?>
                <p style="color: #999; padding: 20px; text-align: center;">
                    No hay materias registradas todavía.
                </p>
            <?php else: ?>
                <table class="tabla-kardex">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Nombre</th>
                            <th>Carrera</th>
                            <th>Créditos</th>
                            <th>Parciales</th>
                            <th>Grupos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($materias as $m): 
                        $tiene_grupos = $m['num_grupos'] > 0;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['clave_materia']) ?></strong></td>
                            <td><?= htmlspecialchars($m['nombre']) ?></td>
                            <td><?= htmlspecialchars($m['carrera']) ?></td>
                            <td><?= $m['creditos'] ?></td>
                            <td><?= $m['num_parciales'] ?></td>
                            <td>
                                <?php if ($tiene_grupos): ?>
                                    <span class="estado-pill activo"><?= $m['num_grupos'] ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?editar=<?= urlencode($m['clave_materia']) ?>" 
                                   class="btn-tabla" title="Editar">
                                    <iconify-icon icon="heroicons:pencil-square-solid"></iconify-icon>
                                </a>
                                <form action="actions/materia_eliminar.php" method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar la materia <?= htmlspecialchars($m['clave_materia']) ?>? Esta acción es permanente.')">
                                    <input type="hidden" name="clave_materia" 
                                           value="<?= htmlspecialchars($m['clave_materia']) ?>">
                                    <button type="submit" class="btn-tabla btn-rojo" 
                                            title="<?= $tiene_grupos ? 'No se puede: hay grupos asociados' : 'Eliminar' ?>"
                                            <?= $tiene_grupos ? 'disabled' : '' ?>>
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