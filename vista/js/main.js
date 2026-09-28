/* =========================================================================
   WORKSHIFT - SISTEMA DE CALENDARIO, CRONÓMETRO Y GEOFENCING MULTI-SEDE
========================================================================= */

// Fallbacks de color reactivos
var COLOR_CAMPUS = window.COLOR_CAMPUS || '#06b6d4';
var COLOR_CASA = window.COLOR_CASA || '#6366f1';

/* =========================================================================
   1. ESTILOS Y COLORES DINÁMICOS DEL CALENDARIO (Única implementación)
========================================================================= */
function aplicarEstiloEstado(boton, estado) {
    boton.dataset.estado = estado;
    boton.setAttribute('data-estado', estado);

    const esFestivo = boton.dataset.festivo === '1' || boton.getAttribute('data-festivo') === '1';
    const indicador = boton.querySelector('.indicador-estado');

    if (estado === 'campus') {
        boton.style.background = `${COLOR_CAMPUS}20`;
        boton.style.borderColor = `${COLOR_CAMPUS}90`;
        boton.style.boxShadow = `0 0 12px ${COLOR_CAMPUS}30`;
        if (indicador) {
            indicador.innerHTML = `<span class="w-2 h-2 rounded-full" style="background: ${COLOR_CAMPUS}"></span><span class="hidden sm:inline text-[10px] font-bold" style="color: ${COLOR_CAMPUS}">Campus</span>`;
        }
    } else if (estado === 'casa') {
        boton.style.background = `${COLOR_CASA}20`;
        boton.style.borderColor = `${COLOR_CASA}90`;
        boton.style.boxShadow = `0 0 12px ${COLOR_CASA}30`;
        if (indicador) {
            indicador.innerHTML = `<span class="w-2 h-2 rounded-full" style="background: ${COLOR_CASA}"></span><span class="hidden sm:inline text-[10px] font-bold" style="color: ${COLOR_CASA}">Casa</span>`;
        }
    } else {
        boton.style.boxShadow = 'none';
        if (esFestivo) {
            boton.style.background = 'rgba(245, 158, 11, 0.08)';
            boton.style.borderColor = 'rgba(245, 158, 11, 0.4)';
            if (indicador) {
                indicador.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-400"></span><span class="hidden sm:inline text-[10px] font-bold text-amber-300">Festivo</span>';
            }
        } else {
            boton.style.background = 'rgba(15, 23, 42, 0.5)';
            boton.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            if (indicador) {
                indicador.innerHTML = '<span class="hidden sm:inline text-[10px] text-slate-500 font-medium">Libre</span>';
            }
        }
    }

    // Gestionar visibilidad del botón de nota rápida
    const contenedor = boton.parentElement;
    const btnNota = contenedor ? contenedor.querySelector('.btn-abrir-nota') : null;
    if (btnNota) {
        if (estado === 'ninguno' && !esFestivo) {
            btnNota.classList.add('!hidden');
        } else {
            btnNota.classList.remove('!hidden');
        }
    }
}

/* =========================================================================
   2. CONTROL Y PERSISTENCIA DE TURNOS
========================================================================= */

