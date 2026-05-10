<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios - Kárdex Académico</title>

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
                    <a href="#" class="btn-nav">
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

    <main class="horarios-contenedor">
        <div class="materias-card">
            <div class="tabla-materias">
                <div class="tabla-row tabla-header">
                    <div class="celda col-clave">Clave</div>
                    <div class="celda col-grupo">Grupo</div>
                    <div class="celda col-materia">Materia</div>
                    <div class="celda col-profesor">Profesor</div>
                    <div class="celda col-salon">Salón</div>
                    <div class="celda col-dia">L</div>
                    <div class="celda col-dia">M</div>
                    <div class="celda col-dia">M</div>
                    <div class="celda col-dia">J</div>
                    <div class="celda col-dia">V</div>
                    <div class="celda col-tipo">Tipo</div>
                    <div class="celda col-cupo">Cupo</div>
                </div>

                </div>
        </div>
    </main>
</body>
</html>