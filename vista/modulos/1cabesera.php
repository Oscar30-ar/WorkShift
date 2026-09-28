<?php
$rutaActual = $_GET["ruta"] ?? "calendario";
$nombreUsuario = $_SESSION["nombre"] ?? "Usuario";
$userTag = $_SESSION["username"] ?? "user";
$esAdmin = !empty($_SESSION["es_admin"]) && (int)$_SESSION["es_admin"] === 1;

// Si existe el nombre del cargo en sesión, lo mostramos; si no, dejamos vacío
$cargoNombre = $_SESSION["cargo_nombre"] ?? "";
?>

<header class="w-full bg-slate-950/80 backdrop-blur-md border-b border-white/10 sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">

        <!-- Logo y Marca -->
        <a href="index.php?ruta=calendario" class="flex items-center gap-3 group">
            <img src="vista/img/logo.jpeg"
                alt="WorkShift Logo"
                class="h-11 w-11 rounded-2xl object-cover shadow-lg shadow-cyan-500/25 border border-white/10 group-hover:scale-105 transition-transform duration-200">
            <div>
                <div class="text-base font-black tracking-tight text-white leading-none">
                    Work<span class="text-cyan-400">Shift</span>
                </div>
                <div class="text-[9px] tracking-widest text-slate-400 font-bold uppercase mt-1">
                    Campus & Remote
                </div>
            </div>
        </a>

        <!-- Menú de Navegación Central -->
        <nav class="flex items-center gap-1 sm:gap-2">
            <!-- 1. Mi Calendario -->
            <a href="index.php?ruta=calendario"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition duration-150 <?= ($rutaActual === 'calendario' || $rutaActual === 'inicio') ? 'bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 shadow-sm shadow-cyan-500/10' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>"
                title="Mi Calendario">
                <i class="fa-solid fa-calendar-days text-xs"></i>
                <span class="hidden sm:inline">Mi Calendario</span>
            </a>

            <!-- 2. Compañeros / Red Social -->
            <a href="index.php?ruta=equipo"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition duration-150 <?= ($rutaActual === 'equipo') ? 'bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 shadow-sm shadow-cyan-500/10' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>"
                title="Directorio de Compañeros">
                <i class="fa-solid fa-users text-xs"></i>
                <span class="hidden sm:inline">Compañeros</span>
            </a>

            <!-- 3. MÓDULO EXCLUSIVO ADMIN (Solo visible si es_admin == 1) -->
            <?php if ($esAdmin): ?>
                <a href="index.php?ruta=admin"
                    class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition duration-150 <?= ($rutaActual === 'admin') ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 shadow-sm shadow-amber-500/10' : 'text-amber-400/80 hover:text-amber-300 hover:bg-amber-500/10' ?>"
                    title="Panel de Administración y Jerarquías">
                    <i class="fa-solid fa-shield-halved text-xs"></i>
                    <span class="hidden sm:inline">Admin</span>
                </a>
            <?php endif; ?>

            <!-- 4. Ajustes -->
            <a href="index.php?ruta=ajustes"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition duration-150 <?= ($rutaActual === 'ajustes') ? 'bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 shadow-sm shadow-cyan-500/10' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>"
                title="Ajustes de Perfil">
                <i class="fa-solid fa-sliders text-xs"></i>
                <span class="hidden sm:inline">Ajustes</span>
            </a>
        </nav>

        <!-- Perfil y Salir -->
        <div class="flex items-center gap-3 shrink-0">
            <!-- Información del Usuario -->
            <div class="hidden lg:flex flex-col text-right">
                <div class="flex items-center justify-end gap-1.5">
                    <span class="text-xs font-bold text-white truncate max-w-[140px]">
                        <?= htmlspecialchars($nombreUsuario) ?>
                    </span>
                    <?php if (!empty($cargoNombre)): ?>
                        <span class="text-[9px] px-1.5 py-0.2 rounded bg-white/5 text-slate-300 font-mono">
                            <?= htmlspecialchars($cargoNombre) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] text-cyan-400 font-mono">@<?= htmlspecialchars($userTag) ?></span>
            </div>

            <!-- Botón Cerrar Sesión -->
            <a href="index.php?ruta=salir"
                class="h-9 w-9 flex items-center justify-center rounded-xl border border-white/10 bg-slate-900/60 hover:bg-rose-500/20 hover:border-rose-500/30 text-slate-400 hover:text-rose-300 transition duration-150"
                title="Cerrar sesión">
                <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
            </a>
        </div>

    </div>
</header>