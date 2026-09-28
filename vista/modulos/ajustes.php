<?php
if (!isset($_SESSION["id"])) {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

$ajustes = new UsuariosControlador();
$ajustes->ctrActualizarAjustes();

$usuario = UsuarioModelo::mdlBuscarPorId($_SESSION["id"]);
$colorCampus = $usuario["color_campus"] ?? '#06b6d4';
$colorCasa = $usuario["color_casa"] ?? '#6366f1';
$perfilPublico = isset($usuario["perfil_publico"]) ? ((int)$usuario["perfil_publico"] === 1) : true;
$laboraFestivos = isset($usuario["labora_festivos"]) ? ((int)$usuario["labora_festivos"] === 1) : false;
$direccionCasa = $usuario["direccion_casa"] ?? '';

// Obtener todas las áreas disponibles
$db = Conexion::conectar();
$areas = $db->query("SELECT * FROM areas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="flex-1 max-w-3xl mx-auto px-4 py-8 w-full space-y-6">

    <!-- Toast / Notificación visual elegante de guardado (sin alert del navegador) -->
    <?php if (isset($_GET["msg"]) && $_GET["msg"] === "ok"): ?>
        <div id="toastSuccess" class="glass-panel p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center justify-between shadow-xl transition-all duration-300">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <p class="font-bold text-white">¡Cambios guardados con éxito!</p>
                    <p class="text-[11px] text-slate-400">Tus preferencias y colores se han actualizado correctamente.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('toastSuccess').remove()" class="text-slate-400 hover:text-white text-xs">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('toastSuccess');
                if (toast) {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="glass-panel rounded-3xl p-6 sm:p-8 shadow-2xl border border-white/10">

        <!-- Encabezado -->
        <div class="flex items-center justify-between pb-6 border-b border-white/10 mb-6">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 text-xl">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Configuración del Perfil</h2>
                    <p class="text-xs text-slate-400">Administra tus datos, departamento y colores de calendario</p>
                </div>
            </div>
            <?php if (!empty($usuario["cargo_nombre"])): ?>
                <span class="hidden sm:inline-block px-3 py-1 rounded-xl bg-white/5 border border-white/10 text-xs font-mono text-cyan-400">
                    <?= htmlspecialchars($usuario["cargo_nombre"]) ?> (Nivel <?= (int)$usuario["nivel_jerarquia"] ?>)
                </span>
            <?php endif; ?>
        </div>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="actualizarAjustes" value="1">

            <!-- 1. INFORMACIÓN PERSONAL Y DE EQUIPO -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                    <i class="fa-solid fa-user-gear text-cyan-400"></i> Información Laboral & Cuenta
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Nombre Completo -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nombre Completo</label>
                        <div class="relative">
                            <i class="fa-regular fa-user absolute left-4 top-3 text-slate-500 text-xs"></i>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario["nombre"]) ?>" required class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-400 transition">
                        </div>
                    </div>

                    <!-- @Username -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nombre de Usuario (@)</label>
                        <div class="relative">
                            <i class="fa-solid fa-at absolute left-4 top-3 text-slate-500 text-xs"></i>
                            <input type="text" name="username" value="<?= htmlspecialchars($usuario["username"]) ?>" required pattern="[a-zA-Z0-9_]+" class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-400 transition">
                        </div>
                    </div>

                    <!-- Correo Electrónico (Informativo / Bloqueado por seguridad) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Correo Corporativo</label>
                        <div class="relative">
                            <i class="fa-regular fa-envelope absolute left-4 top-3 text-slate-500 text-xs"></i>
                            <input type="email" value="<?= htmlspecialchars($usuario["email"]) ?>" disabled class="w-full bg-slate-900/30 border border-white/5 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-400 cursor-not-allowed">
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-white/10">

            <!-- 2. PERSONALIZACIÓN DE COLORES -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                    <i class="fa-solid fa-palette text-cyan-400"></i> Colores de Turno en Calendario
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Color Campus -->
                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-white/5 space-y-2">
                        <label class="block text-xs font-semibold text-slate-300">Días en Campus</label>
                        <div class="flex items-center gap-3">
                            <input type="color"
                                id="inputColorCampus"
                                name="color_campus"
                                value="<?= htmlspecialchars($colorCampus) ?>"
                                class="h-10 w-14 rounded-xl cursor-pointer bg-transparent border-0">
                            <span id="labelColorCampus" class="text-xs font-mono font-bold" style="color: <?= htmlspecialchars($colorCampus) ?>">
                                <?= htmlspecialchars($colorCampus) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Color Casa -->
                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-white/5 space-y-2">
                        <label class="block text-xs font-semibold text-slate-300">Días en Casa</label>
                        <div class="flex items-center gap-3">
                            <input type="color"
                                id="inputColorCasa"
                                name="color_casa"
                                value="<?= htmlspecialchars($colorCasa) ?>"
                                class="h-10 w-14 rounded-xl cursor-pointer bg-transparent border-0">
                            <span id="labelColorCasa" class="text-xs font-mono font-bold" style="color: <?= htmlspecialchars($colorCasa) ?>">
                                <?= htmlspecialchars($colorCasa) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-white/10">

            <!-- Librería de Mapas Leaflet.js (Gratuita y Open Source) -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

            <style>
                /* Asegurar que el mapa encaje en el tema oscuro */
                .leaflet-tile {
                    filter: brightness(0.7) invert(1) contrast(1.5) hue-rotate(200deg) saturate(0.3);
                }

                .leaflet-container {
                    background: #020617;
                    border-radius: 1rem;
                }

                .leaflet-popup-content-wrapper {
                    background: #0f172a;
                    color: #fff;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                }

                .leaflet-popup-tip {
                    background: #0f172a;
                }
            </style>

            <!-- SECCIÓN: UBICACIÓN DE CASA CON CONFIRMACIÓN EN MAPA -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <i class="fa-solid fa-house-laptop text-cyan-400"></i> Punto de Trabajo en Casa (Geofencing)
                    </h3>
                    <span class="text-[10px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full font-medium">
                        <i class="fa-solid fa-lock text-[9px]"></i> 100% Privado
                    </span>
                </div>

                <!-- Buscador de dirección -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Buscar Dirección</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <i class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-slate-500 text-xs"></i>
                            <input type="text"
                                id="inputBuscarDir"
                                autocomplete="off"
                                placeholder="Ej: Calle 64B # 24-40, Bogotá..."
                                class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400 transition">

                            <!-- Sugerencias en tiempo real -->
                            <ul id="listaDirecciones" class="hidden absolute left-0 right-0 top-full mt-1 bg-slate-900/95 backdrop-blur-md border border-white/15 rounded-xl shadow-2xl z-50 max-h-48 overflow-y-auto text-xs divide-y divide-white/5"></ul>
                        </div>

                        <button type="button"
                            id="btnGpsDirecto"
                            onclick="ubicarPorGpsActual()"
                            class="px-3.5 py-2.5 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-xs font-semibold flex items-center gap-1.5 transition shrink-0"
                            title="Ubicar donde me encuentro ahora">
                            <i class="fa-solid fa-crosshairs"></i>
                            <span class="hidden sm:inline">Mi GPS actual</span>
                        </button>
                    </div>
                </div>

                <!-- Contenedor del Mapa Interactivo -->
                <div class="relative rounded-2xl overflow-hidden border border-white/10 shadow-lg">
                    <div id="mapaCasa" class="w-full h-56 z-10"></div>
                    <div class="absolute bottom-2 left-2 z-20 bg-slate-950/80 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/10 text-[10px] text-slate-300">
                        <i class="fa-solid fa-hand-pointer text-cyan-400"></i> Puedes arrastrar el marcador al punto exacto
                    </div>
                </div>

                <!-- Panel de Confirmación -->
                <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-white/5 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs">
                        <p class="text-slate-400 text-[11px]">Ubicación seleccionada:</p>
                        <p id="lblDireccionConfirmada" class="font-bold text-white text-xs truncate max-w-xs">Sin ubicación fijada</p>
                    </div>

                    <button type="button"
                        id="btnConfirmarPunto"
                        onclick="confirmarUbicacionCasa()"
                        class="w-full sm:w-auto px-4 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-md shadow-cyan-500/20 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-check"></i> Confirmar como Mi Casa
                    </button>
                </div>

                <!-- Inputs ocultos para enviar con el formulario PHP -->
                <input type="hidden" name="direccion_casa" id="hiddenDireccion" value="<?= htmlspecialchars($direccionCasa ?? '') ?>">
            </div>

            <script>
                let mapa, marcador;
                let latActual = 4.609710;
                let lngActual = -74.081750;
                let direccionTemporal = "<?= htmlspecialchars($direccionCasa ?? '') ?>";

                // 1. Inicializar Mapa Leaflet
                function inicializarMapa() {
                    const latGuardada = localStorage.getItem('workshift_casa_lat');
                    const lngGuardada = localStorage.getItem('workshift_casa_lng');

                    if (latGuardada && lngGuardada) {
                        latActual = parseFloat(latGuardada);
                        lngActual = parseFloat(lngGuardada);
                    }

                    mapa = L.map('mapaCasa', {
                        zoomControl: false
                    }).setView([latActual, lngActual], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19
                    }).addTo(mapa);

                    // Marcador arrastrable (Draggable)
                    marcador = L.marker([latActual, lngActual], {
                        draggable: true
                    }).addTo(mapa);

                    // Si el usuario arrastra el pin manualmente
                    marcador.on('dragend', async function(e) {
                        const posicion = marcador.getLatLng();
                        latActual = posicion.lat;
                        lngActual = posicion.lng;
                        mapa.panTo([latActual, lngActual]);

                        // Geocodificación inversa: obtener texto a partir del punto
                        obtenerNombreDesdeCoords(latActual, lngActual);
                    });

                    if (direccionTemporal) {
                        document.getElementById('lblDireccionConfirmada').innerText = direccionTemporal;
                    }
                }

                // 2. Buscar dirección mientras escribe (Nominatim)
                let debounceTimer;
                const inputBusqueda = document.getElementById('inputBuscarDir');
                const listaResultados = document.getElementById('listaDirecciones');

                inputBusqueda.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    const q = inputBusqueda.value.trim();

                    if (q.length < 4) {
                        listaResultados.classList.add('hidden');
                        return;
                    }

                    debounceTimer = setTimeout(async () => {
                        try {
                            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&countrycodes=co&limit=4`);
                            const items = await res.json();

                            listaResultados.innerHTML = '';
                            if (items.length > 0) {
                                items.forEach(item => {
                                    const li = document.createElement('li');
                                    li.className = 'p-2.5 hover:bg-cyan-500/20 cursor-pointer text-slate-200 flex items-center gap-2 transition';
                                    li.innerHTML = `<i class="fa-solid fa-map-pin text-cyan-400"></i> <span class="truncate">${item.display_name}</span>`;
                                    li.onclick = () => {
                                        latActual = parseFloat(item.lat);
                                        lngActual = parseFloat(item.lon);
                                        direccionTemporal = item.display_name;

                                        // Mover el mapa y el pin
                                        mapa.setView([latActual, lngActual], 17);
                                        marcador.setLatLng([latActual, lngActual]);

                                        document.getElementById('lblDireccionConfirmada').innerText = direccionTemporal;
                                        inputBusqueda.value = item.display_name;
                                        listaResultados.classList.add('hidden');
                                    };
                                    listaResultados.appendChild(li);
                                });
                                listaResultados.classList.remove('hidden');
                            } else {
                                listaResultados.classList.add('hidden');
                            }
                        } catch (e) {
                            console.error("Error buscando dirección:", e);
                        }
                    }, 450);
                });

                // 3. Obtener ubicación con sensor GPS actual
                function ubicarPorGpsActual() {
                    if (!("geolocation" in navigator)) return;
                    const btn = document.getElementById('btnGpsDirecto');
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                    navigator.geolocation.getCurrentPosition(async (pos) => {
                        latActual = pos.coords.latitude;
                        lngActual = pos.coords.longitude;

                        mapa.setView([latActual, lngActual], 17);
                        marcador.setLatLng([latActual, lngActual]);

                        await obtenerNombreDesdeCoords(latActual, lngActual);

                        btn.innerHTML = '<i class="fa-solid fa-check text-emerald-400"></i>';
                        setTimeout(() => {
                            btn.innerHTML = '<i class="fa-solid fa-crosshairs"></i> <span class="hidden sm:inline">Mi GPS actual</span>';
                        }, 2000);
                    }, null, {
                        enableHighAccuracy: true
                    });
                }

                // 4. Geocodificación inversa (Coords a texto)
                async function obtenerNombreDesdeCoords(lat, lng) {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                        const data = await res.json();
                        if (data && data.display_name) {
                            direccionTemporal = data.display_name;
                            document.getElementById('lblDireccionConfirmada').innerText = direccionTemporal;
                            document.getElementById('inputBuscarDir').value = direccionTemporal;
                        }
                    } catch (e) {
                        direccionTemporal = `Coordenadas: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                        document.getElementById('lblDireccionConfirmada').innerText = direccionTemporal;
                    }
                }

                // 5. Confirmación por el usuario
                function confirmarUbicacionCasa() {
                    localStorage.setItem('workshift_casa_lat', latActual);
                    localStorage.setItem('workshift_casa_lng', lngActual);
                    document.getElementById('hiddenDireccion').value = direccionTemporal;

                    const btn = document.getElementById('btnConfirmarPunto');
                    const original = btn.innerHTML;
                    btn.className = "w-full sm:w-auto px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 font-bold text-xs flex items-center justify-center gap-1.5";
                    btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> ¡Ubicación Confirmada!';

                    setTimeout(() => {
                        btn.className = "w-full sm:w-auto px-4 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-md shadow-cyan-500/20 flex items-center justify-center gap-1.5";
                        btn.innerHTML = original;
                    }, 2500);
                }

                document.addEventListener('DOMContentLoaded', inicializarMapa);
            </script>

            <hr class="border-white/10">

            <!-- 4. OPERATIVIDAD Y PRIVACIDAD -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-cyan-400"></i> Parámetros y Privacidad
                </h3>

                <label class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/60 border border-white/5 cursor-pointer hover:bg-slate-900/80 transition">
                    <div>
                        <span class="text-xs font-semibold text-white block">Laborar Días Festivos</span>
                        <span class="text-[11px] text-slate-400 block">Si está inactivo, los festivos computan a Campus por política corporativa.</span>
                    </div>
                    <input type="checkbox" name="labora_festivos" value="1" <?= $laboraFestivos ? 'checked' : '' ?> class="rounded bg-slate-950 border-white/20 text-cyan-500 focus:ring-0 h-4 w-4 cursor-pointer">
                </label>

                <label class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/60 border border-white/5 cursor-pointer hover:bg-slate-900/80 transition">
                    <div>
                        <span class="text-xs font-semibold text-white block">Perfil Visible en el Directorio</span>
                        <span class="text-[11px] text-slate-400 block">Permite que compañeros de tu área puedan seguirte y ver tu turno diario.</span>
                    </div>
                    <input type="checkbox" name="perfil_publico" value="1" <?= $perfilPublico ? 'checked' : '' ?> class="rounded bg-slate-950 border-white/20 text-cyan-500 focus:ring-0 h-4 w-4 cursor-pointer">
                </label>
            </div>

            <hr class="border-white/10">

            <!-- 5. SEGURIDAD -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-slate-300">Cambiar Contraseña</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-4 top-3 text-slate-500 text-xs"></i>
                    <input type="password" name="nuevo_password" placeholder="Dejar en blanco para conservar la contraseña actual" class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400 transition">
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-cyan-500 hover:from-indigo-600 hover:to-cyan-600 text-white font-bold text-xs shadow-lg shadow-indigo-500/25 transition duration-150 active:scale-95">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    // Sincronización visual inmediata de los códigos hexadecimales
    document.getElementById('inputColorCampus').addEventListener('input', (e) => {
        const val = e.target.value.toUpperCase();
        const lbl = document.getElementById('labelColorCampus');
        lbl.innerText = val;
        lbl.style.color = val;
    });

    document.getElementById('inputColorCasa').addEventListener('input', (e) => {
        const val = e.target.value.toUpperCase();
        const lbl = document.getElementById('labelColorCasa');
        lbl.innerText = val;
        lbl.style.color = val;
    });

    // Guardado de GPS del hogar sin alertas invasivas
    function guardarMiUbicacionActual() {
        if (!("geolocation" in navigator)) {
            return;
        }
        navigator.geolocation.getCurrentPosition((pos) => {
            localStorage.setItem('workshift_casa_lat', pos.coords.latitude);
            localStorage.setItem('workshift_casa_lng', pos.coords.longitude);

            // Notificación en consola y feedback visual en el botón
            const btn = event.currentTarget;
            const textoOriginal = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check text-emerald-400"></i> <span class="hidden sm:inline text-emerald-400">¡GPS Guardado!</span>';
            setTimeout(() => {
                btn.innerHTML = textoOriginal;
            }, 2500);
        });
    }
</script>