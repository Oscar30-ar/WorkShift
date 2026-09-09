<?php
$paso = $_SESSION["paso_recuperacion"] ?? 1;

if (isset($_POST["accion_recuperar"]) && $_POST["accion_recuperar"] === "enviar_codigo") {
    $email = trim($_POST["email_rec"]);
    $usr = UsuarioModelo::mdlBuscarPorEmail($email);
    if ($usr) {
        $codigo = (string)random_int(100000, 999999);
        UsuarioModelo::mdlGuardarCodigoOtp($email, $codigo);
        CorreoServicio::enviarCodigoRecuperacion($email, $codigo);
        $_SESSION["email_en_proceso"] = $email;
        $_SESSION["paso_recuperacion"] = 2;
        $paso = 2;
    } else {
        $error = "El correo no se encuentra en el sistema.";
    }
}

if (isset($_POST["accion_recuperar"]) && $_POST["accion_recuperar"] === "validar_cambiar") {
    $codigo = trim($_POST["otp_codigo"]);
    $nuevaClave = trim($_POST["nueva_pass"]);
    $email = $_SESSION["email_en_proceso"] ?? "";

    $valido = UsuarioModelo::mdlVerificarOtp($email, $codigo);
    if ($valido && strlen($nuevaClave) >= 6) {
        $hash = password_hash($nuevaClave, PASSWORD_BCRYPT);
        UsuarioModelo::mdlRestablecerClave($valido["id"], $hash);
        unset($_SESSION["paso_recuperacion"], $_SESSION["email_en_proceso"]);
        echo '<div class="p-3 mb-4 text-xs font-semibold text-emerald-300 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">Contraseña cambiada con éxito.</div>';
        echo '<script>setTimeout(function(){ window.location = "index.php?ruta=login"; }, 1500);</script>';
    } else {
        $error = "Código incorrecto, expirado (10 min) o contraseña muy corta.";
    }
}
?>

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-panel w-full max-w-md rounded-3xl p-8 sm:p-10 shadow-2xl relative">
        <h2 class="text-xl font-bold text-white text-center mb-2">Recuperar Acceso</h2>
        <p class="text-xs text-slate-400 text-center mb-6">
            <?= $paso === 1 ? 'Ingresa tu correo para recibir tu código de 6 dígitos.' : 'Ingresa el código enviado a tu correo y tu nueva contraseña.' ?>
        </p>

        <?php if (!empty($error)): ?>
            <div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($paso === 1): ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="accion_recuperar" value="enviar_codigo">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2">Correo Electrónico</label>
                    <input type="email" name="email_rec" required placeholder="tu@correo.com" class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-cyan-400">
                </div>
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-indigo-500 to-cyan-500 hover:opacity-90 text-white font-semibold rounded-xl text-sm transition">
                    Enviar Código (10 min)
                </button>
            </form>
        <?php else: ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="accion_recuperar" value="validar_cambiar">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2">Código de 6 dígitos</label>
                    <input type="text" name="otp_codigo" maxlength="6" required placeholder="123456" class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-4 py-3 text-center text-lg tracking-widest font-mono text-cyan-400 focus:outline-none focus:border-cyan-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2">Nueva Contraseña</label>
                    <input type="password" name="nueva_pass" required minlength="6" placeholder="Mínimo 6 caracteres" class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-cyan-400">
                </div>
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-cyan-500 hover:opacity-90 text-white font-semibold rounded-xl text-sm transition">
                    Verificar y Cambiar Contraseña
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-6 text-center text-xs">
            <a href="index.php?ruta=login" class="text-slate-400 hover:text-white transition">Regresar al Login</a>
        </div>
    </div>
</div>