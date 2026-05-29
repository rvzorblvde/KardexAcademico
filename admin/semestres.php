<?php 
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/connection.php';

// Modo edición
$semestre_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM Semestre WHERE id_semestre = ?");
    $stmt->execute([$_GET['editar']]);
    $semestre_editar = $stmt->fetch() ?: null;
}

// Listado con conteo de grupos
$semestres = $pdo->query("
    SELECT s.id_semestre, s.nombre, s.activo,
           (SELECT COUNT(*) FROM Grupo g WHERE g.id_semestre = s.id_semestre) AS num_grupos
    FROM Semestre s
    ORDER BY s.id_semestre DESC
")->fetchAll();

$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Semestres</title>
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
                <span class="txt-academico">Semestres</span>
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

        <!-- Formulario -->
        <section class="perfil-card">
            <h2><?= $semestre_editar ? 'Editar semestre' : 'Nuevo semestre' ?></h2>

            <form action="actions/semestre_guardar.php" method="POST" class="form-admin">
                <?php if ($semestre_editar): ?>
                    <input type="hidden" name="id_original" 
                           value="<?= htmlspecialchars($semestre_editar['id_semestre']) ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="input-grupo">
                        <label>ID del semestre:</label>
                        <input type="text" name="id_semestre" required maxlength="10"
                               placeholder="Ej. 2026-I, 2026-II"
                               value="<?= htmlspecialchars($semestre_editar['id_semestre'] ?? '') ?>">
                    </div>

                    <div class="input-grupo">
                        <label>Nombre completo:</label>
                        <input type="text" name="nombre" required maxlength="30"
                               placeholder="Ej. Ciclo 2026-I"
                               value="<?= htmlspecialchars($semestre_editar['nombre'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-acciones">
                    <button type="submit" class="btn-login">
                        <?= $semestre_editar ? 'Actualizar' : 'Crear semestre' ?>
                    </button>
                    <?php if ($semestre_editar): ?>
                        <a href="semestres.php" class="btn-cancelar">Cancelar edición</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- lista -->
        <section class="tabla-scroll">
            <h2 style="margin-bottom: 15px;">Semestres registrados (<?= count($semestres) ?>)</h2>

            <?php if (count($semestres) === 0): ?>
                <p style="color: #999; padding: 20px; text-align: center;">
                    No hay semestres registrados todavía.
                </p>
            <?php else: ?>
                <table class="tabla-kardex">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Grupos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($semestres as $s): 
                        $tiene_grupos = $s['num_grupos'] > 0;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['id_semestre']) ?></strong></td>
                            <td><?= htmlspecialchars($s['nombre']) ?></td>
                            <td>
                                <?php if ($tiene_grupos): ?>
                                    <span class="estado-pill activo"><?= $s['num_grupos'] ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['activo']): ?>
                                    <span class="estado-pill activo">
                                        <iconify-icon icon="heroicons:check-circle-solid"></iconify-icon>
                                        Vigente
                                    </span>
                                <?php else: ?>
                                    <span class="estado-pill inactivo">Archivado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?editar=<?= urlencode($s['id_semestre']) ?>" 
                                   class="btn-tabla" title="Editar">
                                    <iconify-icon icon="heroicons:pencil-square-solid"></iconify-icon>
                                </a>

                                <?php if (!$s['activo']): ?>
                                    <form action="actions/semestre_activar.php" method="POST" 
                                          style="display:inline"
                                          onsubmit="return confirm('¿Marcar <?= htmlspecialchars($s['id_semestre']) ?> como semestre vigente? Esto archivará el actual.')">
                                        <input type="hidden" name="id_semestre" 
                                               value="<?= htmlspecialchars($s['id_semestre']) ?>">
                                        <button type="submit" class="btn-tabla btn-verde" 
                                                title="Marcar como vigente">
                                            <iconify-icon icon="heroicons:check-circle-solid"></iconify-icon>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form action="actions/semestre_eliminar.php" method="POST" 
                                      style="display:inline"
                                      onsubmit="return confirm('¿Eliminar el semestre <?= htmlspecialchars($s['id_semestre']) ?>? Esta acción es permanente.')">
                                    <input type="hidden" name="id_semestre" 
                                           value="<?= htmlspecialchars($s['id_semestre']) ?>">
                                    <button type="submit" class="btn-tabla btn-rojo" 
                                            title="<?= $tiene_grupos ? 'No se puede: tiene grupos' : 'Eliminar' ?>"
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