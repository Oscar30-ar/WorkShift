<?php
if (session_status() === PHP_SESSION_NONE) session_start();
AdminControlador::verificarAccesoAdmin();

// Carga de datos para el Dashboard
$metricas          = AdminModelo::mdlObtenerMetricas();
$usuarios          = AdminModelo::mdlListarUsuarios();
$cargos            = AdminModelo::mdlListarCargos();
$areas             = AdminModelo::mdlListarAreas();
$auditoria         = AdminModelo::mdlListarAuditoriaTurnos(150);
$festivosDecretos  = AdminModelo::mdlListarFestivosDecretos();
$solicitudes       = AdminModelo::mdlListarSolicitudesNovedades();
$conteoPendientes  = AdminModelo::mdlContarSolicitudesPendientes();

$mesActualNum  = (int)date('n');
$anioActualNum = (int)date('Y');
?>

<main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 py-6 w-full space-y-6">

    <!-- CABECERA PRINCIPAL -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-white/10 pb-5">
        <div>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-lg shadow-sm">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight">Consola de Super Administrador</h1>
                    <p class="text-xs text-slate-400">Control Institucional de Presencia Híbrida & Compliance</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button" 
                    onclick="descargarReporteDirecto(<?= (int)$_SESSION['id'] ?>, <?= $mesActualNum ?>, <?= $anioActualNum ?>, this)"
                    class="px-3.5 py-2 rounded-xl bg-slate-900 border border-white/10 hover:border-cyan-500/30 text-slate-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-sm">
                <i class="fa-solid fa-file-arrow-down text-rose-400"></i> Mi Reporte PDF
            </button>
            <a href="index.php?ruta=calendario" class="px-3.5 py-2 rounded-xl bg-slate-900 border border-white/10 text-slate-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 transition">
                <i class="fa-solid fa-calendar-days text-cyan-400"></i> Ir a Calendario
            </a>
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS (KPIS) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="glass-panel p-4 rounded-2xl border border-white/5 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Colaboradores</p>
                <h3 class="text-2xl font-black text-white mt-0.5"><?= (int)($metricas["usuarios"] ?? 0) ?></h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center text-base">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="glass-panel p-4 rounded-2xl border border-white/5 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">En Campus Hoy</p>
                <h3 class="text-2xl font-black text-cyan-400 mt-0.5"><?= (int)($metricas["campus_hoy"] ?? 0) ?></h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center text-base">
                <i class="fa-solid fa-building-user"></i>
            </div>
        </div>

        <div class="glass-panel p-4 rounded-2xl border border-white/5 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Remoto Hoy</p>
                <h3 class="text-2xl font-black text-indigo-400 mt-0.5"><?= (int)($metricas["casa_hoy"] ?? 0) ?></h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-base">
                <i class="fa-solid fa-house-laptop"></i>
            </div>
        </div>

        <div class="glass-panel p-4 rounded-2xl border border-white/5 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Departamentos</p>
                <h3 class="text-2xl font-black text-emerald-400 mt-0.5"><?= (int)($metricas["areas"] ?? 0) ?></h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-base">
                <i class="fa-solid fa-sitemap"></i>
            </div>
        </div>
    </div>

    <!-- PANEL PRINCIPAL DE GESTIÓN (TABS) -->
    <div class="glass-panel rounded-3xl p-4 sm:p-6 border border-white/5 shadow-2xl">
        
        <!-- NAVEGACIÓN ENTRE PESTAÑAS -->
        <div class="flex items-center gap-2 border-b border-white/10 pb-3 overflow-x-auto no-scrollbar text-xs">
            <button type="button" onclick="cambiarTabAdmin('usuarios')" id="tabBtn-usuarios" class="tab-btn px-4 py-2 rounded-xl font-bold text-cyan-400 bg-white/5 transition flex items-center gap-2 whitespace-nowrap">
                <i class="fa-solid fa-user-gear"></i> Colaboradores
            </button>
            <button type="button" onclick="cambiarTabAdmin('novedades')" id="tabBtn-novedades" class="tab-btn px-4 py-2 rounded-xl font-bold text-slate-400 hover:text-white transition flex items-center gap-2 whitespace-nowrap">
                <i class="fa-solid fa-inbox"></i> Solicitudes de Novedad
                <?php if ($conteoPendientes > 0): ?>
                    <span class="px-1.5 py-0.5 rounded-full bg-amber-500 text-slate-950 font-black text-[10px] animate-pulse">
                        <?= $conteoPendientes ?>
                    </span>
                <?php endif; ?>
            </button>
            <button type="button" onclick="cambiarTabAdmin('auditoria')" id="tabBtn-auditoria" class="tab-btn px-4 py-2 rounded-xl font-bold text-slate-400 hover:text-white transition flex items-center gap-2 whitespace-nowrap">
                <i class="fa-solid fa-clock-rotate-left"></i> Auditoría 7H
            </button>
            <button type="button" onclick="cambiarTabAdmin('festivos')" id="tabBtn-festivos" class="tab-btn px-4 py-2 rounded-xl font-bold text-slate-400 hover:text-white transition flex items-center gap-2 whitespace-nowrap">
                <i class="fa-solid fa-calendar-plus"></i> Festivos por Decreto
            </button>
            <button type="button" onclick="cambiarTabAdmin('cargos')" id="tabBtn-cargos" class="tab-btn px-4 py-2 rounded-xl font-bold text-slate-400 hover:text-white transition flex items-center gap-2 whitespace-nowrap">
                <i class="fa-solid fa-id-badge"></i> Cargos & Niveles
            </button>
            <button type="button" onclick="cambiarTabAdmin('areas')" id="tabBtn-areas" class="tab-btn px-4 py-2 rounded-xl font-bold text-slate-400 hover:text-white transition flex items-center gap-2 whitespace-nowrap">
                <i class="fa-solid fa-building"></i> Áreas
            </button>
        </div>

        <!-- 1. TAB: COLABORADORES -->
        <div id="tabContent-usuarios" class="tab-content pt-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="relative flex-1 max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                    <input type="text" id="busqueda-usuarios" onkeyup="filtrarTablaGenerico('usuarios')" placeholder="Buscar por nombre, usuario o área..." class="w-full bg-slate-900/80 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400">
                </div>
                <button type="button" onclick="abrirModalUsuario()" class="px-4 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-md shadow-cyan-500/20 flex items-center gap-1.5 whitespace-nowrap">
                    <i class="fa-solid fa-user-plus"></i> Nuevo Colaborador
                </button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-white/5">
                <table class="w-full text-left text-xs" id="tabla-usuarios">
                    <thead class="bg-slate-900/90 text-slate-400 font-semibold uppercase tracking-wider text-[10px] border-b border-white/5">
                        <tr>
                            <th class="p-3">Colaborador</th>
                            <th class="p-3">Departamento</th>
                            <th class="p-3">Cargo / Nivel</th>
                            <th class="p-3">Rol</th>
                            <th class="p-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <?php foreach ($usuarios as $u): ?>
                            <tr class="hover:bg-white/[0.02] fila-item">
                                <td class="p-3">
                                    <div class="font-bold text-white"><?= htmlspecialchars($u["nombre"]) ?></div>
                                    <div class="text-[10px] text-slate-500">@<?= htmlspecialchars($u["username"]) ?> &bull; <?= htmlspecialchars($u["email"] ?? '') ?></div>
                                </td>
                                <td class="p-3 text-slate-300"><?= htmlspecialchars($u["area_nombre"] ?? 'Sin Área') ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-800 border border-white/5 text-[11px] text-cyan-400 font-bold">
                                        <?= htmlspecialchars($u["cargo_nombre"] ?? 'Analista') ?> (N<?= (int)($u["nivel_jerarquia"] ?? 1) ?>)
                                    </span>
                                </td>
                                <td class="p-3">
                                    <?= (!empty($u["es_admin"]) && (int)$u["es_admin"] === 1) 
                                        ? '<span class="text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 px-2 py-0.5 rounded-full font-bold">Admin</span>' 
                                        : '<span class="text-[10px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded-full">Usuario</span>' ?>
                                </td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" 
                                                onclick="descargarReporteDirecto(<?= (int)$u['id'] ?>, <?= $mesActualNum ?>, <?= $anioActualNum ?>, this)"
                                                class="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs transition" 
                                                title="Descargar Certificado PDF">
                                            <i class="fa-solid fa-file-arrow-down"></i>
                                        </button>
                                        <a href="index.php?ruta=calendario&ver_usuario=<?= (int)$u['id'] ?>" class="p-2 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/20 text-xs transition" title="Auditar Calendario">
                                            <i class="fa-solid fa-calendar-check"></i>
                                        </a>
                                        <button type="button" 
                                                onclick="editarUsuario(<?= htmlspecialchars(json_encode($u)) ?>)" 
                                                class="p-2 rounded-lg bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-400 border border-cyan-500/20 text-xs transition" 
                                                title="Editar Perfil">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <?php if ((int)$u["id"] !== (int)$_SESSION["id"]): ?>
                                            <button type="button" 
                                                    onclick="eliminarUsuario(<?= (int)$u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre'])) ?>')" 
                                                    class="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs transition" 
                                                    title="Eliminar Colaborador">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between pt-3 text-xs text-slate-400" id="paginacion-usuarios"></div>
        </div>

        <!-- 2. TAB: SOLICITUDES DE NOVEDADES (SERVICENOW) -->
        <div id="tabContent-novedades" class="tab-content hidden pt-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="relative flex-1 max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                    <input type="text" id="busqueda-novedades" onkeyup="filtrarTablaGenerico('novedades')" placeholder="Buscar por ticket, usuario o fecha..." class="w-full bg-slate-900/80 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400">
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-white/5">
                <table class="w-full text-left text-xs" id="tabla-novedades">
                    <thead class="bg-slate-900/90 text-slate-400 font-semibold uppercase tracking-wider text-[10px] border-b border-white/5">
                        <tr>
                            <th class="p-3">Fecha Turno</th>
                            <th class="p-3">Colaborador</th>
                            <th class="p-3">Ticket / Radicado</th>
                            <th class="p-3">Justificación / Motivo</th>
                            <th class="p-3">Estado</th>
                            <th class="p-3 text-right">Decisión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <?php if (empty($solicitudes)): ?>
                            <tr class="fila-item">
                                <td colspan="6" class="p-6 text-center text-slate-500">
                                    No se registran solicitudes de convalidación ni tickets pendientes.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($solicitudes as $s): ?>
                                <tr class="hover:bg-white/[0.02] fila-item">
                                    <td class="p-3 font-mono font-bold text-cyan-400 whitespace-nowrap">
                                        <i class="fa-regular fa-calendar mr-1 text-slate-500"></i><?= $s["fecha_turno"] ?>
                                    </td>
                                    <td class="p-3 whitespace-nowrap">
                                        <div class="font-bold text-white"><?= htmlspecialchars($s["nombre"]) ?></div>
                                        <div class="text-[10px] text-slate-500">@<?= htmlspecialchars($s["username"]) ?> &bull; <?= htmlspecialchars($s["area_nombre"]) ?></div>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-800 border border-white/10 font-mono text-xs text-amber-300 font-bold">
                                            <?= htmlspecialchars($s["ticket_soporte"]) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-300 text-[11px] max-w-xs">
                                        <p class="line-clamp-2"><?= htmlspecialchars($s["mensaje_usuario"]) ?></p>
                                        <?php if (!empty($s["respuesta_admin"])): ?>
                                            <p class="text-[10px] text-slate-500 mt-1 italic">
                                                <i class="fa-solid fa-reply mr-1"></i><?= htmlspecialchars($s["respuesta_admin"]) ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 whitespace-nowrap">
                                        <?php if ($s["estado"] === "pendiente"): ?>
                                            <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/20 text-[10px] font-bold">
                                                <i class="fa-solid fa-clock mr-1"></i>Pendiente
                                            </span>
                                        <?php elseif ($s["estado"] === "aprobado"): ?>
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">
                                                <i class="fa-solid fa-circle-check mr-1"></i>7H Aprobadas
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px] font-bold">
                                                <i class="fa-solid fa-circle-xmark mr-1"></i>Rechazado
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-right whitespace-nowrap">
                                        <?php if ($s["estado"] === "pendiente"): ?>
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" 
                                                        onclick="abrirModalDecision(<?= (int)$s['id'] ?>, 'aprobado', '<?= htmlspecialchars(addslashes($s['nombre'])) ?>', '<?= $s['fecha_turno'] ?>', '<?= htmlspecialchars(addslashes($s['ticket_soporte'])) ?>')" 
                                                        class="px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/20 text-xs font-semibold transition"
                                                        title="Aprobar 7 Horas">
                                                    <i class="fa-solid fa-check mr-1"></i> Aprobar
                                                </button>
                                                <button type="button" 
                                                        onclick="abrirModalDecision(<?= (int)$s['id'] ?>, 'rechazado', '<?= htmlspecialchars(addslashes($s['nombre'])) ?>', '<?= $s['fecha_turno'] ?>', '<?= htmlspecialchars(addslashes($s['ticket_soporte'])) ?>')" 
                                                        class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/20 text-xs font-semibold transition"
                                                        title="Rechazar Novedad">
                                                    <i class="fa-solid fa-xmark mr-1"></i> Rechazar
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-[10px] text-slate-500">
                                                Revisado por <?= htmlspecialchars($s["supervisor_nombre"] ?? 'Jefatura') ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between pt-3 text-xs text-slate-400" id="paginacion-novedades"></div>
        </div>

        <!-- 3. TAB: AUDITORÍA 7 HORAS -->
        <div id="tabContent-auditoria" class="tab-content hidden pt-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="relative flex-1 max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                    <input type="text" id="busqueda-auditoria" onkeyup="filtrarTablaGenerico('auditoria')" placeholder="Filtrar por fecha, usuario o ticket..." class="w-full bg-slate-900/80 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400">
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-white/5">
                <table class="w-full text-left text-xs" id="tabla-auditoria">
                    <thead class="bg-slate-900/90 text-slate-400 font-semibold uppercase tracking-wider text-[10px] border-b border-white/5">
                        <tr>
                            <th class="p-3">Fecha</th>
                            <th class="p-3">Colaborador</th>
                            <th class="p-3">Modalidad</th>
                            <th class="p-3">Marcación / Permanencia</th>
                            <th class="p-3">Estado 7H</th>
                            <th class="p-3 text-right">Acción / ServiceNow</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <?php foreach ($auditoria as $t): ?>
                            <?php 
                                $minutos = (int)($t["minutos_campus"] ?? 0);
                                $cumplio7h = (int)($t["cumplio_7_horas"] ?? 0);
                                $fueConvalidado = !empty($t["justificado_por"]);
                            ?>
                            <tr class="hover:bg-white/[0.02] fila-item">
                                <td class="p-3 font-mono font-bold text-white"><?= $t["fecha"] ?></td>
                                <td class="p-3 font-semibold text-white">
                                    <?= htmlspecialchars($t["nombre"]) ?>
                                    <div class="text-[10px] text-slate-500">@<?= htmlspecialchars($t["username"]) ?> &bull; <?= htmlspecialchars($t["area_nombre"] ?? 'General') ?></div>
                                </td>
                                <td class="p-3">
                                    <?php if ($t["modalidad"] === "campus"): ?>
                                        <span class="px-2 py-0.5 rounded-md bg-cyan-500/20 text-cyan-300 font-bold text-[10px]">Campus</span>
                                    <?php elseif ($t["modalidad"] === "casa"): ?>
                                        <span class="px-2 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300 font-bold text-[10px]">Casa</span>
                                    <?php elseif ($t["modalidad"] === "incapacidad"): ?>
                                        <span class="px-2 py-0.5 rounded-md bg-rose-500/20 text-rose-300 font-bold text-[10px]">Incapacidad</span>
                                    <?php elseif ($t["modalidad"] === "permiso"): ?>
                                        <span class="px-2 py-0.5 rounded-md bg-purple-500/20 text-purple-300 font-bold text-[10px]">Permiso</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-400 text-[10px]">Libre</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-[11px] font-mono">
                                    <?php if (!empty($t["hora_primera_deteccion"])): ?>
                                        <?= $t["hora_primera_deteccion"] ?> - <?= $t["hora_ultima_deteccion"] ?> 
                                        <span class="text-slate-400">(<?= round($minutos / 60, 1) ?>h)</span>
                                    <?php else: ?>
                                        <span class="text-slate-500">Sin Check-in GPS</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <?php if ($fueConvalidado): ?>
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold text-[10px] flex items-center gap-1 w-fit">
                                            <i class="fa-solid fa-file-circle-check"></i> 7H Aprobadas
                                        </span>
                                        <div class="text-[9px] text-slate-400 mt-0.5 font-mono"><?= htmlspecialchars($t["motivo_justificacion"] ?? 'Ticket TI') ?></div>
                                    <?php elseif ($cumplio7h === 1): ?>
                                        <span class="text-emerald-400 font-bold text-[11px] flex items-center gap-1">
                                            <i class="fa-solid fa-circle-check"></i> Cumplido (&ge; 7h)
                                        </span>
                                    <?php elseif ($t["modalidad"] === "campus"): ?>
                                        <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 font-bold text-[10px] flex items-center gap-1 w-fit">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Incompleto (&lt; 7h)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-500">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-right">
                                    <?php if (!$fueConvalidado && $t["modalidad"] === "campus" && $cumplio7h === 0): ?>
                                        <button type="button" 
                                                onclick="abrirModalConvalidar(<?= (int)$t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nombre'])) ?>', '<?= $t['fecha'] ?>')"
                                                class="px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/20 text-[11px] font-semibold transition flex items-center gap-1 ml-auto">
                                            <i class="fa-solid fa-clipboard-check"></i> Convalidar Manual
                                        </button>
                                    <?php else: ?>
                                        <span class="text-[10px] text-emerald-400 font-mono flex items-center justify-end gap-1">
                                            <i class="fa-solid fa-check-double"></i> Convalidado
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between pt-3 text-xs text-slate-400" id="paginacion-auditoria"></div>
        </div>

        <!-- 4. TAB: FESTIVOS POR DECRETO -->
        <div id="tabContent-festivos" class="tab-content hidden pt-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="relative flex-1 max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                    <input type="text" id="busqueda-festivos" onkeyup="filtrarTablaGenerico('festivos')" placeholder="Buscar por fecha, nombre o decreto..." class="w-full bg-slate-900/80 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400">
                </div>
                <button type="button" onclick="abrirModalFestivoDecreto()" class="px-4 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-md shadow-cyan-500/20 flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i> Registrar Decreto
                </button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-white/5">
                <table class="w-full text-left text-xs" id="tabla-festivos">
                    <thead class="bg-slate-900/90 text-slate-400 font-semibold uppercase tracking-wider text-[10px] border-b border-white/5">
                        <tr>
                            <th class="p-3">Fecha del Feriado</th>
                            <th class="p-3">Descripción / Motivo</th>
                            <th class="p-3">Decreto / Norma</th>
                            <th class="p-3">Fecha de Registro</th>
                            <th class="p-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <?php if (empty($festivosDecretos)): ?>
                            <tr class="fila-item">
                                <td colspan="5" class="p-6 text-center text-slate-500">
                                    No hay festivos extraordinarios registrados. El sistema opera con los 18 festivos de ley.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($festivosDecretos as $f): ?>
                                <tr class="hover:bg-white/[0.02] fila-item">
                                    <td class="p-3 font-mono text-cyan-400 font-bold">
                                        <i class="fa-solid fa-calendar-day mr-1 text-slate-500"></i> <?= $f["fecha"] ?>
                                    </td>
                                    <td class="p-3 font-bold text-white"><?= htmlspecialchars($f["descripcion"]) ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-800 border border-white/5 font-mono text-[11px] text-slate-300">
                                            <?= htmlspecialchars($f["decreto"] ?: 'Decreto Extraordinario') ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-500 text-[11px]"><?= substr($f["created_at"], 0, 10) ?></td>
                                    <td class="p-3 text-right">
                                        <button type="button" onclick="eliminarFestivoDecreto(<?= (int)$f['id'] ?>)" class="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs transition" title="Eliminar Festivo">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between pt-3 text-xs text-slate-400" id="paginacion-festivos"></div>
        </div>

        <!-- 5. TAB: CARGOS -->
        <div id="tabContent-cargos" class="tab-content hidden pt-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="relative flex-1 max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                    <input type="text" id="busqueda-cargos" onkeyup="filtrarTablaGenerico('cargos')" placeholder="Buscar cargo..." class="w-full bg-slate-900/80 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400">
                </div>
                <button type="button" onclick="abrirModalCargo()" class="px-4 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-md shadow-cyan-500/20 flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i> Nuevo Cargo
                </button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-white/5">
                <table class="w-full text-left text-xs" id="tabla-cargos">
                    <thead class="bg-slate-900/90 text-slate-400 font-semibold uppercase tracking-wider text-[10px] border-b border-white/5">
                        <tr>
                            <th class="p-3">Nombre del Cargo</th>
                            <th class="p-3">Nivel de Jerarquía</th>
                            <th class="p-3">Asignados</th>
                            <th class="p-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <?php foreach ($cargos as $c): ?>
                            <tr class="hover:bg-white/[0.02] fila-item">
                                <td class="p-3 font-bold text-white"><?= htmlspecialchars($c["nombre"]) ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-800 border border-white/5 font-mono text-[11px] text-cyan-400">
                                        Nivel <?= (int)$c["nivel_jerarquia"] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-slate-400"><?= (int)($c["total_asignados"] ?? 0) ?> usuarios</td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" onclick="editarCargo(<?= htmlspecialchars(json_encode($c)) ?>)" class="p-2 rounded-lg bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-400 border border-cyan-500/20 text-xs transition" title="Editar Cargo">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button type="button" onclick="eliminarCargo(<?= (int)$c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nombre'])) ?>')" class="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs transition" title="Eliminar Cargo">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between pt-3 text-xs text-slate-400" id="paginacion-cargos"></div>
        </div>

        <!-- 6. TAB: ÁREAS -->
        <div id="tabContent-areas" class="tab-content hidden pt-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="relative flex-1 max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                    <input type="text" id="busqueda-areas" onkeyup="filtrarTablaGenerico('areas')" placeholder="Buscar área..." class="w-full bg-slate-900/80 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400">
                </div>
                <button type="button" onclick="abrirModalArea()" class="px-4 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-md shadow-cyan-500/20 flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i> Nueva Área
                </button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-white/5">
                <table class="w-full text-left text-xs" id="tabla-areas">
                    <thead class="bg-slate-900/90 text-slate-400 font-semibold uppercase tracking-wider text-[10px] border-b border-white/5">
                        <tr>
                            <th class="p-3">Nombre</th>
                            <th class="p-3">Descripción</th>
                            <th class="p-3">Miembros</th>
                            <th class="p-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <?php foreach ($areas as $a): ?>
                            <tr class="hover:bg-white/[0.02] fila-item">
                                <td class="p-3 font-bold text-white"><?= htmlspecialchars($a["nombre"]) ?></td>
                                <td class="p-3 text-slate-400 text-[11px]"><?= htmlspecialchars($a["descripcion"] ?? 'Sin descripción') ?></td>
                                <td class="p-3 text-slate-400"><?= (int)($a["total_miembros"] ?? 0) ?> usuarios</td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" onclick="editarArea(<?= htmlspecialchars(json_encode($a)) ?>)" class="p-2 rounded-lg bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-400 border border-cyan-500/20 text-xs transition" title="Editar Área">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button type="button" onclick="eliminarArea(<?= (int)$a['id'] ?>, '<?= htmlspecialchars(addslashes($a['nombre'])) ?>')" class="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs transition" title="Eliminar Área">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between pt-3 text-xs text-slate-400" id="paginacion-areas"></div>
        </div>

    </div>
