<?php 
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../includes/connection.php';

// Determinar si estamos editando un alumno existente
$alumno_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM Alumno WHERE id_alumno = ?");
    $stmt->execute([$_GET['editar']]);
    $alumno_editar = $stmt->fetch();
}

// Listar todos los alumnos con su carrera (JOIN)
$alumnos = $pdo->query("
    SELECT a.id_alumno, a.Nombres, a.Apellido1, a.Apellido2, a.activo, c.Nombre AS carrera
    FROM Alumno a 
    INNER JOIN Carrera c ON a.id_carrera = c.id_carrera
    ORDER BY a.Apellido1, a.Apellido2
")->fetchAll();

// Catálogo de carreras para el select del form
$carreras = $pdo->query("SELECT id_carrera, Nombre FROM Carrera")->fetchAll();

// Capturar mensajes flash (vienen de las acciones)
$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Alumnos</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1><span class="txt-kardex">Gestor</span><span class="txt-academico">Alumnos</span></h1>
        </div>
        <nav><ul>
            <li><a href="../admin.php" class="btn-nav">
                <iconify-icon icon="heroicons:arrow-left-solid"></iconify-icon><span>Volver</span>
            </a></li>
        </ul></nav>
    </header>

    <main class="alumno-contenedor">
        <?php if ($msg): ?>
            <div class="alerta-flash"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- ============ FORMULARIO (crear o editar) ============ -->
        <section class="perfil-card">
            <h2><?= $alumno_editar ? 'Editar' : 'Nuevo' ?> alumno</h2>
            <form action="acciones/alumno_guardar.php" method="POST" class="form-admin">
                <?php if ($alumno_editar): ?>
                    <input type="hidden" name="id_alumno_original" value="<?= $alumno_editar['id_alumno'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="input-grupo">
                        <label>Matrícula:</label>
                        <input type="number" name="id_alumno" required maxlength="6"
                               value="<?= $alumno_editar['id_alumno'] ?? '' ?>">
                    </div>
                    <div class="input-grupo">
                        <label>Nombre(s):</label>
                        <input type="text" name="nombres" required
                               value="<?= htmlspecialchars($alumno_editar['Nombres'] ?? '') ?>">
                    </div>
                    <div class="input-grupo">
                        <label>Apellido paterno:</label>
                        <input type="text" name="apellido1" required
                               value="<?= htmlspecialchars($alumno_editar['Apellido1'] ?? '') ?>">
                    </div>
                    <div class="input-grupo">
                        <label>Apellido materno:</label>
                        <input type="text" name="apellido2"
                               value="<?= htmlspecialchars($alumno_editar['Apellido2'] ?? '') ?>">
                    </div>
                    <div class="input-grupo">
                        <label>Fecha de nacimiento:</label>
                        <input type="date" name="fecha_nacimiento" required
                               value="<?= $alumno_editar['fecha_nacimiento'] ?? '' ?>">
                    </div>
                    <div class="input-grupo">
                        <label>Carrera:</label>
                        <select name="id_carrera" required>
                            <?php foreach ($carreras as $c): ?>
                                <option value="<?= $c['id_carrera'] ?>"
                                    <?= ($alumno_editar['id_carrera'] ?? null) == $c['id_carrera'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['Nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-grupo">
                        <label>Contraseña <?= $alumno_editar ? '(dejar vacío para no cambiar)' : '' ?>:</label>
                        <input type="password" name="password" <?= $alumno_editar ? '' : 'required' ?>>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <?= $alumno_editar ? 'Actualizar' : 'Crear alumno' ?>
                </button>
                <?php if ($alumno_editar): ?>
                    <a href="alumnos.php" class="btn-cancelar">Cancelar</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- ============ LISTADO ============ -->
        <section class="tabla-scroll">
            <table class="tabla-kardex">
                <thead>
                    <tr>
                        <th>Matrícula</th><th>Nombre completo</th><th>Carrera</th>
                        <th>Estado</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($alumnos as $a): ?>
                    <tr>
                        <td><?= $a['id_alumno'] ?></td>
                        <td><?= htmlspecialchars("{$a['Nombres']} {$a['Apellido1']} {$a['Apellido2']}") ?></td>
                        <td><?= htmlspecialchars($a['carrera']) ?></td>
                        <td><?= $a['activo'] ? 'Activo' : 'Baja' ?></td>
                        <td>
                            <a href="?editar=<?= $a['id_alumno'] ?>" class="btn-tabla">
                                <iconify-icon icon="heroicons:pencil-square-solid"></iconify-icon>
                            </a>
                            <form action="acciones/alumno_eliminar.php" method="POST" style="display:inline"
                                  onsubmit="return confirm('¿Dar de baja a este alumno?')">
                                <input type="hidden" name="id_alumno" value="<?= $a['id_alumno'] ?>">
                                <button type="submit" class="btn-tabla btn-rojo">
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