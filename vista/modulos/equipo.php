<?php
if (!isset($_SESSION["id"])) {
    echo '<script>window.location = "index.php?ruta=login";</script>';
    exit;
}

$termino = isset($_GET["q"]) ? trim($_GET["q"]) : "";
$pagina = isset($_GET["pag"]) ? max(1, (int)$_GET["pag"]) : 1;
$porPagina = 10;

try {
    $totalUsuarios = UsuarioModelo::mdlContarCompaneros($_SESSION["id"], $termino);
    $totalPaginas = max(1, ceil($totalUsuarios / $porPagina));
    $companeros = UsuarioModelo::mdlBuscarCompanerosPaginado($_SESSION["id"], $termino, $pagina, $porPagina);
} catch (Exception $e) {
    $totalUsuarios = 0;
    $totalPaginas = 1;
    $companeros = [];
}
?>

<main class="flex-1 max-w-4xl mx-auto px-3 py-6 w-full space-y-5">
    <div class="glass-panel p-5 rounded-3xl shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <span class="text-[10px] uppercase font-extrabold text-cyan-400 tracking-wider">Directorio de Equipo</span>
            <h2 class="text-xl font-bold text-white mt-0.5">¿Dónde están hoy?</h2>
            <p class="text-xs text-slate-400">Sigue a tus compañeros para ver su ubicación en tiempo real.</p>
        </div>

        <form method="GET" action="index.php" class="w-full sm:w-72">
            <input type="hidden" name="ruta" value="equipo">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($termino) ?>" placeholder="Buscar nombre o @usuario..." class="w-full bg-slate-900/80 border border-white/10 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-400">
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <?php if (empty($companeros)): ?>
            <div class="sm:col-span-2 glass-panel p-8 rounded-3xl text-center text-slate-400 text-xs">
                <?= empty($termino) ? 'Aún no hay otros compañeros registrados.' : 'No se encontraron compañeros registrados con ese término.' ?>
            </div>
        <?php else: ?>
            <?php foreach ($companeros as $c): 
                $inicial = strtoupper(substr($c["nombre"] ?? "U", 0, 1));
                $loSigo = !empty($c["lo_sigo"]) && (int)$c["lo_sigo"] > 0;
                $perfilPublico = isset($c["perfil_publico"]) ? ((int)$c["perfil_publico"] === 1) : true;
                $turno = $c["turno_hoy"] ?? 'ninguno';
            ?>
                <div class="glass-panel p-4 rounded-2xl flex items-center justify-between gap-3 border border-white/5 hover:border-white/10 transition">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-11 w-11 rounded-xl bg-gradient-to-tr from-slate-800 to-slate-700 border border-white/10 flex items-center justify-center text-white font-bold text-sm shrink-0">
                            <?= $inicial ?>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-white truncate"><?= htmlspecialchars($c["nombre"]) ?></h4>
                            <p class="text-[11px] text-cyan-400 font-mono truncate">@<?= htmlspecialchars($c["username"]) ?></p>
                            
                            <div class="mt-1 flex items-center gap-1.5">
                                <?php if (!$loSigo): ?>
                                    <span class="text-[10px] text-slate-500 flex items-center gap-1">
                                        <i class="fa-solid fa-user-plus text-[9px] text-slate-600"></i> Síguelo para ver su turno
                                    </span>
                                <?php elseif (!$perfilPublico): ?>
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

                    <button type="button" 
                            onclick="toggleFollow(<?= (int)$c['id'] ?>, this)"
                            class="shrink-0 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition duration-150 <?= $loSigo ? 'bg-white/5 hover:bg-rose-500/20 text-slate-300 hover:text-rose-300 border border-white/10 hover:border-rose-500/30' : 'bg-cyan-500 hover:bg-cyan-400 text-slate-950 shadow-md shadow-cyan-500/20' ?>">
                        <?= $loSigo ? 'Siguiendo' : 'Seguir' ?>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($totalPaginas > 1): ?>
        <div class="flex items-center justify-center gap-2 pt-3">
            <?php if ($pagina > 1): ?>
                <a href="index.php?ruta=equipo&q=<?= urlencode($termino) ?>&pag=<?= $pagina - 1 ?>" class="px-3.5 py-1.5 rounded-xl border border-white/10 bg-slate-900/60 text-xs text-slate-300 hover:bg-white/10 transition">
                    <i class="fa-solid fa-chevron-left mr-1"></i> Anterior
                </a>
            <?php endif; ?>

            <span class="text-xs text-slate-400 font-semibold px-2">Página <?= $pagina ?> de <?= $totalPaginas ?></span>

            <?php if ($pagina < $totalPaginas): ?>
                <a href="index.php?ruta=equipo&q=<?= urlencode($termino) ?>&pag=<?= $pagina + 1 ?>" class="px-3.5 py-1.5 rounded-xl border border-white/10 bg-slate-900/60 text-xs text-slate-300 hover:bg-white/10 transition">
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
            alert(data.message || 'No se pudo procesar la acción.');
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    } catch (e) {
        console.error('Error:', e);
        window.location.reload();
    }
}
</script>