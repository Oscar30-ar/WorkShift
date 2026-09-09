<?php
date_default_timezone_set('America/Bogota');
require_once "modelo/festivosHelper.php";

$colorCampus = $_SESSION["color_campus"] ?? '#06b6d4';
$colorCasa = $_SESSION["color_casa"] ?? '#6366f1';
$laboraFestivos = (int)($_SESSION["labora_festivos"] ?? 0);

$mesActual = isset($_GET["mes"]) ? (int)$_GET["mes"] : (int)date("n");
$anioActual = isset($_GET["anio"]) ? (int)$_GET["anio"] : (int)date("Y");

// Cargar festivos del año
$festivosDelAnio = FestivosHelper::obtenerFestivosAnio($anioActual);

$turnosBD = TurnoModelo::mdlObtenerTurnosMes($_SESSION["id"], $anioActual, $mesActual);
$mapaTurnos = [];
$mapaNotas = [];
$totalCampus = 0;
$totalCasa = 0;

foreach ($turnosBD as $t) {
    $mapaTurnos[$t["fecha"]] = $t["modalidad"];
    $mapaNotas[$t["fecha"]] = $t["nota"] ?? "";
}

$diasEnElMes = cal_days_in_month(CAL_GREGORIAN, $mesActual, $anioActual);
$primerDiaTimestamp = mktime(0, 0, 0, $mesActual, 1, $anioActual);
$diaInicioSemana = (int)date("N", $primerDiaTimestamp); // 1 = Lunes, 7 = Domingo

// Totalizador del mes (Regla: Festivo = Campus si no labora festivos; Fines de semana = Libre por defecto)
for ($dia = 1; $dia <= $diasEnElMes; $dia++) {
    $diaStr = str_pad($dia, 2, '0', STR_PAD_LEFT);
    $mesStr = str_pad($mesActual, 2, '0', STR_PAD_LEFT);
    $fechaComp = "$anioActual-$mesStr-$diaStr";

    $esFestivo = isset($festivosDelAnio[$fechaComp]);
    
    // Si el usuario ya marcó el día en BD, manda su decisión (excepción)
    if (isset($mapaTurnos[$fechaComp])) {
        $modalidad = $mapaTurnos[$fechaComp];
    } else {
        // Regla base: Festivo cuenta Campus si su área no labora festivos. Sáb/Dom quedan libres.
        $modalidad = ($esFestivo && !$laboraFestivos) ? 'campus' : 'ninguno';
    }

    if ($modalidad === 'campus') {
        $totalCampus++;
    } elseif ($modalidad === 'casa') {
        $totalCasa++;
    }
}

$nombresMeses = [1=>"Enero", 2=>"Febrero", 3=>"Marzo", 4=>"Abril", 5=>"Mayo", 6=>"Junio", 7=>"Julio", 8=>"Agosto", 9=>"Septiembre", 10=>"Octubre", 11=>"Noviembre", 12=>"Diciembre"];

$mesAnterior = ($mesActual == 1) ? 12 : $mesActual - 1;
$anioAnterior = ($mesActual == 1) ? $anioActual - 1 : $anioActual;
$mesSiguiente = ($mesActual == 12) ? 1 : $mesActual + 1;
$anioSiguiente = ($mesActual == 12) ? $anioActual + 1 : $anioActual;
?>

<script>
    window.COLOR_CAMPUS = "<?= $colorCampus ?>";
    window.COLOR_CASA = "<?= $colorCasa ?>";
</script>

