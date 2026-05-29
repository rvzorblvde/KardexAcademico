document.querySelectorAll('.alerta-flash').forEach(alerta => {
    setTimeout(() => {
        alerta.style.transition = 'opacity 0.5s';
        alerta.style.opacity = '0';
        setTimeout(() => alerta.remove(), 500);
    }, 4000);
});