async function cambiarModalidad(boton) {
    if (window.MODO_AUDITORIA) return;

    const fecha = boton.dataset.fecha;
    const estadoActual = boton.dataset.estado || 'ninguno';
    const esFestivo = boton.dataset.festivo === '1';

    let siguienteEstado;

    if (esFestivo) {
        // EN FESTIVOS: Exclusivamente entre Festivo, Campus y Casa
        if (estadoActual === 'festivo') {
            siguienteEstado = 'campus';
        } else if (estadoActual === 'campus') {
            siguienteEstado = 'casa';
        } else {
            // Si estaba en 'casa' o cualquier otro estado, regresa al estado natural del festivo
            siguienteEstado = 'festivo';
        }
    } else {
        // EN DÍAS NORMALES: Únicamente Libre, Campus y Casa (JAMÁS Festivo)
        if (estadoActual === 'ninguno') {
            siguienteEstado = 'campus';
        } else if (estadoActual === 'campus') {
            siguienteEstado = 'casa';
        } else {
            siguienteEstado = 'ninguno';
        }
    }

    // Actualización inmediata en pantalla
    aplicarEstiloEstado(boton, siguienteEstado);
    actualizarContadores();

    try {
        await fetch('index.php?action=guardar_turno', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha: fecha, modalidad: siguienteEstado })
        });
    } catch (error) {
        console.error('Error al sincronizar turno:', error);
    }
}
// 2. Estilos visuales para cada uno de los 3 estados
function aplicarEstiloEstado(boton, estado) {
    boton.dataset.estado = estado;
    const indicador = boton.querySelector('.indicador-estado');
    const colorCampus = window.COLOR_CAMPUS || '#06b6d4';
    const colorCasa = window.COLOR_CASA || '#6366f1';

    if (estado === 'campus') {
        boton.style.background = `${colorCampus}20`;
        boton.style.borderColor = `${colorCampus}90`;
        if (indicador) {
            indicador.innerHTML = `<span class="w-2 h-2 rounded-full" style="background: ${colorCampus}"></span><span class="hidden sm:inline text-[10px] font-bold" style="color: ${colorCampus}">Campus</span>`;
        }
    } else if (estado === 'casa') {
        boton.style.background = `${colorCasa}20`;
        boton.style.borderColor = `${colorCasa}90`;
        if (indicador) {
            indicador.innerHTML = `<span class="w-2 h-2 rounded-full" style="background: ${colorCasa}"></span><span class="hidden sm:inline text-[10px] font-bold" style="color: ${colorCasa}">Casa</span>`;
        }
    } else if (estado === 'festivo') {
        boton.style.background = 'rgba(245, 158, 11, 0.15)';
        boton.style.borderColor = 'rgba(245, 158, 11, 0.6)';
        if (indicador) {
            indicador.innerHTML = `<span class="w-2 h-2 rounded-full bg-amber-400"></span><span class="hidden sm:inline text-[10px] font-bold text-amber-300">Festivo</span>`;
        }
    } else {
        boton.style.background = 'rgba(15, 23, 42, 0.5)';
        boton.style.borderColor = 'rgba(255, 255, 255, 0.1)';
        if (indicador) {
            indicador.innerHTML = `<span class="hidden sm:inline text-[10px] text-slate-500 font-medium">Libre</span>`;
        }
    }
}

function actualizarContadores() {
    let campusCount = 0;
    let casaCount = 0;

    document.querySelectorAll('.dia-btn').forEach(btn => {
        const est = btn.dataset.estado;

        // Campus, Festivo, Incapacidad y Permiso alimentan el total de Campus
        if (est === 'campus' || est === 'festivo' || est === 'incapacidad' || est === 'permiso') {
            campusCount++;
        } else if (est === 'casa') {
            casaCount++;
        }
    });

    const statCampus = document.getElementById('stat-campus');
    const statCasa = document.getElementById('stat-casa');
    const statTotal = document.getElementById('stat-total');

    if (statCampus) statCampus.textContent = campusCount;
    if (statCasa) statCasa.textContent = casaCount;
    if (statTotal) statTotal.textContent = campusCount + casaCount;
}
/* =========================================================================
   3. NOTAS RÁPIDAS EN MODAL
========================================================================= */
let fechaNotaSeleccionada = null;

function abrirModalNota(fecha) {
    fechaNotaSeleccionada = fecha;
    const boton = document.querySelector(`.dia-btn[data-fecha="${fecha}"]`);
    const notaActual = boton ? (boton.dataset.nota || boton.getAttribute('data-nota') || '') : '';

    const fechaLabel = document.getElementById('fechaNotaModal');
    const textoArea = document.getElementById('textoNotaModal');
    const modal = document.getElementById('modalNotas');

    if (fechaLabel) fechaLabel.textContent = fecha;
    if (textoArea) textoArea.value = notaActual;
    if (modal) modal.classList.remove('hidden');
}

