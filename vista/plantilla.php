<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#020617">
    <title>WorkShift - Campus & Remote</title>
    
    <!-- Soporte PWA -->
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/906/906334.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased flex flex-col justify-between selection:bg-indigo-500 selection:text-white">
    <!-- Luces decorativas fijas -->
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
        } elseif ($ruta === "ajustes") {
            include "vista/modulos/ajustes.php";
        } elseif ($ruta === "salir") {
            UsuariosControlador::ctrCerrarSesion();
        } else {
            include "vista/modulos/calendario.php";
        }
    } else {
        if ($ruta === "registro") {
            include "vista/modulos/registro.php";
        } elseif ($ruta === "recuperar") {
            include "vista/modulos/recuperar.php";
        } else {
            include "vista/modulos/login.php";
        }
    }

    include "vista/modulos/zpie.php";
    ?>
    <script>
        // Registrar Service Worker para PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').catch(err => console.log('SW registration error: ', err));
            });
        }
    </script>
</body>
</html>