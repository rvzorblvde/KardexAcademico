<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>

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
                    <a href="horarios.html" class="btn-nav">
                        <iconify-icon icon="heroicons:academic-cap-solid" class="nav-icon"></iconify-icon>
                        <span>Horarios</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="btn-nav">
                        <iconify-icon icon="heroicons:user-solid" class="nav-icon"></iconify-icon>
                        <span>Entrar</span>
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <main class="login-contenedor">
        <div class="login-card">
            <h2>Iniciar Sesión</h2>

            <div class="login-icono">
                <iconify-icon icon="heroicons:users-solid"></iconify-icon>
            </div>

            <form id="login-form" action="auth.php" method="POST">
                <div class="input-grupo">
                    <label for="username">Matricula:</label>
                    <div class="input-icon">
                        <iconify-icon icon="heroicons:user-solid"></iconify-icon>
                        <input type="text" name="usuario" id="username" placeholder="Ej. 332508" required maxlength="10">
                    </div>
                </div>

                <div class="input-grupo">
                    <label for="password">Contraseña:</label>
                    <div class="input-icon">
                        <iconify-icon icon="heroicons:lock-closed-solid"></iconify-icon>
                        <input type="password" name="contrasena" id="password" placeholder="Ingrese la contraseña" required>
                    </div>
                </div>

                <p id="mensaje-error" class="txt-error" style="display: none;"></p>

                <button type="submit" class="btn-login">
                    <span>Entrar</span>
                </button>
            </form>    
        </div>
    </main>
</body>

<script src="scripts/main.js"></script>

</html>