</main>

<!-- MODAL: GESTIÓN DE COLABORADORES (CREAR / EDITAR) -->
<div id="modalUsuario" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-lg rounded-3xl p-6 border border-white/10 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 id="modalUsuarioTitulo" class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-user-gear text-cyan-400"></i> Nuevo Colaborador
            </h3>
            <button type="button" onclick="cerrarModalUsuario()" class="text-slate-400 hover:text-white text-xs">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formUsuarioAdmin" class="space-y-3">
            <input type="hidden" name="id" id="usuarioId">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Nombre Completo</label>
                    <input type="text" name="nombre" id="userNombre" required placeholder="Ej: Carolina Delgado" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Usuario (@)</label>
                    <input type="text" name="username" id="userUsername" required placeholder="Ej: cdelgado" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Correo Electrónico</label>
                    <input type="email" name="email" id="userEmail" required placeholder="correo@empresa.com" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Contraseña <span id="passOpcional" class="text-[9px] text-slate-500 font-normal"></span></label>
                    <input type="password" name="password" id="userPassword" placeholder="Dejar en blanco para no cambiar" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Departamento / Área</label>
                    <select name="area_id" id="userAreaId" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Cargo & Nivel</label>
                    <select name="cargo_id" id="userCargoId" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
                        <?php foreach ($cargos as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?> (Nivel <?= $c['nivel_jerarquia'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer bg-slate-900/60 p-2.5 rounded-xl border border-white/5">
                    <input type="checkbox" name="es_admin" id="userEsAdmin" value="1" class="rounded bg-slate-800 border-white/10 text-cyan-400 focus:ring-0">
                    <span>Permisos Super Admin</span>
                </label>
                <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer bg-slate-900/60 p-2.5 rounded-xl border border-white/5">
                    <input type="checkbox" name="labora_festivos" id="userLaboraFestivos" value="1" class="rounded bg-slate-800 border-white/10 text-cyan-400 focus:ring-0">
                    <span>Labora Feriados de Ley</span>
                </label>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-white/10">
                <button type="button" onclick="cerrarModalUsuario()" class="px-3.5 py-1.5 rounded-xl border border-white/10 text-xs text-slate-400 hover:text-white">Cancelar</button>
                <button type="submit" id="btnGuardarUsuario" class="px-4 py-1.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs shadow-md shadow-cyan-500/20">Guardar Colaborador</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: DECISIÓN DE NOVEDAD (APROBAR / RECHAZAR) -->
<div id="modalDecisionNovedad" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-md rounded-3xl p-6 border border-white/10 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 id="modalDecisionTitulo" class="text-sm font-bold text-white flex items-center gap-2"></h3>
            <button type="button" onclick="cerrarModalDecisionNovedad()" class="text-slate-400 hover:text-white text-xs">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formDecisionNovedad" class="space-y-3">
            <input type="hidden" name="solicitud_id" id="decisionSolicitudId">
            <input type="hidden" name="decision" id="decisionTipo">

            <div class="bg-slate-900/60 p-3 rounded-xl border border-white/5 text-xs text-slate-300 space-y-1">
                <p>Colaborador: <strong id="decisionNombreUser" class="text-white"></strong></p>
                <p>Fecha Turno: <strong id="decisionFechaTurno" class="text-cyan-400 font-mono"></strong></p>
                <p>Ticket / Radicado: <strong id="decisionTicket" class="text-amber-300 font-mono"></strong></p>
            </div>

            <div>
                <label id="decisionLabelComentario" class="block text-[11px] font-semibold text-slate-300 mb-1">Comentario de Auditoría / Supervisión</label>
                <textarea name="respuesta" id="decisionComentarioInput" rows="3" class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-cyan-400 resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-white/10">
                <button type="button" onclick="cerrarModalDecisionNovedad()" class="px-3 py-1.5 rounded-xl border border-white/10 text-xs text-slate-400 hover:text-white">Cancelar</button>
                <button type="submit" id="btnConfirmarDecision" class="px-4 py-1.5 rounded-xl font-bold text-xs shadow-md transition">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CONVALIDAR 7H MANUAL SERVICENOW -->
<div id="modalConvalidarTicket" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-sm rounded-3xl p-6 border border-white/10 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-clipboard-check text-amber-400"></i> Convalidar Horas por Ticket
            </h3>
            <button type="button" onclick="cerrarModalConvalidar()" class="text-slate-400 hover:text-white text-xs"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="formConvalidarTicket" class="space-y-3">
            <input type="hidden" name="turno_id" id="convalidarTurnoId">

            <div class="bg-slate-900/60 p-3 rounded-xl border border-white/5 text-xs text-slate-300">
                <p>Colaborador: <strong id="convalidarNombreUser" class="text-white"></strong></p>
                <p>Fecha a convalidar: <strong id="convalidarFechaTurno" class="text-cyan-400 font-mono"></strong></p>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Ticket ServiceNow / Soporte</label>
                <input type="text" name="motivo" id="convalidarMotivoInput" placeholder="Ej: INC0094821 - Falla Marcación" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-white/10">
                <button type="button" onclick="cerrarModalConvalidar()" class="px-3 py-1.5 rounded-xl border border-white/10 text-xs text-slate-400 hover:text-white">Cancelar</button>
                <button type="submit" class="px-4 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-md shadow-amber-500/20">Aprobar 7H</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: FESTIVO POR DECRETO -->
<div id="modalFestivoDecreto" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-sm rounded-3xl p-6 border border-white/10 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-calendar-plus text-cyan-400"></i> Registrar Feriado por Decreto
            </h3>
            <button type="button" onclick="cerrarModalFestivoDecreto()" class="text-slate-400 hover:text-white text-xs"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="formFestivoDecretoAdmin" class="space-y-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Fecha del Feriado</label>
                <input type="date" name="fecha" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Descripción / Motivo</label>
                <input type="text" name="descripcion" placeholder="Ej: Día Cívico por la Paz" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Decreto / Norma (Opcional)</label>
                <input type="text" name="decreto" placeholder="Ej: Decreto Presidencial 0500" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-white/10">
                <button type="button" onclick="cerrarModalFestivoDecreto()" class="px-3 py-1.5 rounded-xl border border-white/10 text-xs text-slate-400 hover:text-white">Cancelar</button>
                <button type="submit" class="px-4 py-1.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs shadow-md shadow-cyan-500/20">Guardar Feriado</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: GESTIÓN DE CARGOS (CREAR / EDITAR) -->
<div id="modalCargo" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-sm rounded-3xl p-6 border border-white/10 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 id="modalCargoTitulo" class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-id-badge text-cyan-400"></i> Nuevo Cargo
            </h3>
            <button type="button" onclick="cerrarModalCargo()" class="text-slate-400 hover:text-white text-xs">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formCargoAdmin" class="space-y-3">
            <input type="hidden" name="id" id="cargoId">

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Nombre del Cargo</label>
                <input type="text" name="nombre" id="cargoNombre" required placeholder="Ej: Analista Senior" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Nivel Jerárquico</label>
                <select name="nivel_jerarquia" id="cargoNivel" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
                    <option value="1">Nivel 1 - Operativo / Analista</option>
                    <option value="2">Nivel 2 - Especialista / Senior</option>
                    <option value="3">Nivel 3 - Team Lead / Supervisor (Audita Nivel 1 y 2)</option>
                    <option value="4">Nivel 4 - Gerente de Área</option>
                    <option value="5">Nivel 5 - Director de División</option>
                    <option value="6">Nivel 6 - Vicepresidencia / Ejecutivo</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-white/10">
                <button type="button" onclick="cerrarModalCargo()" class="px-3.5 py-1.5 rounded-xl border border-white/10 text-xs text-slate-400 hover:text-white">Cancelar</button>
                <button type="submit" id="btnGuardarCargo" class="px-4 py-1.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs shadow-md shadow-cyan-500/20">Guardar Cargo</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: GESTIÓN DE ÁREAS (CREAR / EDITAR) -->
<div id="modalArea" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-sm rounded-3xl p-6 border border-white/10 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 id="modalAreaTitulo" class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-building text-cyan-400"></i> Nueva Área / Departamento
            </h3>
            <button type="button" onclick="cerrarModalArea()" class="text-slate-400 hover:text-white text-xs">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formAreaAdmin" class="space-y-3">
            <input type="hidden" name="id" id="areaId">

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Nombre del Departamento</label>
                <input type="text" name="nombre" id="areaNombre" required placeholder="Ej: Seguridad de la Información" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Descripción / Función</label>
                <textarea name="descripcion" id="areaDescripcion" rows="2" placeholder="Breve detalle operativo..." class="w-full bg-slate-900 border border-white/10 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-cyan-400 resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-white/10">
                <button type="button" onclick="cerrarModalArea()" class="px-3.5 py-1.5 rounded-xl border border-white/10 text-xs text-slate-400 hover:text-white">Cancelar</button>
                <button type="submit" id="btnGuardarArea" class="px-4 py-1.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs shadow-md shadow-cyan-500/20">Guardar Área</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts del Panel Admin y Descarga Directa de PDF -->
<script src="vista/js/reporte_pdf.js?v=4.0"></script>
<script>
const estadoPaginacion = {
    usuarios:  { pagina: 1, porPagina: 10, filasFiltradas: [] },
    novedades: { pagina: 1, porPagina: 10, filasFiltradas: [] },
    auditoria: { pagina: 1, porPagina: 10, filasFiltradas: [] },
    festivos:  { pagina: 1, porPagina: 10, filasFiltradas: [] },
    cargos:    { pagina: 1, porPagina: 10, filasFiltradas: [] },
    areas:     { pagina: 1, porPagina: 10, filasFiltradas: [] }
};

document.addEventListener('DOMContentLoaded', () => {
    inicializarTablas();
});

function cambiarTabAdmin(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('text-cyan-400', 'bg-white/5');
        el.classList.add('text-slate-400');
    });

    const targetContent = document.getElementById(`tabContent-${tabId}`);
    const targetBtn = document.getElementById(`tabBtn-${tabId}`);

    if (targetContent) targetContent.classList.remove('hidden');
    if (targetBtn) {
        targetBtn.classList.remove('text-slate-400');
        targetBtn.classList.add('text-cyan-400', 'bg-white/5');
    }
}

function inicializarTablas() {
    ['usuarios', 'novedades', 'auditoria', 'festivos', 'cargos', 'areas'].forEach(id => {
        const tabla = document.getElementById(`tabla-${id}`);
        if (!tabla) return;
        const filas = Array.from(tabla.querySelectorAll('tbody .fila-item'));
        estadoPaginacion[id].filasFiltradas = filas;
        renderizarPaginacion(id);
    });
}

function renderizarPaginacion(id) {
    const estado = estadoPaginacion[id];
    const totalFilas = estado.filasFiltradas.length;
    const totalPaginas = Math.ceil(totalFilas / estado.porPagina) || 1;
    if (estado.pagina > totalPaginas) estado.pagina = totalPaginas;

    const inicio = (estado.pagina - 1) * estado.porPagina;
    const fin = inicio + estado.porPagina;

    const tabla = document.getElementById(`tabla-${id}`);
    const todasLasFilas = Array.from(tabla.querySelectorAll('tbody .fila-item'));
    todasLasFilas.forEach(f => f.style.display = 'none');

    estado.filasFiltradas.slice(inicio, fin).forEach(f => f.style.display = '');

    const paginador = document.getElementById(`paginacion-${id}`);
    if (paginador) {
        paginador.innerHTML = `
            <span>Mostrando ${totalFilas > 0 ? inicio + 1 : 0} a ${Math.min(fin, totalFilas)} de ${totalFilas}</span>
            <div class="flex gap-1">
                <button onclick="cambiarPagina('${id}', ${estado.pagina - 1})" ${estado.pagina <= 1 ? 'disabled class="opacity-40 cursor-not-allowed"' : ''} class="px-2.5 py-1 rounded-lg bg-slate-800 border border-white/5 hover:bg-slate-700">Ant</button>
                <span class="px-2.5 py-1 font-bold text-cyan-400">${estado.pagina} / ${totalPaginas}</span>
                <button onclick="cambiarPagina('${id}', ${estado.pagina + 1})" ${estado.pagina >= totalPaginas ? 'disabled class="opacity-40 cursor-not-allowed"' : ''} class="px-2.5 py-1 rounded-lg bg-slate-800 border border-white/5 hover:bg-slate-700">Sig</button>
            </div>
        `;
    }
}

function cambiarPagina(id, num) {
    estadoPaginacion[id].pagina = num;
    renderizarPaginacion(id);
}

function filtrarTablaGenerico(id) {
    const q = document.getElementById(`busqueda-${id}`).value.toLowerCase();
    const tabla = document.getElementById(`tabla-${id}`);
    const filas = Array.from(tabla.querySelectorAll('tbody .fila-item'));

    estadoPaginacion[id].filasFiltradas = filas.filter(f => f.innerText.toLowerCase().includes(q));
    estadoPaginacion[id].pagina = 1;
    renderizarPaginacion(id);
}

// 1. GESTIÓN DE COLABORADORES
function abrirModalUsuario() {
    document.getElementById('formUsuarioAdmin').reset();
    document.getElementById('usuarioId').value = '';
    document.getElementById('modalUsuarioTitulo').innerHTML = '<i class="fa-solid fa-user-plus text-cyan-400"></i> Nuevo Colaborador';
    document.getElementById('passOpcional').innerText = '(Por defecto: WorkShift2026*)';
    document.getElementById('btnGuardarUsuario').innerText = 'Guardar Colaborador';
    document.getElementById('modalUsuario').classList.remove('hidden');
}

function editarUsuario(u) {
    document.getElementById('formUsuarioAdmin').reset();
    document.getElementById('usuarioId').value = u.id;
    document.getElementById('userNombre').value = u.nombre;
    document.getElementById('userUsername').value = u.username;
    document.getElementById('userEmail').value = u.email || '';
    document.getElementById('userAreaId').value = u.area_id || 1;
    document.getElementById('userCargoId').value = u.cargo_id || 1;
    document.getElementById('userEsAdmin').checked = parseInt(u.es_admin) === 1;
    document.getElementById('userLaboraFestivos').checked = parseInt(u.labora_festivos) === 1;
    document.getElementById('modalUsuarioTitulo').innerHTML = '<i class="fa-solid fa-user-pen text-cyan-400"></i> Editar Colaborador: ' + u.nombre;
    document.getElementById('passOpcional').innerText = '(Dejar en blanco para mantener actual)';
    document.getElementById('btnGuardarUsuario').innerText = 'Actualizar Datos';
    document.getElementById('modalUsuario').classList.remove('hidden');
}

function cerrarModalUsuario() {
    document.getElementById('modalUsuario').classList.add('hidden');
}

document.getElementById('formUsuarioAdmin').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarUsuario');
    const originalText = btn.innerText;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    try {
        const res = await fetch('index.php?action=admin_guardar_usuario', {
            method: 'POST',
            body: new FormData(e.target)
        });
        const data = await res.json();
        if (data.status === 'success') {
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo guardar el usuario.'));
        }
    } catch(err) {
        alert('Error de conexión al guardar el usuario.');
    } finally {
        btn.innerText = originalText;
        btn.disabled = false;
    }
});

