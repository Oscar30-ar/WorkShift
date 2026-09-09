<?php $token = $_GET["token"] ?? ""; ?>
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-panel w-full max-w-md rounded-3xl p-8 sm:p-10 shadow-2xl relative">
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-white">Nueva Contraseña</h2>
            <p class="text-xs text-slate-400 mt-1">Ingresa tu nueva clave de acceso</p>
        </div>

        <?php
        $rec = new UsuariosControlador();
        $rec->ctrRestablecerClave($token);
        ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Nueva Contraseña</label>
                <input type="password" name="nuevaClave" required placeholder="Mínimo 6 caracteres" class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-cyan-400">
            </div>

            <button type="submit" class="w-full py-3 bg-gradient-to-r from-cyan-500 to-indigo-600 text-white font-semibold rounded-xl text-sm transition">
                Actualizar contraseña
            </button>
        </form>
    </div>
</div>