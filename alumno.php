<?php
session_start();

// Si no hay sesión, mandarlo de vuelta al login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Kárdex Académico</title>

    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <header>
        <div class="logo-contenedor">
            <div class="escudo-placeholder"></div>
            <h1>
                <span class="txt-kardex">Kardéx</span>
                <span class="txt-academico">Académico</span>
            </h1>
        </div>

        <nav>
            <ul>
                <li>
                    <a href="index.html" class="btn-nav">
                        <iconify-icon icon="heroicons:home-solid" class="nav-icon"></iconify-icon>
                        <span>Inicio</span>
                    </a>
                </li>
                <li>
                    <a href="horarios.php" class="btn-nav">
                        <iconify-icon icon="heroicons:academic-cap-solid" class="nav-icon"></iconify-icon>
                        <span>Horarios</span>
                    </a>
                </li>
                <li>
                    <a href="login.php" class="btn-nav">
                        <iconify-icon icon="heroicons:user-solid" class="nav-icon"></iconify-icon>
                        <span>Entrar</span>
                    </a>
                </li>
            </ul>

            
        </nav>
    </header>

<main class="alumno-contenedor">
    <section class="perfil-card">
        <div class="perfil-header">
            <div class="fecha-sistema" id="fecha-actual">
                2026-05-08 12:30
            </div>
            <div class="foto-alumno">
                <div class="foto-placeholder">
                    <iconify-icon icon="heroicons:user-circle-solid"></iconify-icon>
                </div>
            </div>
        </div>

        <div class="perfil-info">
            <div class="info-grupo">
                <span class="label">Nombre:</span>
                <span class="valor"><?php echo $_SESSION['nombre']; ?></span>
            </div>
            <div class="info-grupo">
                <span class="label">Matrícula:</span>
                <div class="matricula-copiar">
                    <?php echo $_SESSION['user_id']; ?>
                    <button class="btn-copy" onclick="copiarMatricula()">
                        <iconify-icon icon="heroicons:clipboard-document"></iconify-icon>
                    </button>
                </div>
            </div>
            <div class="info-grupo">
                <span class="label">Carrera:</span>
                <span class="valor">Ingeniería en Computación</span>
            </div>
            <div class="info-grupo">
                <span class="label">Correo:</span>
                <span class="valor">a284123@alumnos.uaslp.mx</span>
            </div>
        </div>
    </section>

    <nav class="alumno-nav">
        <button class="tab-btn active">Calificaciones</button>
        <button class="tab-btn">Semestres Anteriores</button>
        <button class="tab-btn">Estadísticas</button>
        <button class="tab-btn btn-print">
            <iconify-icon icon="heroicons:printer-solid"></iconify-icon> Imprimir Horario
        </button>
    </nav>

    <section class="tab-content">
        <div id="tab-calificaciones" class="content-item active">
            <div class="tabla-scroll">
                <table class="tabla-kardex">
                    </table>
            </div>
        </div>

        <div id="tab-semestres" class="content-item">
            <p>Aquí se mostrará el historial académico de años anteriores.</p>
        </div>

        <div id="tab-estadisticas" class="content-item">
            <p>Gráficas de promedio y avance de créditos.</p>
        </div>
    </section>
</main>
</body>

<script src="scripts/main.js"></script>

</html>