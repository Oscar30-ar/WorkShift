<?php
$usuario = UsuarioModelo::mdlBuscarPorId($_SESSION["id"]);
?>

<main class="flex-1 max-w-2xl mx-auto px-4 py-8 w-full space-y-6">
    <div class="glass-panel p-6 sm:p-8 rounded-3xl shadow-2xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="h-10 w-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-sliders"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">Ajustes de Cuenta</h2>
                <p class="text-xs text-slate-400">Personaliza tu perfil, colores y preferencias de asistencia</p>
            </div>
        </div>

        <?php
        $ajustes = new UsuariosControlador();
        $ajustes->ctrActualizarAjustes();
        // Recargar datos actualizados tras procesar formulario
        $usuario = UsuarioModelo::mdlBuscarPorId($_SESSION["id"]);
        ?>

        <form method="POST" class="space-y-5">
            <!-- Datos Básicos -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre Completo</label>
                    <input type="text" name="actNombre" value="<?= htmlspecialchars($usuario["nombre"]) ?>" required class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-400">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Username (@)</label>
                    <input type="text" name="actUsername" value="<?= htmlspecialchars($usuario["username"]) ?>" required class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-400">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Correo Electrónico</label>
                <input type="email" name="actEmail" value="<?= htmlspecialchars($usuario["email"]) ?>" required class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-400">
            </div>

            <!-- Contraseña Adaptativa -->
            <div class="p-4 rounded-2xl bg-slate-900/40 border border-white/5 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-300">
                        <?= (!empty($usuario["pass_temporal"]) && $usuario["pass_temporal"] == 1) ? 'Crear Contraseña Personal' : 'Cambiar Contraseña (Opcional)' ?>
                    </span>
                    <?php if (!empty($usuario["pass_temporal"]) && $usuario["pass_temporal"] == 1): ?>
                        <span class="text-[10px] text-cyan-400 font-semibold bg-cyan-500/10 px-2 py-0.5 rounded-md border border-cyan-500/20">Cuenta Google</span>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php if (empty($usuario["pass_temporal"]) || $usuario["pass_temporal"] == 0): ?>
                        <input type="password" name="passActual" placeholder="Contraseña actual" class="w-full bg-slate-900/80 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400">
                    <?php endif; ?>
                    <input type="password" name="passNueva" placeholder="Nueva contraseña (mín. 6)" class="w-full bg-slate-900/80 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-400 <?= (!empty($usuario["pass_temporal"]) && $usuario["pass_temporal"] == 1) ? 'sm:col-span-2' : '' ?>">
                </div>
            </div>

            <!-- Colores de Calendario con Guardado en Tiempo Real -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-300">Color Campus</label>
                        <span id="badge-campus" class="text-[10px] text-emerald-400 font-semibold opacity-0 transition duration-300">Guardado</span>
                    </div>
                    <div class="flex items-center gap-2 bg-slate-900/60 p-2 rounded-xl border border-white/10">
                        <input type="color" id="pickerCampus" name="colorCampus" value="<?= htmlspecialchars($usuario["color_campus"] ?? '#06b6d4') ?>" class="h-8 w-10 bg-transparent border-0 rounded cursor-pointer">
                        <span id="hexCampus" class="text-xs text-slate-300 font-mono"><?= htmlspecialchars($usuario["color_campus"] ?? '#06b6d4') ?></span>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-300">Color Casa</label>
                        <span id="badge-casa" class="text-[10px] text-emerald-400 font-semibold opacity-0 transition duration-300">Guardado</span>
                    </div>
                    <div class="flex items-center gap-2 bg-slate-900/60 p-2 rounded-xl border border-white/10">
                        <input type="color" id="pickerCasa" name="colorCasa" value="<?= htmlspecialchars($usuario["color_casa"] ?? '#6366f1') ?>" class="h-8 w-10 bg-transparent border-0 rounded cursor-pointer">
                        <span id="hexCasa" class="text-xs text-slate-300 font-mono"><?= htmlspecialchars($usuario["color_casa"] ?? '#6366f1') ?></span>
                    </div>
                </div>
            </div>

            <!-- Preferencias y Festivos -->
            <div class="space-y-3 pt-2 border-t border-white/5">
                <label class="flex items-center gap-3 cursor-pointer p-2.5 rounded-2xl bg-slate-900/40 border border-white/5">
                    <input type="checkbox" name="perfilPublico" value="1" <?= (!empty($usuario["perfil_publico"]) && $usuario["perfil_publico"] == 1) ? 'checked' : '' ?> class="w-4 h-4 rounded text-cyan-500 focus:ring-0 bg-slate-950 border-white/20">
                    <span class="text-xs text-slate-300">Permitir que los compañeros que me siguen vean mi ubicación laboral</span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer p-3 rounded-2xl bg-slate-900/40 border border-white/5 hover:border-white/10 transition">
                    <input type="checkbox" name="laboraFestivos" value="1" <?= (!empty($usuario["labora_festivos"]) && $usuario["labora_festivos"] == 1) ? 'checked' : '' ?> class="mt-0.5 w-4 h-4 rounded text-amber-500 focus:ring-0 bg-slate-950 border-white/20 cursor-pointer">
                    <div>
                        <span class="text-xs text-white font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-business-time text-amber-400"></i> Mi área labora los días festivos
                        </span>
                        <p class="text-[11px] text-slate-400 mt-0.5 leading-snug">
                            Si está marcado, los festivos no sumarán a Campus por defecto y podrás registrarlos manualmente según tu turno.
                        </p>
                    </div>
                </label>
            </div>

            <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-600 hover:to-indigo-700 text-white font-semibold rounded-xl text-sm transition shadow-lg shadow-cyan-500/20">
                Guardar Cambios
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-white/5 flex items-center justify-between text-xs text-slate-400">
            <span>Términos y Tratamiento de Datos</span>
            <button type="button" onclick="document.getElementById('modalTerminosAjustes').classList.remove('hidden')" class="text-cyan-400 hover:underline">
                Consultar Políticas
            </button>
        </div>
    </div>
