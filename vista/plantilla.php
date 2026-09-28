<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>WorkShift - Campus & Remote</title>
    <!-- SweetAlert2 Oficial -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Color de la barra de estado del sistema operativo -->
    <meta name="theme-color" content="#020617">

    <!-- Soporte Android / Chrome PWA -->
    <link rel="manifest" href="manifest.json">

    <!-- Favicon estándar para pestañas de navegador (PC y Móvil) -->
    <link rel="icon" type="image/png" sizes="192x192" href="vista/img/icono-192.png">
    <link rel="shortcut icon" href="vista/img/icono-192.png">

    <!-- Soporte Exclusivo iOS Safari (Pantalla de Inicio de iPhone/iPad) -->
    <link rel="apple-touch-icon" sizes="180x180" href="vista/img/icono-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="WorkShift">

    <!-- Tailwind CSS y Fuentes Corporativas -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>

    <?php if (isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] === "ok"): ?>
        <style>
            :root {
                --color-campus: <?= !empty($_SESSION["color_campus"]) ? htmlspecialchars($_SESSION["color_campus"]) : '#06b6d4' ?>;
                --color-casa: <?= !empty($_SESSION["color_casa"]) ? htmlspecialchars($_SESSION["color_casa"]) : '#6366f1' ?>;
            }

            .btn-turno-campus {
                background-color: var(--color-campus) !important;
                color: #020617 !important;
                box-shadow: 0 0 14px var(--color-campus) !important;
                border-color: transparent !important;
            }

            .btn-turno-casa {
                background-color: var(--color-casa) !important;
                color: #ffffff !important;
                box-shadow: 0 0 14px var(--color-casa) !important;
                border-color: transparent !important;
            }
        </style>
    <?php endif; ?>
</head>

<body class="min-h-screen bg-slate-950 text-slate-100 antialiased flex flex-col justify-between selection:bg-indigo-500 selection:text-white">
    <!-- Luces decorativas de fondo -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-40 left-1/4 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 -right-20 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
    </div>

    <?php
    $ruta = $_GET["ruta"] ?? "login";

    if (isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] === "ok") {
        include "vista/modulos/1cabesera.php";

        if ($ruta === "calendario" || $ruta === "inicio") {
            include "vista/modulos/calendario.php";
        } elseif ($ruta === "equipo") {
            include "vista/modulos/equipo.php";
        } elseif ($ruta === "admin") {
            if (!empty($_SESSION["es_admin"]) && (int)$_SESSION["es_admin"] === 1) {
                include "vista/modulos/admin.php";
            } else {
                include "vista/modulos/calendario.php";
            }
        } elseif ($ruta === "ajustes") {
            include "vista/modulos/ajustes.php";
        } elseif ($ruta === "salir") {
            UsuariosControlador::ctrCerrarSesion();
        } else {
            include "vista/modulos/calendario.php";
        }

        include "vista/modulos/zpie.php";
    } else {
        if ($ruta === "registro") {
            include "vista/modulos/registro.php";
        } elseif ($ruta === "recuperar") {
            include "vista/modulos/recuperar.php";
        } else {
            include "vista/modulos/login.php";
        }
    }
    ?>

    <?php if (isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] === "ok"): ?>
        <!-- BOTÓN FLOTANTE DISCRETO DE AYUDA RÁPIDA -->
        <button type="button"
            onclick="abrirGuiaRapida()"
            class="fixed bottom-20 right-4 z-40 h-10 w-10 rounded-full bg-slate-900/90 border border-cyan-500/30 text-cyan-400 hover:text-white hover:bg-cyan-500 flex items-center justify-center shadow-xl shadow-cyan-500/10 transition active:scale-95"
            title="¿Cómo funciona WorkShift?">
            <i class="fa-solid fa-question text-sm"></i>
        </button>

        <!-- MODAL INTERACTIVO DE GUÍA RÁPIDA -->
        <div id="modalGuiaRapida" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md transition-all">
            <div class="glass-panel w-full max-w-sm rounded-3xl p-6 border border-white/10 shadow-2xl relative overflow-hidden flex flex-col justify-between min-h-[420px]">

                <!-- Indicador de pasos -->
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <div class="flex gap-1.5" id="puntosPaso">
                        <span class="h-1.5 w-6 rounded-full bg-cyan-400 transition-all step-dot" data-step="1"></span>
                        <span class="h-1.5 w-2 rounded-full bg-slate-700 transition-all step-dot" data-step="2"></span>
                        <span class="h-1.5 w-2 rounded-full bg-slate-700 transition-all step-dot" data-step="3"></span>
                        <span class="h-1.5 w-2 rounded-full bg-slate-700 transition-all step-dot" data-step="4"></span>
                    </div>
                    <button type="button" onclick="cerrarGuiaRapida()" class="text-slate-400 hover:text-white text-xs">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Diapositivas -->
                <div class="my-auto py-4 text-center">
                    <div class="guia-slide space-y-3" id="guiaPaso1">
                        <div class="h-16 w-16 mx-auto rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-2xl shadow-lg">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>
                        <h4 class="text-base font-bold text-white">Regla de las 2 Aperturas</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Para que tu día cuente como <strong>Campus</strong>, abre la app al <strong>llegar</strong> y vuelve a abrirla al <strong>salir</strong> de la sede (G3, BTS5, BTS6).
                        </p>
                    </div>

                    <div class="guia-slide space-y-3 hidden" id="guiaPaso2">
                        <div class="h-16 w-16 mx-auto rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 text-2xl shadow-lg">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <h4 class="text-base font-bold text-white">Mínimo 7 Horas</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Entre tu primera y última apertura deben pasar al menos <strong>7 horas</strong>. Usa el cronómetro de la pantalla principal para ver tu hora de salida cumplida.
                        </p>
                    </div>

                    <div class="guia-slide space-y-3 hidden" id="guiaPaso3">
                        <div class="h-16 w-16 mx-auto rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-2xl shadow-lg">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <h4 class="text-base font-bold text-white">Salida Blindada</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Al cumplir tus 7 horas y salir después de las <strong>3:00 PM</strong>, tu Campus queda sellado. Al llegar a casa no se sobreescribirá tu turno presencial.
                        </p>
                    </div>

                    <div class="guia-slide space-y-3 hidden" id="guiaPaso4">
                        <div class="h-16 w-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-2xl shadow-lg">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <h4 class="text-base font-bold text-white">Tu Privacidad es Primero</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Configura tu ubicación en <em>Ajustes</em>. <strong>Nadie</strong> ve tu mapa ni tu dirección; tus compañeros y jefes solo ven si estás en Campus o Remoto.
                        </p>
                    </div>
                </div>

                <!-- Controles -->
                <div class="pt-3 border-t border-white/10 flex items-center justify-between">
                    <button type="button"
                        id="btnAtrasGuia"
                        onclick="cambiarSlideGuia(-1)"
                        class="text-xs text-slate-400 hover:text-white font-semibold transition invisible">
                        Atrás
                    </button>
                    <button type="button"
                        id="btnSiguienteGuia"
                        onclick="cambiarSlideGuia(1)"
                        class="px-5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-md shadow-cyan-500/25">
                        Siguiente
                    </button>
                </div>
            </div>
        </div>

        <!-- Script central de funcionalidades del cliente -->
        <script src="vista/js/main.js"></script>
    <?php endif; ?>

    <script>
        // Registro del Service Worker PWA
        if ('serviceWorker' in navigator && window.location.protocol.startsWith('http')) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('SW activo:', reg.scope))
                    .catch(err => console.debug('SW no disponible:', err.message));
            });
        }
    </script>
</body>

</html>