function cerrarModalNota() {
    const modal = document.getElementById('modalNotas');
    if (modal) modal.classList.add('hidden');
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
                boton.setAttribute('data-nota', texto);
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
        }
    } catch (e) {
        console.error('Error al guardar nota:', e);
    }
}

/* =========================================================================
   4. CRONÓMETRO DE 7 HORAS EN CAMPUS
========================================================================= */
function calcularHoraSalida() {
    const inputHora = document.getElementById('horaEntrada');
    if (!inputHora) return;

    const valor = inputHora.value;
    if (!valor) return;

    localStorage.setItem('workshift_hora_entrada', valor);

    const [horas, minutos] = valor.split(':').map(Number);
    const fechaBase = new Date();
    fechaBase.setHours(horas, minutos, 0, 0);

    const fechaSalida = new Date(fechaBase.getTime() + (7 * 60 * 60 * 1000));

    let horasSalida = fechaSalida.getHours();
    const minutosSalida = fechaSalida.getMinutes().toString().padStart(2, '0');
    const ampm = horasSalida >= 12 ? 'PM' : 'AM';
    horasSalida = horasSalida % 12 || 12;

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

/* =========================================================================
   5. SEDES AUTORIZADAS Y GEOFENCING AUTOMÁTICO
========================================================================= */
const SEDES_CAMPUS = [
    {
        nombre: "G3",
        lat: 4.683225913751331,
        lng: -74.11973090860381,
        radioMetros: 400
    }, {
        nombre: "BTS5",
        lat: 4.683818976968065,
        lng: -74.1185030079268,
        radioMetros: 400
    },
    {
        nombre: "BTS6",
        lat: 4.683925907027581,
        lng: -74.11871892573308,
        radioMetros: 400
    }
];

function calcularDistanciaMetros(lat1, lon1, lat2, lon2) {
    const R = 6371e3;
    const rad = Math.PI / 180;
    const dLat = (lat2 - lat1) * rad;
    const dLon = (lon2 - lon1) * rad;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * rad) * Math.cos(lat2 * rad) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

/* =========================================================================
   GEOFENCING CORPORATIVO: REGLA DE 7 HORAS Y DOBLE CHECK-IN OBLIGATORIO
========================================================================= */

function verificarPresenciaInteligente() {
    if (!("geolocation" in navigator) || window.MODO_AUDITORIA) return;

    const hoyStr = new Date().toISOString().split('T')[0];
    const btnHoy = document.querySelector(`.dia-btn[data-fecha="${hoyStr}"]`);
    const estadoActual = btnHoy ? (btnHoy.dataset.estado || btnHoy.getAttribute('data-estado')) : 'ninguno';

    // Si ya tiene Campus consolidado y validado, no realiza más peticiones
    if (estadoActual === 'campus' && localStorage.getItem(`workshift_consolidado_${hoyStr}`) === '1') {
        return;
    }

    navigator.geolocation.getCurrentPosition(async (pos) => {
        const uLat = pos.coords.latitude;
        const uLng = pos.coords.longitude;

        // 1. Verificar si está en alguna de las sedes (G3 o BTS5)
        let sedeDetectada = null;
        for (const sede of SEDES_CAMPUS) {
            const distancia = calcularDistanciaMetros(uLat, uLng, sede.lat, sede.lng);
            if (distancia <= sede.radioMetros) {
                sedeDetectada = sede.nombre;
                break;
            }
        }

        // 2. Verificar distancia a casa
        const casaLat = localStorage.getItem('workshift_casa_lat');
        const casaLng = localStorage.getItem('workshift_casa_lng');
        let distCasa = Infinity;
        if (casaLat && casaLng) {
            distCasa = calcularDistanciaMetros(uLat, uLng, parseFloat(casaLat), parseFloat(casaLng));
        }

        let tipoLugar = null;
        if (sedeDetectada) {
            tipoLugar = 'campus';
        } else if (distCasa <= 250) {
            tipoLugar = 'casa';
        }

        if (!tipoLugar) return; // Fuera de rango o en trayecto

        try {
            const formData = new FormData();
            formData.append('tipo_lugar', tipoLugar);
            formData.append('sede_nombre', sedeDetectada || '');

            const res = await fetch('index.php?action=guardar_presencia_geo', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                if (data.modalidad_asignada === 'campus') {
                    // Cumplió las 7h y 2 check-ins
                    localStorage.setItem(`workshift_consolidado_${hoyStr}`, '1');
                    if (btnHoy) {
                        aplicarEstiloEstado(btnHoy, 'campus');
                        actualizarContadores();
                    }
                    mostrarNotificacionJornada('¡Jornada de Campus completada (7h cumplidas)!');
                } else if (data.modalidad_asignada === 'en_progreso') {
                    // Primera entrada o tiempo insuficiente
                    const minLlevados = data.minutos_acumulados || 0;
                    const horas = Math.floor(minLlevados / 60);
                    const mins = minLlevados % 60;
                    mostrarNotificacionJornada(`Campus en curso (${horas}h ${mins}m). Vuelve a abrir la app al salir para completar las 7h.`);
                } else if (data.modalidad_asignada === 'casa') {
                    if (btnHoy && estadoActual !== 'campus') {
                        aplicarEstiloEstado(btnHoy, 'casa');
                        actualizarContadores();
                    }
                }
            }
        } catch (e) {
            console.error('Error al verificar presencia:', e);
        }
    }, (err) => {
        console.warn('[WorkShift Geo]', err.message);
    }, { enableHighAccuracy: true, timeout: 9000 });
}

function mostrarNotificacionJornada(mensaje) {
    let toast = document.getElementById('toastJornada');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toastJornada';
        toast.className = 'fixed bottom-4 right-4 z-50 glass-panel p-3.5 px-4 rounded-2xl border border-cyan-500/30 text-white text-xs shadow-2xl flex items-center gap-2 max-w-sm';
        document.body.appendChild(toast);
    }
    toast.innerHTML = `<i class="fa-solid fa-building-user text-cyan-400"></i> <span>${mensaje}</span>`;
    toast.style.display = 'flex';
    setTimeout(() => {
        if (toast) toast.style.display = 'none';
    }, 6000);
}