async function eliminarUsuario(id, nombre) {
    if (!confirm(`¿Estás seguro de que deseas eliminar a ${nombre}?`)) return;
    const fd = new FormData();
    fd.append('id', id);
    try {
        const res = await fetch('index.php?action=admin_eliminar_usuario', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
        else alert('No se pudo eliminar el usuario.');
    } catch(err) {
        alert('Error de conexión.');
    }
}

// 2. DECISIÓN DE NOVEDADES
function abrirModalDecision(solicitudId, decision, nombre, fecha, ticket) {
    document.getElementById('decisionSolicitudId').value = solicitudId;
    document.getElementById('decisionTipo').value = decision;
    document.getElementById('decisionNombreUser').innerText = nombre;
    document.getElementById('decisionFechaTurno').innerText = fecha;
    document.getElementById('decisionTicket').innerText = ticket;

    const titulo = document.getElementById('modalDecisionTitulo');
    const label = document.getElementById('decisionLabelComentario');
    const btn = document.getElementById('btnConfirmarDecision');
    const txtArea = document.getElementById('decisionComentarioInput');

    if (decision === 'aprobado') {
        titulo.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-400"></i> Aprobar Convalidación (7 Horas)';
        label.innerText = 'Observación de Aprobación (Opcional):';
        txtArea.placeholder = 'Ej: Convalidado con éxito según ticket de soporte.';
        txtArea.required = false;
        btn.className = 'px-4 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-md shadow-emerald-500/20';
        btn.innerText = 'Aprobar 7 Horas';
    } else {
        titulo.innerHTML = '<i class="fa-solid fa-circle-xmark text-rose-400"></i> Rechazar Solicitud de Novedad';
        label.innerText = 'Motivo del Rechazo (Obligatorio):';
        txtArea.placeholder = 'Explica por qué no procede la convalidación...';
        txtArea.required = true;
        btn.className = 'px-4 py-1.5 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-bold text-xs shadow-md shadow-rose-500/20';
        btn.innerText = 'Rechazar Solicitud';
    }

    txtArea.value = '';
    document.getElementById('modalDecisionNovedad').classList.remove('hidden');
}

function cerrarModalDecisionNovedad() {
    document.getElementById('modalDecisionNovedad').classList.add('hidden');
}

document.getElementById('formDecisionNovedad').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnConfirmarDecision');
    const originalText = btn.innerText;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
    btn.disabled = true;

    try {
        const res = await fetch('index.php?action=admin_responder_solicitud', {
            method: 'POST',
            body: new FormData(e.target)
        });
        const data = await res.json();

        if (data.status === 'success') {
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo actualizar el estado.'));
        }
    } catch (err) {
        alert('Error de comunicación con el servidor.');
    } finally {
        btn.innerText = originalText;
        btn.disabled = false;
    }
});