<main class="flex-1 max-w-4xl mx-auto px-3 py-6 w-full space-y-5">

    <!-- AVISO GOOGLE: Si tiene contraseña temporal -->
    <?php if (!empty($_SESSION["pass_temporal"]) && $_SESSION["pass_temporal"] == 1): ?>
        <div class="glass-panel p-4 rounded-2xl border border-cyan-500/30 bg-cyan-950/20 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-sm">
                    <i class="fa-brands fa-google"></i>
                </div>
                <div>
                    <p class="font-bold text-white">Iniciaste sesión con Google</p>
                    <p class="text-slate-400 text-[11px]">Puedes seguir usando Google o crear una contraseña propia en ajustes.</p>
                </div>
            </div>
            <a href="index.php?ruta=ajustes" class="whitespace-nowrap px-3.5 py-1.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold transition">
                Crear mi contraseña
            </a>
        </div>
    <?php endif; ?>

    <!-- WIDGET: Control y Temporizador de 7 Horas Mínimas en Campus -->
    <div class="glass-panel p-4 sm:p-5 rounded-3xl border border-cyan-500/20 bg-gradient-to-r from-slate-900/90 via-slate-900/60 to-cyan-950/20 shadow-xl">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="h-11 w-11 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-lg shadow-sm">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        Control de 7 Horas en Campus
                        <span class="text-[10px] bg-cyan-500/20 text-cyan-300 px-2 py-0.5 rounded-full font-semibold">Regla Mínima</span>
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Ingresa tu hora de llegada para saber con exactitud a qué hora puedes salir.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-end">
                <div class="flex items-center gap-2 bg-slate-950/80 border border-white/10 rounded-2xl p-1.5 px-3">
                    <span class="text-[11px] text-slate-400 font-medium">Entrada:</span>
                    <input type="time" id="horaEntrada" value="08:00" onchange="calcularHoraSalida()" class="bg-transparent text-white font-mono font-bold text-xs focus:outline-none cursor-pointer">
                </div>

                <div class="flex items-center gap-2.5 bg-cyan-500/10 border border-cyan-500/30 rounded-2xl py-1.5 px-4">
                    <span class="text-[11px] text-cyan-300 font-medium">Salida cumplida:</span>
                    <span id="horaSalidaResultado" class="text-sm font-mono font-extrabold text-white">03:00 PM</span>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2 text-slate-300">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
                </span>
                <span class="text-[11px]">Llevas hoy: <strong id="tiempoTranscurrido" class="text-cyan-400 font-mono">0h 0m</strong></span>
                <span class="text-slate-600 text-xs">•</span>
                <span class="text-[11px]">Restan: <strong id="tiempoFaltante" class="text-indigo-400 font-mono">7h 0m</strong></span>
            </div>
            
            <div class="w-full sm:w-48 bg-slate-800 rounded-full h-2 overflow-hidden border border-white/5">
                <div id="barraProgresoHoras" class="bg-gradient-to-r from-cyan-400 to-indigo-500 h-full rounded-full transition-all duration-500" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- Contadores del Mes -->
    <div class="grid grid-cols-3 gap-2 sm:gap-4">
        <div class="glass-panel p-3.5 sm:p-4 rounded-2xl flex items-center justify-between">
            <div>
                <p class="text-[10px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Campus</p>
                <h3 id="stat-campus" class="text-xl sm:text-3xl font-extrabold mt-0.5" style="color: <?= $colorCampus ?>"><?= $totalCampus ?></h3>
            </div>
            <div class="h-9 w-9 sm:h-11 sm:w-11 rounded-xl flex items-center justify-center text-sm sm:text-lg" style="background-color: <?= $colorCampus ?>15; border: 1px solid <?= $colorCampus ?>30; color: <?= $colorCampus ?>;">
                <i class="fa-solid fa-building-user"></i>
            </div>
        </div>

        <div class="glass-panel p-3.5 sm:p-4 rounded-2xl flex items-center justify-between">
            <div>
                <p class="text-[10px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Casa</p>
                <h3 id="stat-casa" class="text-xl sm:text-3xl font-extrabold mt-0.5" style="color: <?= $colorCasa ?>"><?= $totalCasa ?></h3>
            </div>
            <div class="h-9 w-9 sm:h-11 sm:w-11 rounded-xl flex items-center justify-center text-sm sm:text-lg" style="background-color: <?= $colorCasa ?>15; border: 1px solid <?= $colorCasa ?>30; color: <?= $colorCasa ?>;">
                <i class="fa-solid fa-house-laptop"></i>
            </div>
        </div>

        <div class="glass-panel p-3.5 sm:p-4 rounded-2xl flex items-center justify-between">
            <div>
                <p class="text-[10px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Válidos</p>
                <h3 id="stat-total" class="text-xl sm:text-3xl font-extrabold text-emerald-400 mt-0.5"><?= $totalCampus + $totalCasa ?></h3>
            </div>
            <div class="h-9 w-9 sm:h-11 sm:w-11 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-sm sm:text-lg">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
        </div>
    </div>

    <!-- Calendario Principal -->
    <div class="glass-panel rounded-2xl sm:rounded-3xl p-3 sm:p-6 shadow-2xl">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-5">
            <form method="GET" action="index.php" class="flex items-center gap-2">
                <input type="hidden" name="ruta" value="calendario">
                <select name="mes" onchange="this.form.submit()" class="bg-slate-900 border border-white/10 text-white font-bold rounded-xl px-3 py-1.5 text-sm focus:outline-none focus:border-cyan-400 cursor-pointer">
                    <?php foreach ($nombresMeses as $num => $nombre): ?>
                        <option value="<?= $num ?>" <?= $num == $mesActual ? 'selected' : '' ?>><?= $nombre ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="anio" onchange="this.form.submit()" class="bg-slate-900 border border-white/10 text-slate-300 font-bold rounded-xl px-3 py-1.5 text-sm focus:outline-none focus:border-cyan-400 cursor-pointer">
                    <?php for ($a = 2024; $a <= 2028; $a++): ?>
                        <option value="<?= $a ?>" <?= $a == $anioActual ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
            </form>

            <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-white/10">
                <a href="index.php?ruta=calendario&mes=<?= $mesAnterior ?>&anio=<?= $anioAnterior ?>" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 text-slate-300 transition text-xs">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <a href="index.php?ruta=calendario&mes=<?= (int)date('n') ?>&anio=<?= (int)date('Y') ?>" class="px-2.5 h-8 flex items-center justify-center text-[11px] font-semibold rounded-lg hover:bg-white/10 text-slate-300 transition">
                    Hoy
                </a>
                <a href="index.php?ruta=calendario&mes=<?= $mesSiguiente ?>&anio=<?= $anioSiguiente ?>" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 text-slate-300 transition text-xs">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-2 text-center">
            <?php foreach (['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $d): ?>
                <span class="text-[11px] font-bold text-slate-400 uppercase py-1"><?= $d ?></span>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-7 gap-1 sm:gap-2">
            <?php
            for ($i = 1; $i < $diaInicioSemana; $i++) {
                echo '<div class="aspect-square rounded-xl bg-slate-900/10 border border-dashed border-white/5 pointer-events-none"></div>';
            }

            for ($dia = 1; $dia <= $diasEnElMes; $dia++):
                $diaStr = str_pad($dia, 2, '0', STR_PAD_LEFT);
                $mesStr = str_pad($mesActual, 2, '0', STR_PAD_LEFT);
                $fechaComp = "$anioActual-$mesStr-$diaStr";

                $tsDia = strtotime($fechaComp);
                $diaSemanaNum = (int)date('N', $tsDia);
                $esFinDeSemana = ($diaSemanaNum >= 6);
                $esFestivo = isset($festivosDelAnio[$fechaComp]);
                $nombreFestivo = $festivosDelAnio[$fechaComp] ?? "";

                // Prioridad: Registro explícito en BD > Festivo (si no labora festivos = campus) > Libre
                if (isset($mapaTurnos[$fechaComp])) {
                    $estado = $mapaTurnos[$fechaComp];
                } else {
                    $estado = ($esFestivo && !$laboraFestivos) ? 'campus' : 'ninguno';
                }

                $nota = $mapaNotas[$fechaComp] ?? '';

                $bgPersonalizado = 'background: rgba(15, 23, 42, 0.5);';
                $bordeEstilo = 'border border-white/10';
                $badgeContent = '<span class="hidden sm:inline text-[10px] text-slate-500 font-medium">Libre</span>';
                $textoDiaColor = $esFinDeSemana ? 'text-slate-400' : 'text-white';

                if ($esFestivo) {
                    $bordeEstilo = 'ring-2 ring-amber-400/50 shadow-lg shadow-amber-500/10';
                    $textoDiaColor = 'text-amber-300';
                }

                if ($estado === 'campus') {
                    $bgPersonalizado = "background: {$colorCampus}20; border-color: {$colorCampus}90;";
                    $badgeContent = "<span class='w-2 h-2 rounded-full' style='background: {$colorCampus}'></span><span class='hidden sm:inline text-[10px] font-bold' style='color: {$colorCampus}'>Campus</span>";
                } elseif ($estado === 'casa') {
                    $bgPersonalizado = "background: {$colorCasa}20; border-color: {$colorCasa}90;";
                    $badgeContent = "<span class='w-2 h-2 rounded-full' style='background: {$colorCasa}'></span><span class='hidden sm:inline text-[10px] font-bold' style='color: {$colorCasa}'>Casa</span>";
                } elseif ($esFestivo) {
                    $bgPersonalizado = "background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.4);";
                    $badgeContent = "<span class='w-2 h-2 rounded-full bg-amber-400'></span><span class='hidden sm:inline text-[10px] font-bold text-amber-300'>Festivo</span>";
                }
            ?>
                <div class="relative group aspect-square">
                    <button type="button"
                            data-fecha="<?= $fechaComp ?>"
                            data-estado="<?= $estado ?>"
                            data-festivo="<?= $esFestivo ? '1' : '0' ?>"
                            data-finde="<?= $esFinDeSemana ? '1' : '0' ?>"
                            data-nota="<?= htmlspecialchars($nota) ?>"
                            onclick="cambiarModalidad(this)"
                            style="<?= $bgPersonalizado ?>"
                            class="dia-btn w-full h-full rounded-2xl p-1.5 sm:p-2.5 flex flex-col justify-between items-center sm:items-start transition-all duration-150 active:scale-95 <?= $bordeEstilo ?>">
                        
                        <div class="flex items-center justify-between w-full">
                            <span class="text-xs sm:text-base font-extrabold <?= $textoDiaColor ?> select-none">
                                <?= $dia ?>
                            </span>

                            <div class="flex items-center gap-1">
                                <?php if ($esFestivo): ?>
                                    <span class="text-[10px] text-amber-400 bg-amber-500/10 px-1 rounded border border-amber-500/20" title="<?= htmlspecialchars($nombreFestivo) ?>">
                                        <i class="fa-solid fa-flag text-[9px]"></i>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="nota-icono text-[10px] text-cyan-400 <?= empty($nota) ? 'hidden' : '' ?>" title="Tiene nota">
                                    <i class="fa-solid fa-sticky-note"></i>
                                </span>
                            </div>
                        </div>

                        <div class="indicador-estado flex items-center gap-1">
                            <?= $badgeContent ?>
                        </div>
                    </button>

                    <!-- Botón para Editar Nota del Día -->
                    <button type="button" 
                            onclick="abrirModalNota('<?= $fechaComp ?>')"
                            class="btn-abrir-nota absolute top-1 right-1 h-5 w-5 rounded-md bg-slate-900/90 text-slate-300 hover:text-white border border-white/10 hidden group-hover:flex items-center justify-center text-[10px] transition <?= $estado === 'ninguno' && !$esFestivo ? '!hidden' : '' ?>" 
                            title="Editar Nota">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                </div>
            <?php endfor; ?>
        </div>

        <div class="mt-5 pt-4 border-t border-white/5 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400">
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background: <?= $colorCampus ?>"></span> Campus</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background: <?= $colorCasa ?>"></span> Casa</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Festivo</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-700"></span> Libre</span>
            </div>
            <span class="text-[11px] text-slate-500">Haz clic en cualquier día o festivo para rotar tu modalidad</span>
        </div>
    </div>
</main>

<!-- MODAL: Notas del Día -->
<div id="modalNotas" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-sm rounded-3xl p-6 border border-white/10 shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-white/5">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-regular fa-note-sticky text-amber-400"></i>
                <span>Nota del Día: <span id="fechaNotaModal" class="text-cyan-400 font-mono text-xs"></span></span>
            </h3>
            <button onclick="cerrarModalNota()" class="text-slate-400 hover:text-white text-xs"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <textarea id="textoNotaModal" rows="3" placeholder="Ej: Guardia, Sala 302, reunión con equipo de TI..." class="w-full bg-slate-900/80 border border-white/10 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-cyan-400 resize-none"></textarea>

        <div class="flex items-center justify-end gap-2 mt-4">
            <button type="button" onclick="cerrarModalNota()" class="px-3 py-1.5 rounded-xl border border-white/10 text-xs text-slate-400 hover:text-white transition">Cancelar</button>
            <button type="button" onclick="guardarNotaModal()" class="px-4 py-1.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-md shadow-cyan-500/20">Guardar Nota</button>
        </div>
    </div>
</div>