document.addEventListener('DOMContentLoaded', () => {
    console.log("Código JS cargado correctamente");
    
    actualizarFecha();
    configurarTabs();
    configurarLogin();
});

function actualizarFecha() {
    const fecha_contenedor = document.getElementById('fecha-actual');
    if(fecha_contenedor) {
        const ahora = new Date();
        const opciones = {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        };
        fecha_contenedor.innerText = ahora.toLocaleString('es-MX', opciones).replace(',', '');
    }
}

function copiarMatricula() {
    const matricula_texto = document.getElementById('matricula').innerText;

    navigator.clipboard.writeText(matricula_texto).then(() => {
        const btn = document.querySelector('.btn-copy');
        const html_original = btn.innerHTML;

        btn.innerHTML = '<iconify-icon icon="heroicons:check-circle-20-solid"></iconify-icon>';
        btn.style.color = "#4ade80";

        setTimeout(() => {
            btn.innerHTML = html_original;
            btn.style.color = "";
        }, 2000);
    }).catch(err => {
        console.error('Error al copiar: ', err);
    });
}

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
