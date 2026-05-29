<?php
require_once 'includes/auth_admin.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Kárdex Académico</title>
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="icon" type="image/svg+xml" href="assets/icons/favicon.svg">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Panel</span>
                <span class="txt-academico">Administrador</span>
            </h1>
        </div>
        <nav>
            <ul>
                <li><a href="admin.php" class="btn-nav">
                    <iconify-icon icon="heroicons:home-solid"></iconify-icon><span>Inicio</span>
                </a></li>
                <li><a href="logout.php" class="btn-nav">
                    <iconify-icon icon="heroicons:arrow-right-on-rectangle-solid"></iconify-icon>
                    <span>Salir</span>
                </a></li>
            </ul>
        </nav>
    </header>

    <main class="alumno-contenedor">
        <section class="perfil-card">
            <div class="perfil-info">
                <div class="info-grupo">
                    <span class="label">Bienvenido:</span>
                    <span class="valor"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
                </div>
                <div class="info-grupo">
                    <span class="label">Rol:</span>
                    <span class="valor">Administrador del sistema</span>
                </div>
            </div>
        </section>

        <nav class="alumno-nav">
            <a href="admin/semestres.php" class="tab-btn">
                <iconify-icon icon="heroicons:calendar-days-solid"></iconify-icon> Semestres
            </a>
            <a href="admin/carreras.php" class="tab-btn">
                <iconify-icon icon="heroicons:building-library-solid"></iconify-icon> Carreras
            </a>
            <a href="admin/materias.php" class="tab-btn">
                <iconify-icon icon="heroicons:book-open-solid"></iconify-icon> Materias
            </a>
            <a href="admin/alumnos.php" class="tab-btn">
                <iconify-icon icon="heroicons:user-group-solid"></iconify-icon> Alumnos
            </a>
            <a href="admin/profesores.php" class="tab-btn">
                <iconify-icon icon="heroicons:academic-cap-solid"></iconify-icon> Profesores
            </a>
            <a href="admin/grupos.php" class="tab-btn">
                <iconify-icon icon="heroicons:rectangle-stack-solid"></iconify-icon> Grupos
            </a>
        </nav>
    </main>
</body>
</html>