/* =========================================================================
   6. INICIALIZACIÓN GLOBAL
========================================================================= */
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar cronómetro
    const guardada = localStorage.getItem('workshift_hora_entrada') || "08:00";
    const input = document.getElementById('horaEntrada');
    if (input) {
        input.value = guardada;
        calcularHoraSalida();
        setInterval(calcularHoraSalida, 60000);
    }

    // Evaluar geolocalización al abrir la app
    verificarPresenciaInteligente();
});

/* =========================================================================
   GUÍA INTERACTIVA (ONBOARDING RÁPIDO PARA EL USUARIO)
========================================================================= */
let slideActualGuia = 1;
const totalSlidesGuia = 4;

function actualizarVistaGuia() {
    // Ocultar todos los slides
    for (let i = 1; i <= totalSlidesGuia; i++) {
        const el = document.getElementById(`guiaPaso${i}`);
        if (el) el.classList.add('hidden');
    }
    // Mostrar el actual
    const slideActivo = document.getElementById(`guiaPaso${slideActualGuia}`);
    if (slideActivo) slideActivo.classList.remove('hidden');

    // Actualizar los puntitos de progreso
    document.querySelectorAll('.step-dot').forEach(dot => {
        const step = parseInt(dot.getAttribute('data-step'));
        if (step === slideActualGuia) {
            dot.className = "h-1.5 w-6 rounded-full bg-cyan-400 transition-all step-dot";
        } else {
            dot.className = "h-1.5 w-2 rounded-full bg-slate-700 transition-all step-dot";
        }
    });

    // Control de botones
    const btnAtras = document.getElementById('btnAtrasGuia');
    const btnSig = document.getElementById('btnSiguienteGuia');

    if (btnAtras) {
        if (slideActualGuia === 1) {
            btnAtras.classList.add('invisible');
        } else {
            btnAtras.classList.remove('invisible');
        }
    }

    if (btnSig) {
        if (slideActualGuia === totalSlidesGuia) {
            btnSig.innerText = "¡Entendido!";
            btnSig.className = "px-5 py-2 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-bold text-xs transition shadow-md shadow-emerald-400/25";
        } else {
            btnSig.innerText = "Siguiente";
            btnSig.className = "px-5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-md shadow-cyan-500/25";
        }
    }
}