// 3. CONVALIDACIÓN MANUAL EN AUDITORÍA 7H
function abrirModalConvalidar(turnoId, nombre, fecha) {
    document.getElementById('convalidarTurnoId').value = turnoId;
    document.getElementById('convalidarNombreUser').innerText = nombre;
    document.getElementById('convalidarFechaTurno').innerText = fecha;
    document.getElementById('convalidarMotivoInput').value = '';
    document.getElementById('modalConvalidarTicket').classList.remove('hidden');
}

function cerrarModalConvalidar() {
    document.getElementById('modalConvalidarTicket').classList.add('hidden');
}

document.getElementById('formConvalidarTicket').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    try {
        const res = await fetch('index.php?action=admin_justificar_jornada', { method: 'POST', body: new FormData(e.target) });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
        else alert('Error: ' + (data.message || 'No se pudo convalidar.'));
    } catch(err) {
        alert('Error de conexión.');
    } finally {
        btn.innerHTML = 'Aprobar 7H';
        btn.disabled = false;
    }
});

// 4. FESTIVOS POR DECRETO
function abrirModalFestivoDecreto() {
    document.getElementById('formFestivoDecretoAdmin').reset();
    document.getElementById('modalFestivoDecreto').classList.remove('hidden');
}
function cerrarModalFestivoDecreto() {
    document.getElementById('modalFestivoDecreto').classList.add('hidden');
}

