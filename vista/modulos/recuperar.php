<?php
require_once "modelo/correoServicio.php";
require_once "modelo/usuarioModelo.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Permitir reiniciar el proceso de recuperación si el usuario lo desea
if (isset($_GET["reiniciar"])) {
    unset($_SESSION["paso_recuperacion"], $_SESSION["email_en_proceso"]);
    echo '<script>window.location = "index.php?ruta=recuperar";</script>';
    exit;
}

$paso = $_SESSION["paso_recuperacion"] ?? 1;
$error = "";

// PASO 1: Generar código de 6 dígitos y enviar por correo
if (isset($_POST["accion_recuperar"]) && $_POST["accion_recuperar"] === "enviar_codigo") {
    $email = trim($_POST["email_rec"] ?? "");
    $usr = UsuarioModelo::mdlBuscarPorEmail($email);

    if ($usr) {
        $codigo = (string)random_int(100000, 999999);
        $expiracion = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        // Guarda el código en la columna token_recuperacion y token_expira
        UsuarioModelo::mdlGuardarCodigoOtp($email, $codigo, $expiracion);

        // Envía el correo con PHPMailer / Mailer corporativo
        if (class_exists("CorreoServicio") && method_exists("CorreoServicio", "enviarCodigoRecuperacion")) {
            CorreoServicio::enviarCodigoRecuperacion($email, $codigo);
        }

        $_SESSION["email_en_proceso"] = $email;
        $_SESSION["paso_recuperacion"] = 2;
        $paso = 2;
    } else {
        $error = "El correo no se encuentra registrado en el sistema.";
    }
}

// PASO 2: Verificar el código OTP y actualizar la contraseña
if (isset($_POST["accion_recuperar"]) && $_POST["accion_recuperar"] === "validar_cambiar") {
    $codigo = trim($_POST["otp_codigo"] ?? "");
    $nuevaClave = trim($_POST["nueva_pass"] ?? "");
    $email = $_SESSION["email_en_proceso"] ?? "";

    // Se invoca el método exacto declarado en UsuarioModelo
    $valido = UsuarioModelo::mdlVerificarCodigoOtp($email, $codigo);

    if ($valido && strlen($nuevaClave) >= 6) {
        $hash = password_hash($nuevaClave, PASSWORD_BCRYPT);

        // Actualiza el password y limpia el token usado en la base de datos
        UsuarioModelo::mdlActualizarPasswordRecuperada("usuarios", (int)$valido["id"], $hash);

        unset($_SESSION["paso_recuperacion"], $_SESSION["email_en_proceso"]);
        echo '<div class="min-h-screen flex items-center justify-center p-4">
                <div class="glass-panel w-full max-w-md rounded-3xl p-8 text-center border border-emerald-500/30 bg-slate-950/90 shadow-2xl">
                    <div class="w-14 h-14 bg-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl border border-emerald-500/30">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h3 class="text-base font-bold text-white mb-1">¡Contraseña Actualizada!</h3>
                    <p class="text-xs text-slate-400 mb-4">Ya puedes ingresar con tus nuevas credenciales.</p>
                    <a href="index.php?ruta=login" class="px-5 py-2.5 bg-cyan-500 text-slate-950 font-bold rounded-xl text-xs inline-block">Ir a Iniciar Sesión</a>
                </div>
              </div>';
        echo '<script>setTimeout(function(){ window.location = "index.php?ruta=login"; }, 2500);</script>';
        return;
    } else {
        $error = "Código incorrecto, expirado (10 min) o la contraseña tiene menos de 6 caracteres.";
    }
}
?>

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-panel w-full max-w-md rounded-3xl p-8 sm:p-10 shadow-2xl relative border border-white/10 bg-slate-950/90">
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 mx-auto mb-3 text-lg">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h2 class="text-xl font-bold text-white mb-1">Recuperar Acceso</h2>
            <p class="text-xs text-slate-400">
                <?= $paso === 1 ? 'Ingresa tu correo institucional para recibir tu código de 6 dígitos.' : 'Ingresa el código enviado a <strong class="text-cyan-400">' . htmlspecialchars($_SESSION["email_en_proceso"] ?? "") . '</strong>' ?>
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-3 mb-5 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/20 rounded-xl flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-400"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <?php if ($paso === 1): ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="accion_recuperar" value="enviar_codigo">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2">Correo Electrónico</label>
                    <input type="email" name="email_rec" required placeholder="tu@correo.com" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-cyan-400 transition">
                </div>
                <button type="submit" class="w-full py-3 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:opacity-90 text-slate-950 font-bold rounded-xl text-xs transition shadow-lg shadow-cyan-500/10">
                    Enviar Código (10 min)
                </button>
            </form>
        <?php else: ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="accion_recuperar" value="validar_cambiar">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2">Código de 6 dígitos</label>
                    <input type="text" name="otp_codigo" maxlength="6" pattern="[0-9]{6}" required placeholder="123456" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-center text-lg tracking-widest font-mono text-cyan-400 focus:outline-none focus:border-cyan-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2">Nueva Contraseña</label>
                    <input type="password" name="nueva_pass" required minlength="6" placeholder="Mínimo 6 caracteres" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-cyan-400">
                </div>
                <button type="submit" class="w-full py-3 bg-gradient-to-r from-emerald-400 to-cyan-500 hover:opacity-90 text-slate-950 font-bold rounded-xl text-xs transition shadow-lg shadow-emerald-500/10">
                    Verificar y Cambiar Contraseña
                </button>
                <div class="text-center pt-2">
                    <a href="index.php?ruta=recuperar&reiniciar=1" class="text-[11px] text-slate-400 hover:text-cyan-400 transition">
                        ¿Escribiste mal el correo? Enviar a otro
                    </a>
                </div>
            </form>
        <?php endif; ?>

        <div class="mt-6 pt-4 border-t border-white/5 text-center text-xs">
            <a href="index.php?ruta=login" class="text-slate-400 hover:text-white transition">Regresar al Login</a>
        </div>
    </div>
</div>