/* =========================================================================
   1. CONTROL DE TURNOS EN CALENDARIO (SIN RECARGA DE PÁGINA)
========================================================================= */
async function cambiarModalidad(boton) {
    const fecha = boton.dataset.fecha;
    const estadoActual = boton.dataset.estado;

    // Ciclo de estados
    const siguienteEstado = {
        'ninguno': 'campus',
        'campus': 'casa',
        'casa': 'ninguno'
    }[estadoActual];

    // Actualización visual reactiva instantánea
    aplicarEstiloEstado(boton, siguienteEstado);
    actualizarContadores();

    try {
        const respuesta = await fetch('index.php?action=guardar_turno', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha: fecha, modalidad: siguienteEstado })
        });

        const resultado = await respuesta.json();
        if (resultado.status !== 'success') {
            // Revertir en caso de error
            aplicarEstiloEstado(boton, estadoActual);
            actualizarContadores();
            alert('Error al guardar el turno.');
        } else {
            // Mostrar u ocultar el botón para agregar notas
            const contenedor = boton.parentElement;
            const btnNota = contenedor.querySelector('.btn-abrir-nota');
            const esFestivo = boton.dataset.festivo === '1';

            if (btnNota) {
                if (siguienteEstado === 'ninguno' && !esFestivo) {
                    btnNota.classList.add('!hidden');
                } else {
                    btnNota.classList.remove('!hidden');
                }
            }
        }
    } catch (error) {
        console.error(error);
        aplicarEstiloEstado(boton, estadoActual);
        actualizarContadores();
    }
}

function aplicarEstiloEstado(boton, estado) {
    boton.dataset.estado = estado;
    const colorCampus = window.COLOR_CAMPUS || '#06b6d4';
    const colorCasa = window.COLOR_CASA || '#6366f1';
    const esFestivo = boton.dataset.festivo === '1';
    const indicador = boton.querySelector('.indicador-estado');

    if (estado === 'campus') {
        boton.style.background = `${colorCampus}20`;
        boton.style.borderColor = `${colorCampus}90`;
        boton.style.boxShadow = `0 0 12px ${colorCampus}30`;
        indicador.innerHTML = `<span class="w-2 h-2 rounded-full" style="background: ${colorCampus}"></span><span class="hidden sm:inline text-[10px] font-bold" style="color: ${colorCampus}">Campus</span>`;
    } else if (estado === 'casa') {
        boton.style.background = `${colorCasa}20`;
        boton.style.borderColor = `${colorCasa}90`;
        boton.style.boxShadow = `0 0 12px ${colorCasa}30`;
        indicador.innerHTML = `<span class="w-2 h-2 rounded-full" style="background: ${colorCasa}"></span><span class="hidden sm:inline text-[10px] font-bold" style="color: ${colorCasa}">Casa</span>`;
    } else {
        if (esFestivo) {
            boton.style.background = 'rgba(245, 158, 11, 0.08)';
            boton.style.borderColor = 'rgba(245, 158, 11, 0.4)';
            boton.style.boxShadow = 'none';
            indicador.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-400"></span><span class="hidden sm:inline text-[10px] font-bold text-amber-300">Festivo</span>';
        } else {
            boton.style.background = 'rgba(15, 23, 42, 0.5)';
            boton.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            boton.style.boxShadow = 'none';
            indicador.innerHTML = '<span class="hidden sm:inline text-[10px] text-slate-500 font-medium">Libre</span>';
        }
    }
}

function actualizarContadores() {
    let campusCount = 0;
    let casaCount = 0;

    document.querySelectorAll('.dia-btn').forEach(btn => {
        const est = btn.dataset.estado;
        if (est === 'campus') campusCount++;
        if (est === 'casa') casaCount++;
    });

    const statCampus = document.getElementById('stat-campus');
    const statCasa = document.getElementById('stat-casa');
    const statTotal = document.getElementById('stat-total');

    if (statCampus) statCampus.textContent = campusCount;
    if (statCasa) statCasa.textContent = casaCount;
    if (statTotal) statTotal.textContent = campusCount + casaCount;
}

/* =========================================================================
   2. NOTAS RÁPIDAS EN MODAL (SIN RECARGA)
========================================================================= */
let fechaNotaSeleccionada = null;

function abrirModalNota(fecha) {
    fechaNotaSeleccionada = fecha;
    const boton = document.querySelector(`.dia-btn[data-fecha="${fecha}"]`);
    const notaActual = boton ? boton.dataset.nota : '';

    document.getElementById('fechaNotaModal').textContent = fecha;
    document.getElementById('textoNotaModal').value = notaActual || '';
    document.getElementById('modalNotas').classList.remove('hidden');
}

