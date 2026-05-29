function configurarTabs() {
    const tabs = document.querySelectorAll('.tab-btn');
    
    tabs.forEach((btn, index) => {
        btn.addEventListener('click', () => {
            
            if (btn.classList.contains('btn-print')) {
                window.print();
                return;
            }

            tabs.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const targets = ['tab-calificaciones', 'tab-semestres', 'tab-estadisticas'];
            document.querySelectorAll('.content-item').forEach(item => item.classList.remove('active'));
            
            const activeTarget = targets[index];
            if(activeTarget && document.getElementById(activeTarget)) {
                document.getElementById(activeTarget).classList.add('active');
            }
        });
    });
}

function configurarLogin() {
    const loginForm = document.getElementById('login-form');
    const mensajeError = document.getElementById('mensaje-error');

    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            const usuarioInput = document.getElementById('username');
            const usuario = usuarioInput.value.trim().toLowerCase();
            const pass = document.getElementById('password').value;

            const regexValidacion = /^a\d{6}$|^p\d{5}$|^sysadmin\d{2}$/;

            mensajeError.style.display = 'none';
            mensajeError.innerText = "";

            if (!regexValidacion.test(usuario)) {
                e.preventDefault();
                mensajeError.innerText = "Usuario no reconocido. Verifica el formato (a000000, p00000 o sysadmin00).";
                mensajeError.style.display = 'block';
                usuarioInput.focus();
                return;
            }

            if (pass.trim() === "") {
                e.preventDefault();
                mensajeError.innerText = "La contraseña es obligatoria.";
                mensajeError.style.display = 'block';
                return;
            }
        });
    }
}