function cambiarSlideGuia(direccion) {
    slideActualGuia += direccion;
    if (slideActualGuia > totalSlidesGuia) {
        cerrarGuiaRapida();
        return;
    }
    if (slideActualGuia < 1) slideActualGuia = 1;
    actualizarVistaGuia();
}

function abrirGuiaRapida() {
    slideActualGuia = 1;
    actualizarVistaGuia();
    const modal = document.getElementById('modalGuiaRapida');
    if (modal) modal.classList.remove('hidden');
}

function cerrarGuiaRapida() {
    const modal = document.getElementById('modalGuiaRapida');
    if (modal) modal.classList.add('hidden');
    localStorage.setItem('workshift_onboarding_visto', '1');
}


let alarmaEjecutada = false;

// Solicitar permiso de notificaciones al interactuar con el temporizador
function solicitarPermisoNotificacion() {
    if ('Notification' in window && Notification.permission !== 'granted') {
        Notification.requestPermission();
    }
}

function dispararAlarmaSalida() {
    if (alarmaEjecutada) return;
    alarmaEjecutada = true;

    // 1. Notificación de sistema
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('¡Jornada de 7H Cumplida!', {
            body: 'Has cumplido tus 7 horas mínimas en Campus. Ya puedes registrar tu salida.',
            icon: 'vista/img/icono-192.png'
        });
    }

    // 2. Beep sonoro mediante Web Audio API (sin mp3 externo)
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.type = 'sine';
        osc.frequency.value = 880; // Tono A5
        gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.6);
    } catch(e) {
        console.log("Audio contextual no permitido aún.");
    }

    alert("⏰ ¡Atención! Has cumplido las 7 horas reglamentarias en Campus.");
}

// Auto-abrir la primera vez que inicia sesión
document.addEventListener('DOMContentLoaded', () => {
    const yaLoVio = localStorage.getItem('workshift_onboarding_visto');
    // Solo se auto-abre en la pantalla principal del calendario
    const esCalendario = window.location.href.includes('ruta=calendario') || !window.location.href.includes('ruta=');
    if (!yaLoVio && esCalendario) {
        setTimeout(abrirGuiaRapida, 800);
    }
});

function programarAlertaSalida7Horas(horaEntradaStr) {
    if (!("Notification" in window)) return;

    Notification.requestPermission().then(permission => {
        if (permission !== "granted") return;

        // Calcular milisegundos para 7 horas (420 minutos)
        const msSieteHoras = 7 * 60 * 60 * 1000;

        // Si la PWA tiene Service Worker activo, registrar la notificación
        if (navigator.serviceWorker && navigator.serviceWorker.controller) {
            navigator.serviceWorker.ready.then(registration => {
                setTimeout(() => {
                    registration.showNotification("WorkShift - Cumplimiento de Jornada", {
                        body: "¡Has completado tus 7 horas reglamentarias en Campus! Abre la app para consolidar tu turno.",
                        icon: "vista/img/icono-192.png",
                        badge: "vista/img/icono-192.png",
                        vibrate: [200, 100, 200],
                        tag: "alerta-7-horas",
                        renotify: true
                    });
                }, msSieteHoras);
            });
        }
    });
}