document.getElementById('formFestivoDecretoAdmin').addEventListener('submit', async (e) => {
    e.preventDefault();
    const res = await fetch('index.php?action=admin_guardar_festivo_decreto', { method: 'POST', body: new FormData(e.target) });
    const data = await res.json();
    if (data.status === 'success') window.location.reload();
    else alert(data.message || 'Error al guardar decreto.');
});

async function eliminarFestivoDecreto(id) {
    if (!confirm('¿Deseas eliminar este festivo decretado?')) return;
    const fd = new FormData();
    fd.append('id', id);
    const res = await fetch('index.php?action=admin_eliminar_festivo_decreto', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.status === 'success') window.location.reload();
}

// 5. GESTIÓN DE CARGOS
function abrirModalCargo() {
    document.getElementById('formCargoAdmin').reset();
    document.getElementById('cargoId').value = '';
    document.getElementById('modalCargoTitulo').innerHTML = '<i class="fa-solid fa-id-badge text-cyan-400"></i> Nuevo Cargo';
    document.getElementById('btnGuardarCargo').innerText = 'Guardar Cargo';
    document.getElementById('modalCargo').classList.remove('hidden');
}

function editarCargo(c) {
    document.getElementById('formCargoAdmin').reset();
    document.getElementById('cargoId').value = c.id;
    document.getElementById('cargoNombre').value = c.nombre;
    document.getElementById('cargoNivel').value = c.nivel_jerarquia || 1;
    document.getElementById('modalCargoTitulo').innerHTML = '<i class="fa-solid fa-pen text-cyan-400"></i> Editar Cargo: ' + c.nombre;
    document.getElementById('btnGuardarCargo').innerText = 'Actualizar Cargo';
    document.getElementById('modalCargo').classList.remove('hidden');
}

