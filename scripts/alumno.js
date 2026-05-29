document.addEventListener('DOMContentLoaded', () => {
    inicializarTabs();
    inicializarFecha();        // ← nueva línea
});

function inicializarFecha() {
    const contenedor = document.getElementById('fecha-actual');
    if (!contenedor) return;

    const formatear = () => {
        const ahora = new Date();
        const opciones = {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        };
        contenedor.textContent = ahora.toLocaleString('es-MX', opciones).replace(',', '');
    };

    formatear();
    setInterval(formatear, 60000);  // actualiza cada minuto
}

document.addEventListener('DOMContentLoaded', () => {
    inicializarTabs();
    inicializarGrafica();
});

// pestañas
function inicializarTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.content-item').forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            const target = btn.dataset.target;
            if (target) {
                const seccion = document.getElementById(target);
                if (seccion) seccion.classList.add('active');
            }
        });
    });
}

// grafica
function inicializarGrafica() {
    const canvas = document.getElementById('chart_distribucion');
    if (!canvas) return;                       // no hay canvas en la página
    if (typeof Chart === 'undefined') {        // Chart.js no cargó
        console.error('Chart.js no está disponible');
        return;
    }
    if (!window.dataDist) {
        console.warn('No hay datos para la gráfica');
        return;
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: window.dataDist.labels,
            datasets: [{
                label: 'Calificaciones',
                data: window.dataDist.valores,
                backgroundColor: window.dataDist.labels.map((_, i) =>
                    i < 6 ? '#dc2626' : '#769127'
                ),
                borderColor: '#fff',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
}

// imprimir pdf
async function imprimirHorario() {
    if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
        alert('Las librerías de PDF no están disponibles.');
        return;
    }

    const { jsPDF } = window.jspdf;
    const elemento = document.getElementById('horario-imprimible');
    const datos = window.datosAlumno;

    if (!elemento || !datos) {
        alert('No hay horario para imprimir.');
        return;
    }

    try {
        const canvas = await html2canvas(elemento, {
            scale: 2,
            backgroundColor: '#ffffff',
            useCORS: true
        });

        const imgData = canvas.toDataURL('image/png');

        const pdf = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'letter'
        });

        // header
        pdf.setFontSize(18);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Horario de clases', 15, 18);

        pdf.setFontSize(11);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Alumno: ${datos.nombre}`, 15, 28);
        pdf.text(`Matrícula: a${datos.id}`, 15, 34);
        pdf.text(`Carrera: ${datos.carrera}`, 15, 40);
        pdf.text(`Semestre: ${datos.semestre}`, 15, 46);

        const ahora = new Date().toLocaleString('es-MX', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        });
        pdf.text(`Generado: ${ahora}`, 200, 28);

        // Imagen del horario
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth() - 30;
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

        pdf.addImage(imgData, 'PNG', 15, 55, pdfWidth, pdfHeight);

        // pie de pagina
        pdf.setFontSize(8);
        pdf.setTextColor(120);
        pdf.text('Kárdex Académico — Documento informativo', 15, pdf.internal.pageSize.getHeight() - 10);

        pdf.save(`horario_a${datos.id}_${datos.semestre}.pdf`);
    } catch (err) {
        console.error('Error al generar el PDF:', err);
        alert('Hubo un problema al generar el PDF. Revisa la consola.');
    }
}

// copiar matricula
window.copiarMatricula = function() {
    const texto = document.getElementById('matricula').textContent.trim();
    
    navigator.clipboard.writeText(texto).then(() => {
        const btn = document.querySelector('.btn-copy');
        if (!btn) return;
        
        const htmlOriginal = btn.innerHTML;
        btn.innerHTML = '<iconify-icon icon="heroicons:check-circle-solid"></iconify-icon>';
        btn.classList.add('btn-copy-success');
        
        setTimeout(() => {
            btn.innerHTML = htmlOriginal;
            btn.classList.remove('btn-copy-success');
        }, 2000);
    }).catch(err => {
        console.error('Error al copiar:', err);
        alert('No se pudo copiar al portapapeles.');
    });
};