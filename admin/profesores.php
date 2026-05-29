<?php 
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/connection.php';

// Modo edición
$profesor_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM Profesor WHERE id_profesor = ?");
    $stmt->execute([(int) $_GET['editar']]);
    $profesor_editar = $stmt->fetch() ?: null;
}

// Listado completo 
$profesores = $pdo->query("
    SELECT id_profesor, Nombres, Apellido1, Apellido2, fecha_nacimiento, activo
    FROM Profesor
    ORDER BY Apellido1, Apellido2
")->fetchAll();

$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Profesores</title>
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
                <span class="txt-academico">Profesores</span>
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
            <h2><?= $profesor_editar ? 'Editar profesor' : 'Nuevo profesor' ?></h2>
            
            <form action="actions/profesor_guardar.php" method="POST" class="form-admin">
                <?php if ($profesor_editar): ?>
                    <input type="hidden" name="id_profesor_original" 
                           value="<?= $profesor_editar['id_profesor'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="input-grupo">
                        <label>ID del profesor:</label>
                        <input type="number" name="id_profesor" required min="10000" max="99999"
                               placeholder="Ej. 10003"
                               value="<?= $profesor_editar['id_profesor'] ?? '' ?>">
                    </div>
                    
                    <div class="input-grupo">
                        <label>Nombre(s):</label>
                        <input type="text" name="nombres" required
                               value="<?= htmlspecialchars($profesor_editar['Nombres'] ?? '') ?>">
                    </div>
                    
                    <div class="input-grupo">
                        <label>Apellido paterno:</label>
                        <input type="text" name="apellido1" required
                               value="<?= htmlspecialchars($profesor_editar['Apellido1'] ?? '') ?>">
                    </div>
                    
                    <div class="input-grupo">
                        <label>Apellido materno:</label>
                        <input type="text" name="apellido2"
                               value="<?= htmlspecialchars($profesor_editar['Apellido2'] ?? '') ?>">
                    </div>
                    
                    <div class="input-grupo">
                        <label>Fecha de nacimiento:</label>
                        <input type="date" name="fecha_nacimiento" required
                               value="<?= $profesor_editar['fecha_nacimiento'] ?? '' ?>">
                    </div>
                    
                    <div class="input-grupo">
                        <label>Contraseña <?= $profesor_editar ? '(dejar vacío para no cambiar)' : '' ?>:</label>
                        <input type="password" name="password" 
                               <?= $profesor_editar ? '' : 'required' ?>
                               placeholder="<?= $profesor_editar ? 'Sin cambios' : 'Mínimo 6 caracteres' ?>">
                    </div>
                </div>

                <div class="form-acciones">
                    <button type="submit" class="btn-login">
                        <?= $profesor_editar ? 'Actualizar' : 'Crear profesor' ?>
                    </button>
                    <?php if ($profesor_editar): ?>
                        <a href="profesores.php" class="btn-cancelar">Cancelar edición</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="tabla-scroll">
            <h2 style="margin-bottom: 15px;">Profesores registrados (<?= count($profesores) ?>)</h2>
            
            <?php if (count($profesores) === 0): ?>
                <p style="color: #999; padding: 20px; text-align: center;">
                    No hay profesores registrados todavía.
                </p>
            <?php else: ?>
                <table class="tabla-kardex">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre completo</th>
                            <th>Fecha nac.</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($profesores as $p): ?>
                        <tr class="<?= $p['activo'] ? '' : 'fila-inactiva' ?>">
                            <td>p<?= $p['id_profesor'] ?></td>
                            <td><?= htmlspecialchars("{$p['Nombres']} {$p['Apellido1']} {$p['Apellido2']}") ?></td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_nacimiento'])) ?></td>
                            <td>
                                <span class="estado-pill <?= $p['activo'] ? 'activo' : 'inactivo' ?>">
                                    <?= $p['activo'] ? 'Activo' : 'Baja' ?>
                                </span>
                            </td>
                            <td>
                                <a href="?editar=<?= $p['id_profesor'] ?>" class="btn-tabla" title="Editar">
                                    <iconify-icon icon="heroicons:pencil-square-solid"></iconify-icon>
                                </a>
                                
                                <?php if ($p['activo']): ?>
                                    <form action="actions/profesor_eliminar.php" method="POST" style="display:inline"
                                          onsubmit="return confirm('¿Dar de baja al profesor <?= htmlspecialchars($p['Nombres']) ?>?')">
                                        <input type="hidden" name="id_profesor" value="<?= $p['id_profesor'] ?>">
                                        <button type="submit" class="btn-tabla btn-rojo" title="Dar de baja">
                                            <iconify-icon icon="heroicons:user-minus-solid"></iconify-icon>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="actions/profesor_reactivar.php" method="POST" style="display:inline">
                                        <input type="hidden" name="id_profesor" value="<?= $p['id_profesor'] ?>">
                                        <button type="submit" class="btn-tabla btn-verde" title="Reactivar">
                                            <iconify-icon icon="heroicons:arrow-path-solid"></iconify-icon>
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