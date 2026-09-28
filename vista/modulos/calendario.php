<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["id"])) {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

require_once "controlador/festivosHelper.php";

$miId = (int)$_SESSION["id"];
$miUsuario = UsuarioModelo::mdlBuscarPorId($miId);
$miNivel = (int)($miUsuario["nivel_jerarquia"] ?? 1);
$miAreaId = (int)($miUsuario["area_id"] ?? 1);

// 1. Detección de Supervisión / Modo Auditoría
$idObjetivo = isset($_GET["ver_usuario"]) ? (int)$_GET["ver_usuario"] : $miId;
$modoAuditoria = false;
$usuarioObjetivo = $miUsuario;

if ($idObjetivo !== $miId) {
    $candidato = UsuarioModelo::mdlBuscarPorId($idObjetivo);
    if ($candidato) {
        $suNivel = (int)($candidato["nivel_jerarquia"] ?? 1);
        $mismaArea = ((int)$candidato["area_id"] === $miAreaId);
        $esSuperAdmin = !empty($_SESSION["es_admin"]) && (int)$_SESSION["es_admin"] === 1;

        if ($esSuperAdmin || ($miNivel >= 3 && $miNivel > $suNivel && $mismaArea)) {
            $modoAuditoria = true;
            $usuarioObjetivo = $candidato;
        } else {
            echo '<script>alert("No tienes permisos jerárquicos para auditar este usuario."); window.location = "index.php?ruta=calendario";</script>';
            exit;
        }
    }
}

// 2. Parámetros del Usuario Seleccionado
$targetUserId = (int)$usuarioObjetivo["id"];
$colorCampus = !empty($usuarioObjetivo["color_campus"]) ? $usuarioObjetivo["color_campus"] : '#06b6d4';
$colorCasa = !empty($usuarioObjetivo["color_casa"]) ? $usuarioObjetivo["color_casa"] : '#6366f1';
$laboraFestivos = (int)($usuarioObjetivo["labora_festivos"] ?? 0);

// 3. Cálculos de Fechas del Calendario
$mesActual = isset($_GET["mes"]) ? (int)$_GET["mes"] : (int)date("n");
$anioActual = isset($_GET["anio"]) ? (int)$_GET["anio"] : (int)date("Y");

$festivosDelAnio = FestivosHelper::getFestivosColombia($anioActual);

$primerDiaMes = mktime(0, 0, 0, $mesActual, 1, $anioActual);
$diasEnElMes = (int)date("t", $primerDiaMes);
$diaInicioSemana = (int)date("N", $primerDiaMes); // 1 = Lunes, 7 = Domingo

$mesAnterior = $mesActual == 1 ? 12 : $mesActual - 1;
$anioAnterior = $mesActual == 1 ? $anioActual - 1 : $anioActual;
$mesSiguiente = $mesActual == 12 ? 1 : $mesActual + 1;
$anioSiguiente = $mesActual == 12 ? $anioActual + 1 : $anioActual;

$nombresMeses = [
    1 => "Enero",
    2 => "Febrero",
    3 => "Marzo",
    4 => "Abril",
    5 => "Mayo",
    6 => "Junio",
    7 => "Julio",
    8 => "Agosto",
    9 => "Septiembre",
    10 => "Octubre",
    11 => "Noviembre",
    12 => "Diciembre"
];

// 4. Cargar Turnos
$turnosDb = TurnoModelo::mdlObtenerTurnosMes($targetUserId, $anioActual, $mesActual);
$mapaTurnos = [];
$mapaNotas = [];
$totalCampus = 0;
$totalCasa = 0;

foreach ($turnosDb as $t) {
    $mapaTurnos[$t["fecha"]] = $t["modalidad"];
    if (!empty($t["nota"])) {
        $mapaNotas[$t["fecha"]] = $t["nota"];
    }
    if ($t["modalidad"] === "campus" || $t["modalidad"] === "festivo" || $t["modalidad"] === "incapacidad" || $t["modalidad"] === "permiso") {
        $totalCampus++;
    } elseif ($t["modalidad"] === "casa") {
        $totalCasa++;
    }
}

// 5. Consolidación de festivos automáticos que aún no tienen turno manual
if (!$laboraFestivos) {
    for ($d = 1; $d <= $diasEnElMes; $d++) {
        $fComp = sprintf("%04d-%02d-%02d", $anioActual, $mesActual, $d);
        if (isset($festivosDelAnio[$fComp]) && !isset($mapaTurnos[$fComp])) {
            $totalCampus++;
        }
    }
}

// Carga de solicitudes del usuario propio
$misSolicitudes = AdminModelo::mdlListarMisSolicitudes($miId);
$misPendientes = array_filter($misSolicitudes, fn($s) => $s["estado"] === "pendiente");
?>

<script>
    var COLOR_CAMPUS = "<?= $colorCampus ?>";
    var COLOR_CASA = "<?= $colorCasa ?>";
    var MODO_AUDITORIA = <?= $modoAuditoria ? 'true' : 'false' ?>;
    var LABORA_FESTIVOS = <?= $laboraFestivos ? 'true' : 'false' ?>;
</script>

<main class="flex-1 max-w-4xl mx-auto px-3 py-6 w-full space-y-5">

    <!-- MODO AUDITORÍA BANNER -->
    <?php if ($modoAuditoria): ?>
        <div class="glass-panel p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-amber-500/20 text-amber-300 flex items-center justify-center font-bold text-lg border border-amber-500/30">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-bold text-amber-300">Auditoría: <?= htmlspecialchars($usuarioObjetivo["nombre"]) ?></p>
                        <span class="text-[9px] bg-amber-500/20 text-amber-200 px-1.5 py-0.5 rounded font-mono">Solo lectura</span>
                    </div>
                    <p class="text-[11px] text-slate-400">@<?= htmlspecialchars($usuarioObjetivo["username"]) ?> &bull; Cargo: <?= htmlspecialchars($usuarioObjetivo["cargo_nombre"] ?? 'Analista') ?></p>
                </div>
            </div>
            <a href="index.php?ruta=calendario" class="px-3.5 py-1.5 rounded-xl bg-slate-900 border border-white/10 text-xs font-semibold text-slate-300 hover:text-white transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Volver a Mi Calendario
            </a>
        </div>
    <?php endif; ?>

    <!-- WIDGET: CONTROL DE 7 HORAS -->
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
                    <p class="text-[11px] text-slate-400 mt-0.5">Ingresa tu hora de llegada para saber a qué hora concluye tu jornada.</p>
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

    <!-- BOTÓN: MIS SOLICITUDES (SERVICENOW) -->
    <?php if (!$modoAuditoria): ?>
        <div class="flex justify-end">
            <button type="button" onclick="abrirModalMisSolicitudes()" class="px-3.5 py-1.5 rounded-xl bg-slate-900 border border-white/10 hover:border-cyan-500/30 text-xs font-semibold text-slate-300 hover:text-white transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-list-check text-cyan-400"></i>
                <span>Mis Solicitudes</span>
                <?php if (count($misPendientes) > 0): ?>
                    <span class="px-1.5 py-0.2 rounded-full bg-amber-500 text-slate-950 font-bold text-[10px]">
                        <?= count($misPendientes) ?>
                    </span>
                <?php endif; ?>
            </button>
        </div>
    <?php endif; ?>

    <!-- CONTADORES DEL MES -->
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

    <!-- CALENDARIO PRINCIPAL -->
    <div class="glass-panel rounded-2xl sm:rounded-3xl p-3 sm:p-6 shadow-2xl">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-5">
            <form method="GET" action="index.php" class="flex items-center gap-2">
                <input type="hidden" name="ruta" value="calendario">
                <?php if ($modoAuditoria): ?>
                    <input type="hidden" name="ver_usuario" value="<?= $targetUserId ?>">
                <?php endif; ?>

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

            <!-- Botón Descargar PDF Directo -->
            <button type="button"
                onclick="descargarReporteDirecto(<?= $targetUserId ?>, <?= $mesActual ?>, <?= $anioActual ?>, this)"
                class="px-3 py-1.5 rounded-xl bg-slate-900 border border-white/10 hover:border-cyan-500/30 text-xs font-semibold text-slate-300 hover:text-white transition flex items-center gap-1.5 shadow-sm"
                title="Descargar Certificado PDF">
                <i class="fa-solid fa-file-arrow-down text-rose-400 text-xs"></i>
                <span class="hidden sm:inline">Descargar PDF</span>
            </button>

            <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-white/10">
                <?php $paramUser = $modoAuditoria ? "&ver_usuario=$targetUserId" : ""; ?>
                <a href="index.php?ruta=calendario&mes=<?= $mesAnterior ?>&anio=<?= $anioAnterior ?><?= $paramUser ?>" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 text-slate-300 transition text-xs">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <a href="index.php?ruta=calendario&mes=<?= (int)date('n') ?>&anio=<?= (int)date('Y') ?><?= $paramUser ?>" class="px-2.5 h-8 flex items-center justify-center text-[11px] font-semibold rounded-lg hover:bg-white/10 text-slate-300 transition">
                    Hoy
                </a>
                <a href="index.php?ruta=calendario&mes=<?= $mesSiguiente ?>&anio=<?= $anioSiguiente ?><?= $paramUser ?>" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 text-slate-300 transition text-xs">
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

                if (isset($mapaTurnos[$fechaComp])) {
                    $estado = $mapaTurnos[$fechaComp];
                } else {
                    $estado = ($esFestivo && !$laboraFestivos) ? 'festivo' : 'ninguno';
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
                } elseif ($estado === 'festivo') {
                    $bgPersonalizado = "background: rgba(245, 158, 11, 0.12); border-color: rgba(245, 158, 11, 0.5);";
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
                        <?= $modoAuditoria ? 'disabled' : 'onclick="cambiarModalidad(this)"' ?>
                        style="<?= $bgPersonalizado ?>"
                        class="dia-btn w-full h-full rounded-2xl p-1.5 sm:p-2.5 flex flex-col justify-between items-center sm:items-start transition-all duration-150 <?= $modoAuditoria ? 'cursor-default opacity-90' : 'active:scale-95' ?> <?= $bordeEstilo ?>">

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

                    <?php if (!$modoAuditoria): ?>
                        <button type="button"
                            onclick="abrirModalNota('<?= $fechaComp ?>')"
                            class="btn-abrir-nota absolute top-1 right-1 h-5 w-5 rounded-md bg-slate-900/90 text-slate-300 hover:text-white border border-white/10 hidden group-hover:flex items-center justify-center text-[10px] transition"
                            title="Opciones del día">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                    <?php endif; ?>
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
            <span class="text-[11px] text-slate-500">
                <?= $modoAuditoria ? 'Visualizando calendario en modo de solo lectura' : 'Haz clic en un día normal o festivo para rotar tu modalidad' ?>
            </span>
        </div>
    </div>
</main>

<!-- 1. MODAL: NOTAS DEL DÍA Y ACCESO A TICKET SERVICENOW -->
<div id="modalNotas" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-sm rounded-3xl p-6 border border-white/10 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-white/5">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-regular fa-calendar-check text-cyan-400"></i>
                <span>Gestión del Día: <span id="fechaNotaModal" class="text-cyan-400 font-mono text-xs"></span></span>
            </h3>
            <button type="button" onclick="cerrarModalNota()" class="text-slate-400 hover:text-white text-xs"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Nota Interna:</label>
            <textarea id="textoNotaModal" rows="2" placeholder="Ej: Turno presencial, Sala 302..." class="w-full bg-slate-900/80 border border-white/10 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-cyan-400 resize-none"></textarea>
        </div>

        <div class="pt-2 border-t border-white/5 flex items-center justify-between gap-2">
            <button type="button" onclick="abrirModalSolicitudDesdeNota()" class="px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/20 text-xs font-semibold flex items-center gap-1.5 transition">
                <i class="fa-solid fa-headset"></i> Ticket ServiceNow
            </button>
            <button type="button" onclick="guardarNotaModal()" class="px-4 py-1.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-md shadow-cyan-500/20">Guardar Nota</button>
        </div>
    </div>
</div>

<!-- 2. MODAL: SOLICITUD DE NOVEDAD / SERVICENOW -->
<div id="modalSolicitudNovedad" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-md rounded-3xl p-6 border border-white/10 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-comments text-cyan-400"></i> Solicitar Convalidación / Falla GPS
            </h3>
            <button type="button" onclick="cerrarModalSolicitudNovedad()" class="text-slate-400 hover:text-white text-xs">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formSolicitudNovedad" class="space-y-4" novalidate>
            <input type="hidden" name="fecha" id="solicitudFechaTurno">

            <div class="bg-slate-900/60 p-3.5 rounded-2xl border border-white/5 text-xs text-slate-300">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Fecha a justificar:</span>
                    <strong id="solicitudFechaTexto" class="text-cyan-400 font-mono text-sm"></strong>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">El sistema asignará automáticamente un número de radicado oficial a tu solicitud.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Motivo / Justificación <span class="text-rose-400">*</span>
                </label>
                <textarea name="mensaje" rows="4" placeholder="Explica detalladamente el motivo de la convalidación o falla de GPS..." required
                    class="w-full bg-slate-900 border border-white/10 rounded-2xl p-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-400 resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-white/10">
                <button type="button" onclick="cerrarModalSolicitudNovedad()" class="px-4 py-2 rounded-xl border border-white/10 text-xs font-semibold text-slate-400 hover:text-white transition">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 transition">Enviar Solicitud</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. MODAL: MIS SOLICITUDES RADICADAS -->
<div id="modalMisSolicitudes" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-2xl rounded-3xl p-6 border border-white/10 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-cyan-400"></i> Mis Solicitudes de Convalidación
            </h3>
            <button type="button" onclick="cerrarModalMisSolicitudes()" class="text-slate-400 hover:text-white text-xs">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="max-h-80 overflow-y-auto space-y-2 pr-1">
            <?php if (empty($misSolicitudes)): ?>
                <div class="p-6 text-center text-slate-500 text-xs">
                    No has radicado solicitudes de convalidación este periodo.
                </div>
            <?php else: ?>
                <?php foreach ($misSolicitudes as $ms): ?>
                    <div class="bg-slate-900/70 border border-white/5 rounded-2xl p-3 text-xs space-y-1.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-white"><?= $ms["fecha_turno"] ?></span>
                                <span class="px-2 py-0.5 rounded-md bg-slate-800 text-cyan-400 font-mono text-[11px]">
                                    <?= htmlspecialchars($ms["ticket_soporte"]) ?>
                                </span>
                            </div>
                            <div>
                                <?php if ($ms["estado"] === "pendiente"): ?>
                                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/20 font-bold text-[10px]">
                                        <i class="fa-solid fa-clock mr-1"></i>En revisión
                                    </span>
                                <?php elseif ($ms["estado"] === "aprobado"): ?>
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold text-[10px]">
                                        <i class="fa-solid fa-check mr-1"></i>7H Aprobadas
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 font-bold text-[10px]">
                                        <i class="fa-solid fa-xmark mr-1"></i>Rechazado
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-slate-300 text-[11px]"><?= htmlspecialchars($ms["mensaje_usuario"]) ?></p>
                        <?php if (!empty($ms["respuesta_admin"])): ?>
                            <div class="bg-white/[0.02] border border-white/5 rounded-xl p-2 text-[11px] text-slate-400">
                                <strong class="text-cyan-400">Jefatura:</strong> <?= htmlspecialchars($ms["respuesta_admin"]) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="flex justify-end pt-2 border-t border-white/10">
            <button type="button" onclick="cerrarModalMisSolicitudes()" class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs transition">Cerrar</button>
        </div>
    </div>
</div>

<!-- Scripts de Interacción y Descarga Directa de PDF -->
<script src="vista/js/reporte_pdf.js?v=3.0"></script>
<script>
    let fechaActivaParaNovedad = '';

    function abrirModalNota(fecha) {
        fechaActivaParaNovedad = fecha;
        const labelFecha = document.getElementById('fechaNotaModal');
        if (labelFecha) labelFecha.innerText = fecha;

        const btn = document.querySelector(`.dia-btn[data-fecha="${fecha}"]`);
        const inputNota = document.getElementById('textoNotaModal');
        if (inputNota) inputNota.value = btn ? (btn.dataset.nota || '') : '';

        document.getElementById('modalNotas').classList.remove('hidden');
    }

    function cerrarModalNota() {
        document.getElementById('modalNotas').classList.add('hidden');
    }

    function abrirModalSolicitudDesdeNota() {
        if (!fechaActivaParaNovedad) {
            const fechaModal = document.getElementById('fechaNotaModal');
            fechaActivaParaNovedad = fechaModal ? fechaModal.innerText.trim() : '';
        }
        cerrarModalNota();

        const inputFecha = document.getElementById('solicitudFechaTurno');
        const txtFecha = document.getElementById('solicitudFechaTexto');

        if (inputFecha) inputFecha.value = fechaActivaParaNovedad;
        if (txtFecha) txtFecha.innerText = fechaActivaParaNovedad;

        document.getElementById('modalSolicitudNovedad').classList.remove('hidden');
    }

    function cerrarModalSolicitudNovedad() {
        document.getElementById('modalSolicitudNovedad').classList.add('hidden');
    }

    function abrirModalMisSolicitudes() {
        document.getElementById('modalMisSolicitudes').classList.remove('hidden');
    }

    function cerrarModalMisSolicitudes() {
        document.getElementById('modalMisSolicitudes').classList.add('hidden');
    }

    // ENVÍO DE SOLICITUD / RADICACIÓN ATÓMICA REQ
    document.getElementById('formSolicitudNovedad').addEventListener('submit', async (e) => {
        e.preventDefault();

        const form = e.target;
        let fecha = document.getElementById('solicitudFechaTurno').value;
        const mensajeInput = form.querySelector('textarea[name="mensaje"]');
        const mensaje = mensajeInput ? mensajeInput.value.trim() : '';

        // Si la fecha estuviera vacía, rescata la del texto visible
        if (!fecha) {
            fecha = document.getElementById('solicitudFechaTexto').innerText.trim();
            document.getElementById('solicitudFechaTurno').value = fecha;
        }

        if (!fecha) {
            Swal.fire({
                icon: 'warning',
                title: 'Fecha requerida',
                text: 'No se detectó una fecha válida. Por favor selecciona un día del calendario.',
                background: '#0f172a',
                color: '#f8fafc',
                confirmButtonColor: '#06b6d4'
            });
            return;
        }

        if (!mensaje) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo obligatorio',
                text: 'Por favor escribe el motivo o justificación de las 7 horas.',
                background: '#0f172a',
                color: '#f8fafc',
                confirmButtonColor: '#06b6d4'
            });
            return;
        }

        const btn = form.querySelector('button[type="submit"]');
        const txtOriginal = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Radicando...';
        btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('fecha', fecha);
            formData.append('mensaje', mensaje);

            const res = await fetch('index.php?action=usuario_enviar_solicitud', {
                method: 'POST',
                body: formData
            });

            const rawText = await res.text();
            let data;
            try {
                data = JSON.parse(rawText);
            } catch (errJson) {
                console.error("Respuesta no JSON recibida del servidor:", rawText);
                throw new Error("El servidor no devolvió una respuesta JSON válida. Revisa index.php.");
            }

            if (data.status === 'success') {
                cerrarModalSolicitudNovedad();
                form.reset();

                Swal.fire({
                    icon: 'success',
                    title: '¡Solicitud Radicada!',
                    html: `Requerimiento asignado:<br>
                           <strong class="text-cyan-400 font-mono text-xl block my-3 tracking-wider bg-slate-900/80 py-2 rounded-xl border border-cyan-500/30">${data.numero_req}</strong>
                           <span class="text-xs text-slate-400">Tu supervisor evaluará la justificación.</span>`,
                    background: '#0f172a',
                    color: '#f8fafc',
                    confirmButtonColor: '#06b6d4'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al radicar',
                    text: data.message || 'No se pudo procesar la solicitud.',
                    background: '#0f172a',
                    color: '#f8fafc',
                    confirmButtonColor: '#06b6d4'
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error de procesamiento',
                text: err.message || 'Ocurrió un error de conexión con el servidor.',
                background: '#0f172a',
                color: '#f8fafc'
            });
        } finally {
            btn.innerHTML = txtOriginal;
            btn.disabled = false;
        }
    });
</script>