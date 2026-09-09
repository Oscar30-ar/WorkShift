<div class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-panel w-full max-w-md rounded-3xl p-8 sm:p-10 shadow-2xl relative">
        <div class="text-center mb-6">
            <div class="inline-flex h-12 w-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 items-center justify-center text-xl mb-3">
                <i class="fa-solid fa-key"></i>
            </div>
            <h2 class="text-xl font-bold text-white">Recuperar Contraseña</h2>
            <p class="text-xs text-slate-400 mt-1">Ingresa el correo asociado a tu cuenta</p>
        </div>

        <?php
        $rec = new UsuariosControlador();
        $rec->ctrSolicitarRecuperacion();
        ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Correo Electrónico</label>
                <input type="email" name="recEmail" required placeholder="tu@correo.com" class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-cyan-400">
            </div>

            <button type="submit" class="w-full py-3 bg-gradient-to-r from-cyan-500 to-indigo-600 text-white font-semibold rounded-xl text-sm transition">
                Enviar enlace
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-400">
            <a href="index.php?ruta=login" class="text-cyan-400 hover:underline">Volver a iniciar sesión</a>
        </div>
    </div>
</div>