function cerrarModalCargo() {
    document.getElementById('modalCargo').classList.add('hidden');
}

document.getElementById('formCargoAdmin').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarCargo');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    try {
        const res = await fetch('index.php?action=admin_guardar_cargo', { method: 'POST', body: new FormData(e.target) });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
        else alert('Error: ' + (data.message || 'No se pudo guardar el cargo.'));
    } catch(err) {
        alert('Error de conexión.');
    } finally {
        btn.innerHTML = 'Guardar Cargo';
        btn.disabled = false;
    }
});

async function eliminarCargo(id, nombre) {
    if (!confirm(`¿Deseas eliminar el cargo "${nombre}"? Los usuarios asignados volverán a nivel base.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    try {
        const res = await fetch('index.php?action=admin_eliminar_cargo', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
        else alert('No se pudo eliminar el cargo.');
    } catch(err) {
        alert('Error de conexión.');
    }
}

// 6. GESTIÓN DE ÁREAS
function abrirModalArea() {
    document.getElementById('formAreaAdmin').reset();
    document.getElementById('areaId').value = '';
    document.getElementById('modalAreaTitulo').innerHTML = '<i class="fa-solid fa-building text-cyan-400"></i> Nueva Área';
    document.getElementById('btnGuardarArea').innerText = 'Guardar Área';
    document.getElementById('modalArea').classList.remove('hidden');
}

function editarArea(a) {
    document.getElementById('formAreaAdmin').reset();
    document.getElementById('areaId').value = a.id;
    document.getElementById('areaNombre').value = a.nombre;
    document.getElementById('areaDescripcion').value = a.descripcion || '';
    document.getElementById('modalAreaTitulo').innerHTML = '<i class="fa-solid fa-pen text-cyan-400"></i> Editar Área: ' + a.nombre;
    document.getElementById('btnGuardarArea').innerText = 'Actualizar Área';
    document.getElementById('modalArea').classList.remove('hidden');
}

function cerrarModalArea() {
    document.getElementById('modalArea').classList.add('hidden');
}

document.getElementById('formAreaAdmin').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarArea');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    try {
        const res = await fetch('index.php?action=admin_guardar_area', { method: 'POST', body: new FormData(e.target) });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
        else alert('Error: ' + (data.message || 'No se pudo guardar el área.'));
    } catch(err) {
        alert('Error de conexión.');
    } finally {
        btn.innerHTML = 'Guardar Área';
        btn.disabled = false;
    }
});

async function eliminarArea(id, nombre) {
    if (!confirm(`¿Deseas eliminar el área "${nombre}"?`)) return;
    const fd = new FormData();
    fd.append('id', id);
    try {
        const res = await fetch('index.php?action=admin_eliminar_area', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.status === 'success') window.location.reload();
        else alert('No se pudo eliminar el área.');
    } catch(err) {
        alert('Error de conexión.');
    }
}
</script>