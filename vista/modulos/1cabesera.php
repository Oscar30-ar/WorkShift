<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Page Title</title>
   
    <meta name='viewport' content='width=device-width, initial-scale=1'>
   
    <!-- Boostrap 5.3 -->

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- cdn jquery v 3.7.1 -->

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>


<!-- cdn data tablas .net -->

<link href="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.1.2/b-colvis-3.1.2/b-html5-3.1.2/b-print-3.1.2/r-3.0.3/datatables.min.css" rel="stylesheet">
 
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.1.2/b-colvis-3.1.2/b-html5-3.1.2/b-print-3.1.2/r-3.0.3/datatables.min.js"></script>

 <!-- cdn sweetalert2 -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 
<!-- Archivos personalizados -->

    <link rel='stylesheet' type='text/css' media='screen' href='vista/css/main.css'>
   
<header class="glass-panel sticky top-0 z-40 px-4 sm:px-8 py-3.5 flex items-center justify-between border-b border-white/5">
    <div class="flex items-center gap-3">
        <a href="index.php?ruta=calendario" class="flex items-center gap-2.5">
            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-400 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fa-solid fa-layer-group text-white text-sm"></i>
            </div>
            <div>
                <h1 class="font-bold text-white text-base leading-none">Work<span class="text-cyan-400">Shift</span></h1>
                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Campus & Remote</span>
            </div>
        </a>
    </div>

    <!-- Menú central de navegación -->
    <nav class="flex items-center gap-1 sm:gap-2">
        <a href="index.php?ruta=calendario" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= ($_GET['ruta'] ?? 'calendario') === 'calendario' ? 'bg-white/10 text-white' : 'text-slate-400 hover:text-white' ?>">
            <i class="fa-regular fa-calendar mr-1"></i> <span class="hidden sm:inline">Mi Calendario</span>
        </a>
        <a href="index.php?ruta=equipo" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= ($_GET['ruta'] ?? '') === 'equipo' ? 'bg-white/10 text-white' : 'text-slate-400 hover:text-white' ?>">
            <i class="fa-solid fa-users mr-1"></i> <span class="hidden sm:inline">Compañeros</span>
        </a>
        <a href="index.php?ruta=ajustes" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition <?= ($_GET['ruta'] ?? '') === 'ajustes' ? 'bg-white/10 text-white' : 'text-slate-400 hover:text-white' ?>">
            <i class="fa-solid fa-sliders mr-1"></i> <span class="hidden sm:inline">Ajustes</span>
        </a>
    </nav>

    <!-- Perfil y Salida -->
    <div class="flex items-center gap-2 sm:gap-3">
        <div class="hidden md:flex flex-col text-right">
            <span class="text-xs font-bold text-slate-200"><?= htmlspecialchars($_SESSION["nombre"]) ?></span>
            <span class="text-[11px] text-cyan-400">@<?= htmlspecialchars($_SESSION["username"]) ?></span>
        </div>
        <a href="index.php?ruta=salir" class="w-9 h-9 rounded-xl bg-rose-500/10 text-rose-300 border border-rose-500/20 hover:bg-rose-500/20 flex items-center justify-center transition text-xs" title="Cerrar Sesión">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</header>


</head>
<body>