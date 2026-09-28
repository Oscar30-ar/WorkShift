<?php
if (!isset($_SESSION["id"])) {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

$miUsuario = UsuarioModelo::mdlBuscarPorId($_SESSION["id"]);
$miNivel = (int)($miUsuario["nivel_jerarquia"] ?? 1);
$miAreaId = (int)($miUsuario["area_id"] ?? 1);

$termino = isset($_GET["q"]) ? trim($_GET["q"]) : "";
$tab = isset($_GET["tab"]) && in_array($_GET["tab"], ["todos", "siguiendo", "seguidores"]) ? $_GET["tab"] : "todos";
$pagina = isset($_GET["pag"]) ? max(1, (int)$_GET["pag"]) : 1;
$porPagina = 10;

$contadores = UsuarioModelo::mdlContadoresSociales($_SESSION["id"]);
$totalUsuarios = UsuarioModelo::mdlContarCompaneros($_SESSION["id"], $termino, $tab);
$totalPaginas = max(1, ceil($totalUsuarios / $porPagina));
$companeros = UsuarioModelo::mdlBuscarCompanerosPaginado($_SESSION["id"], $termino, $tab, $pagina, $porPagina);
?>

<main class="flex-1 max-w-4xl mx-auto px-3 py-6 w-full space-y-5">
    <!-- Encabezado y Estadísticas de Red Social -->
    <div class="glass-panel p-5 sm:p-6 rounded-3xl shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase font-extrabold text-cyan-400 tracking-wider">Red de Trabajo & Equipo</span>
                <?php if ($miNivel >= 3): ?>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        <i class="fa-solid fa-crown mr-1 text-[8px]"></i>Rol Supervisor (Nivel <?= $miNivel ?>)
                    </span>
                <?php endif; ?>
            </div>
            <h2 class="text-xl font-bold text-white mt-0.5">¿Dónde está el equipo hoy?</h2>
            <p class="text-xs text-slate-400">
                Siguiendo: <strong class="text-white"><?= $contadores['siguiendo'] ?></strong> &bull;
                Seguidores: <strong class="text-white"><?= $contadores['seguidores'] ?></strong>
            </p>
        </div>

        <form method="GET" action="index.php" class="w-full sm:w-72">
            <input type="hidden" name="ruta" value="equipo">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($termino) ?>" placeholder="Buscar por nombre o @usuario..." class="w-full bg-slate-900/80 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400">
            </div>
        </form>
    </div>

    <!-- Pestañas Tipo Red Social -->
    <div class="flex items-center gap-2 border-b border-white/10 pb-3">
        <a href="index.php?ruta=equipo&tab=todos&q=<?= urlencode($termino) ?>"
            class="px-4 py-2 rounded-xl text-xs font-bold transition <?= $tab === 'todos' ? 'bg-cyan-500 text-slate-950 shadow-md shadow-cyan-500/20' : 'bg-slate-900/50 text-slate-400 hover:text-white border border-white/5' ?>">
            Todos
        </a>
        <a href="index.php?ruta=equipo&tab=siguiendo&q=<?= urlencode($termino) ?>"
            class="px-4 py-2 rounded-xl text-xs font-bold transition <?= $tab === 'siguiendo' ? 'bg-cyan-500 text-slate-950 shadow-md shadow-cyan-500/20' : 'bg-slate-900/50 text-slate-400 hover:text-white border border-white/5' ?>">
            Siguiendo (<?= $contadores['siguiendo'] ?>)
        </a>
        <a href="index.php?ruta=equipo&tab=seguidores&q=<?= urlencode($termino) ?>"
            class="px-4 py-2 rounded-xl text-xs font-bold transition <?= $tab === 'seguidores' ? 'bg-cyan-500 text-slate-950 shadow-md shadow-cyan-500/20' : 'bg-slate-900/50 text-slate-400 hover:text-white border border-white/5' ?>">
            Seguidores (<?= $contadores['seguidores'] ?>)
        </a>
    </div>

    <!-- Listado de Personas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <?php if (empty($companeros)): ?>
            <div class="sm:col-span-2 glass-panel p-8 rounded-3xl text-center text-slate-400 text-xs">
                No hay personas registradas en esta sección.
            </div>
        <?php else: ?>
            <?php foreach ($companeros as $c):
                $inicial = strtoupper(substr($c["nombre"] ?? "U", 0, 1));
                $loSigo = !empty($c["lo_sigo"]) && (int)$c["lo_sigo"] > 0;
                $meSigue = !empty($c["me_sigue"]) && (int)$c["me_sigue"] > 0;
                $perfilPublico = isset($c["perfil_publico"]) ? ((int)$c["perfil_publico"] === 1) : true;
                $turno = $c["turno_hoy"] ?? 'ninguno';

                // LÓGICA DE SUPERVISOR (Punto 4):
                // Si soy Manager o superior (Nivel >= 3), estoy en la misma área y el compañero tiene menor nivel, tengo acceso de supervisión
                $suNivel = (int)($c["nivel_jerarquia"] ?? 1);
                $mismaArea = ((int)$c["area_id"] === $miAreaId);
                $soySuSupervisor = ($miNivel >= 3 && $miNivel > $suNivel && $mismaArea);

                $puedeVerTurno = ($loSigo && $perfilPublico) || $soySuSupervisor;
            ?>
                <div class="glass-panel p-4 rounded-2xl flex items-center justify-between gap-3 border border-white/5 hover:border-white/10 transition">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-12 w-12 rounded-xl bg-gradient-to-tr from-slate-800 to-slate-700 border border-white/10 flex items-center justify-center text-white font-bold text-sm shrink-0 relative">
                            <?= $inicial ?>
                            <?php if ($meSigue): ?>
                                <span class="absolute -bottom-1 -right-1 text-[8px] bg-indigo-500 text-white px-1 rounded-full border border-slate-900" title="Te sigue">
                                    <i class="fa-solid fa-arrow-turn-down-left"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <h4 class="text-xs font-bold text-white truncate"><?= htmlspecialchars($c["nombre"]) ?></h4>
                                <?php if (!empty($c["cargo_nombre"])): ?>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-white/5 text-slate-300 font-mono">
                                        <?= htmlspecialchars($c["cargo_nombre"]) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-[11px] text-cyan-400 font-mono truncate">
                                @<?= htmlspecialchars($c["username"]) ?>
                                <?php if (!empty($c["area_nombre"])): ?>
                                    <span class="text-slate-500 font-sans">&bull; <?= htmlspecialchars($c["area_nombre"]) ?></span>
                                <?php endif; ?>
                            </p>

                            <div class="mt-1 flex items-center gap-1.5">
                                <?php if ($soySuSupervisor): ?>
                                    <span class="text-[9px] font-bold text-amber-400/90 flex items-center gap-1">
                                        <i class="fa-solid fa-eye text-[8px]"></i> Supervisión:
                                    </span>
                                <?php endif; ?>

                                <?php if (!$puedeVerTurno): ?>
                                    <span class="text-[10px] text-slate-500 flex items-center gap-1">
                                        <i class="fa-solid fa-user-plus text-[9px] text-slate-600"></i> Síguelo para ver su turno
                                    </span>
                                <?php elseif (!$perfilPublico && !$soySuSupervisor): ?>
                                    <span class="text-[10px] text-slate-500 flex items-center gap-1">
                                        <i class="fa-solid fa-lock text-[9px] text-amber-500/80"></i> Perfil privado
                                    </span>
                                <?php elseif ($turno === 'campus'): ?>
                                    <span class="text-[10px] font-semibold text-cyan-300 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span> En Campus
                                    </span>
                                <?php elseif ($turno === 'casa'): ?>
                                    <span class="text-[10px] font-semibold text-indigo-300 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span> En Casa
                                    </span>
                                <?php else: ?>
                                    <span class="text-[10px] text-slate-500">Sin turno hoy</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>


                    <?php if ($soySuSupervisor || (!empty($_SESSION["es_admin"]) && (int)$_SESSION["es_admin"] === 1)): ?>
                        <a href="index.php?ruta=calendario&ver_usuario=<?= (int)$c['id'] ?>"
                            class="shrink-0 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-slate-950 border border-amber-500/30 transition duration-150 flex items-center gap-1.5"
                            title="Auditar calendario mensual">
                            <i class="fa-solid fa-calendar-week text-[11px]"></i> Horario
                        </a>
                    <?php endif; ?>
                    <button type="button"
                        onclick="toggleFollow(<?= (int)$c['id'] ?>, this)"
                        class="shrink-0 px-3 py-1.5 rounded-xl text-xs font-semibold transition duration-150 <?= $loSigo ? 'bg-white/5 hover:bg-rose-500/20 text-slate-300 hover:text-rose-300 border border-white/10 hover:border-rose-500/30' : 'bg-cyan-500 hover:bg-cyan-400 text-slate-950 shadow-md shadow-cyan-500/20' ?>">
                        <?= $loSigo ? 'Siguiendo' : 'Seguir' ?>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
        <div class="flex items-center justify-center gap-2 pt-3">
            <?php if ($pagina > 1): ?>
                <a href="index.php?ruta=equipo&tab=<?= $tab ?>&q=<?= urlencode($termino) ?>&pag=<?= $pagina - 1 ?>" class="px-3.5 py-1.5 rounded-xl border border-white/10 bg-slate-900/60 text-xs text-slate-300 hover:bg-white/10 transition">
                    <i class="fa-solid fa-chevron-left mr-1"></i> Anterior
                </a>
            <?php endif; ?>

            <span class="text-xs text-slate-400 font-semibold px-2">Página <?= $pagina ?> de <?= $totalPaginas ?></span>

            <?php if ($pagina < $totalPaginas): ?>
                <a href="index.php?ruta=equipo&tab=<?= $tab ?>&q=<?= urlencode($termino) ?>&pag=<?= $pagina + 1 ?>" class="px-3.5 py-1.5 rounded-xl border border-white/10 bg-slate-900/60 text-xs text-slate-300 hover:bg-white/10 transition">
                    Siguiente <i class="fa-solid fa-chevron-right ml-1"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<script>
    async function toggleFollow(id, btn) {
        btn.disabled = true;
        btn.style.opacity = '0.5';

        try {
            const formData = new FormData();
            formData.append('seguido_id', id);

            const url = window.location.pathname + '?action=seguir_usuario';
            const res = await fetch(url, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Error al procesar.');
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        } catch (e) {
            window.location.reload();
        }
    }
</script>