</main>

<!-- Modal de Políticas -->
<div id="modalTerminosAjustes" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-lg rounded-3xl p-6 border border-white/10 max-h-[85vh] flex flex-col justify-between shadow-2xl">
        <div>
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-cyan-400"></i> Términos y Tratamiento de Datos
                </h3>
                <button type="button" onclick="document.getElementById('modalTerminosAjustes').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="text-xs text-slate-300 space-y-3 overflow-y-auto max-h-[50vh] pr-2 mt-4 text-justify leading-relaxed">
                <p><strong>Tratamiento de Datos:</strong> Tus registros de asistencia presencial y remota son procesados con la finalidad de coordinar la ocupación y sincronización de horarios de equipo en WorkShift.</p>
                <p><strong>Configuración de Visibilidad:</strong> Puedes pausar o activar la visibilidad de tu turno en cualquier momento desde esta pantalla.</p>
                <p><strong>Derechos:</strong> En todo momento puedes solicitar la supresión o actualización de tu cuenta.</p>
            </div>
        </div>
        <div class="pt-4 border-t border-white/10 flex justify-end">
            <button type="button" onclick="document.getElementById('modalTerminosAjustes').classList.add('hidden')" class="px-5 py-2 rounded-xl bg-cyan-500 text-slate-950 font-bold text-xs hover:bg-cyan-400 transition">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
const pickerCampus = document.getElementById('pickerCampus');
const pickerCasa = document.getElementById('pickerCasa');
const hexCampus = document.getElementById('hexCampus');
const hexCasa = document.getElementById('hexCasa');

pickerCampus.addEventListener('input', (e) => { hexCampus.textContent = e.target.value; });
pickerCasa.addEventListener('input', (e) => { hexCasa.textContent = e.target.value; });

async function autoguardarColor(tipo) {
    try {
        const res = await fetch('index.php?action=guardar_colores', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                color_campus: pickerCampus.value,
                color_casa: pickerCasa.value
            })
        });
        const data = await res.json();
        if (data.status === 'success') {
            const badge = document.getElementById(tipo === 'campus' ? 'badge-campus' : 'badge-casa');
            badge.style.opacity = '1';
            setTimeout(() => { badge.style.opacity = '0'; }, 1200);
        }
    } catch (e) {
        console.error('Error guardando color:', e);
    }
}

pickerCampus.addEventListener('change', () => autoguardarColor('campus'));
pickerCasa.addEventListener('change', () => autoguardarColor('casa'));
</script>