function cerrarModalNota() {
    document.getElementById('modalNotas').classList.add('hidden');
}

async function guardarNotaModal() {
    const texto = document.getElementById('textoNotaModal').value.trim();

    try {
        const res = await fetch('index.php?action=guardar_nota', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha: fechaNotaSeleccionada, nota: texto })
        });
        const data = await res.json();

        if (data.status === 'success') {
            const boton = document.querySelector(`.dia-btn[data-fecha="${fechaNotaSeleccionada}"]`);
            if (boton) {
                boton.dataset.nota = texto;
                const iconoNota = boton.querySelector('.nota-icono');
                if (iconoNota) {
                    if (texto.length > 0) {
                        iconoNota.classList.remove('hidden');
                    } else {
                        iconoNota.classList.add('hidden');
                    }
                }
            }
            cerrarModalNota();
        } else {
            alert('No se pudo guardar la nota.');
        }
    } catch (e) {
        console.error(e);
        alert('Error de conexión.');
    }
}

/* =========================================================================
   3. CALCULADOR Y CRONÓMETRO DE 7 HORAS DE CAMPUS
========================================================================= */
function calcularHoraSalida() {
    const inputHora = document.getElementById('horaEntrada');
    if (!inputHora) return;

    const valor = inputHora.value; // formato "HH:MM"
    if (!valor) return;

    localStorage.setItem('workshift_hora_entrada', valor);

    const [horas, minutos] = valor.split(':').map(Number);
    const fechaBase = new Date();
    fechaBase.setHours(horas, minutos, 0, 0);

    // Sumar 7 horas
    const fechaSalida = new Date(fechaBase.getTime() + (7 * 60 * 60 * 1000));

    let horasSalida = fechaSalida.getHours();
    const minutosSalida = fechaSalida.getMinutes().toString().padStart(2, '0');
    const ampm = horasSalida >= 12 ? 'PM' : 'AM';
    horasSalida = horasSalida % 12;
    horasSalida = horasSalida ? horasSalida : 12;

    const resTexto = `${horasSalida}:${minutosSalida} ${ampm}`;
    const elemResultado = document.getElementById('horaSalidaResultado');
    if (elemResultado) elemResultado.textContent = resTexto;

    actualizarProgresoJornada(fechaBase, fechaSalida);
}

function actualizarProgresoJornada(fechaEntrada, fechaSalida) {
    const ahora = new Date();
    const milisegundosTotales = 7 * 60 * 60 * 1000;
    const transcurrido = ahora - fechaEntrada;

    const elemTranscurrido = document.getElementById('tiempoTranscurrido');
    const elemFaltante = document.getElementById('tiempoFaltante');
    const barra = document.getElementById('barraProgresoHoras');

    if (transcurrido <= 0) {
        if (elemTranscurrido) elemTranscurrido.textContent = "0h 0m";
        if (elemFaltante) elemFaltante.textContent = "7h 0m";
        if (barra) barra.style.width = "0%";
        return;
    }

    const porcentaje = Math.min(100, Math.max(0, (transcurrido / milisegundosTotales) * 100));
    if (barra) barra.style.width = `${porcentaje}%`;

    const horasPasadas = Math.floor(transcurrido / (1000 * 60 * 60));
    const minutosPasados = Math.floor((transcurrido % (1000 * 60 * 60)) / (1000 * 60));
    if (elemTranscurrido) elemTranscurrido.textContent = `${horasPasadas}h ${minutosPasados}m`;

    const restante = Math.max(0, milisegundosTotales - transcurrido);
    const horasRestantes = Math.floor(restante / (1000 * 60 * 60));
    const minutosRestantes = Math.floor((restante % (1000 * 60 * 60)) / (1000 * 60));
    
    if (elemFaltante) {
        elemFaltante.textContent = restante === 0 ? "¡Cumplido!" : `${horasRestantes}h ${minutosRestantes}m`;
    }
}

// Inicialización de la calculadora
document.addEventListener('DOMContentLoaded', () => {
    const guardada = localStorage.getItem('workshift_hora_entrada') || "08:00";
    const input = document.getElementById('horaEntrada');
    if (input) {
        input.value = guardada;
        calcularHoraSalida();
        setInterval(calcularHoraSalida, 60000); // Actualiza cada minuto
    }
});