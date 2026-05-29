document.addEventListener('DOMContentLoaded', () => {
    inicializarPreviewImagenes();
});

function inicializarPreviewImagenes() {
    const inputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    inputs.forEach(input => {
        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Validación rápida del lado cliente (segunda validación va en el servidor)
            const MAX_SIZE = 2 * 1024 * 1024;
            const TIPOS_OK = ['image/jpeg', 'image/png', 'image/webp'];

            if (file.size > MAX_SIZE) {
                alert('El archivo es muy grande. Máximo 2 MB.');
                input.value = '';
                return;
            }
            if (!TIPOS_OK.includes(file.type)) {
                alert('Tipo no permitido. Solo JPG, PNG y WebP.');
                input.value = '';
                return;
            }

            mostrarPreview(input, file);
        });
    });
}

function mostrarPreview(input, file) {
    // Buscar (o crear) el contenedor de preview justo antes del input
    let preview = input.parentElement.querySelector('.foto-preview-nueva');
    
    if (!preview) {
        preview = document.createElement('div');
        preview.className = 'foto-preview foto-preview-nueva';
        preview.innerHTML = `
            <img alt="Preview de la foto">
            <span>Nueva foto (sin guardar)</span>
        `;
        input.parentElement.insertBefore(preview, input);
    }

    const reader = new FileReader();
    reader.onload = (event) => {
        preview.querySelector('img').src = event.target.result;
    };
    reader.readAsDataURL(file);
}