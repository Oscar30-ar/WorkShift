<?php
// Configuración Google OAuth 2.0 Universal (Compatible al 100% con iOS / Safari)
$googleClientId = "507765840362-srte8jua5329bto4rh777cnj2ddhcbsa.apps.googleusercontent.com";
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$rutaBase = strtok($_SERVER["REQUEST_URI"], '?');
if (substr($rutaBase, -9) !== 'index.php') {
    $rutaBase = rtrim($rutaBase, '/') . '/index.php';
}
$redirectUri = $protocolo . $_SERVER['HTTP_HOST'] . $rutaBase . "?action=google_oauth_callback";

$googleAuthUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => $googleClientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'access_type' => 'online',
    'prompt' => 'select_account'
]);
?>

<div class="flex-1 w-full flex items-center justify-center p-4 py-8">
    <div class="glass-panel w-full max-w-sm sm:max-w-md rounded-3xl p-6 sm:p-10 shadow-2xl relative overflow-hidden">
        
        <!-- Cabecera -->
        <div class="text-center mb-6">
            <div class="inline-flex h-14 w-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-cyan-400 items-center justify-center shadow-xl shadow-indigo-500/30 mb-3">
                <i class="fa-solid fa-calendar-check text-2xl text-white"></i>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-white">Bienvenido a WorkShift</h2>
            <p class="text-xs text-slate-400 mt-1">Gestión corporativa de campus y trabajo remoto</p>
        </div>

        <!-- Botón Nativo con Diseño Glassmorphism (A prueba de balas en Safari iOS) -->
        <a href="<?= htmlspecialchars($googleAuthUrl) ?>" 
           class="w-full flex items-center justify-center gap-3 py-3 px-4 rounded-xl border border-white/10 bg-slate-900/60 hover:bg-white/5 hover:border-white/20 text-slate-200 hover:text-white font-medium text-xs sm:text-sm transition duration-200 shadow-sm active:scale-[0.99]">
            <svg class="h-4 w-4" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Continuar con Google</span>
        </a>

        <!-- Aviso legal y enlace funcional -->
        <p class="text-[10px] text-slate-400 text-center mt-2.5 max-w-xs mx-auto leading-relaxed">
            Al continuar con Google, confirmas que aceptas nuestros 
            <button type="button" onclick="abrirModalTerminos()" class="text-cyan-400 hover:text-cyan-300 underline font-medium">
                Términos y Tratamiento de Datos
            </button>.
        </p>

        <!-- Separador -->
        <div class="relative flex py-3 items-center">
            <div class="flex-grow border-t border-white/10"></div>
            <span class="flex-shrink mx-3 text-[10px] text-slate-400 uppercase tracking-widest font-semibold">o con credenciales</span>
            <div class="flex-grow border-t border-white/10"></div>
        </div>

        <!-- Formulario tradicional -->
        <form method="POST" class="space-y-4">
            <?php
            $login = new UsuariosControlador();
            $login->ctrIngresoUsuario();
            ?>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Correo o Username</label>
                <div class="relative">
                    <i class="fa-regular fa-envelope absolute left-4 top-3.5 text-slate-400 text-xs"></i>
                    <input type="text" name="ingEmail" required placeholder="tu@correo.com o @username" class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:outline-none focus:border-cyan-400 text-white placeholder:text-slate-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Contraseña</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-4 top-3.5 text-slate-400 text-xs"></i>
                    <input type="password" name="ingPassword" required placeholder="••••••••" class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:outline-none focus:border-cyan-400 text-white placeholder:text-slate-500 transition">
                </div>
            </div>

            <div class="flex justify-end pt-0.5">
                <a href="index.php?ruta=recuperar" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium transition">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-indigo-500 to-cyan-500 hover:from-indigo-600 hover:to-cyan-600 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-lg shadow-indigo-500/25 transition duration-200">
                Iniciar Sesión
            </button>
        </form>

        <a href="index.php?ruta=registro" class="w-full flex items-center justify-center gap-2 py-3 px-4 mt-3 rounded-xl border border-white/10 bg-slate-900/40 hover:bg-white/5 text-slate-200 hover:text-white font-semibold text-xs sm:text-sm transition duration-200">
            <i class="fa-solid fa-user-plus text-xs text-cyan-400"></i>
            <span>Crear cuenta nueva</span>
        </a>

        <?php if (isset($_GET["error"])): ?>
            <div class="mt-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs text-center">
                <?php 
                    if ($_GET["error"] === "google_rechazado") echo "No fue posible validar tu cuenta con Google.";
                    else echo "Ocurrió un error al procesar el inicio de sesión.";
                ?>
            </div>
        <?php endif; ?>

        <div class="mt-6 text-center text-xs text-slate-400">
            Creado por: <span class="text-cyan-400 font-semibold">Oscar Bohorquez</span>
        </div>
    </div>
</div>

<!-- Modal de Términos y Condiciones -->
<div id="modalTerminosLogin" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="glass-panel w-full max-w-lg rounded-3xl p-6 border border-white/10 max-h-[85vh] flex flex-col justify-between shadow-2xl">
        <div>
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-cyan-400"></i> Términos y Tratamiento de Datos
                </h3>
                <button type="button" onclick="cerrarModalTerminos()" class="text-slate-400 hover:text-white text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <div class="text-xs text-slate-300 space-y-3 overflow-y-auto max-h-[50vh] pr-2 mt-4 text-justify leading-relaxed">
                <p><strong>1. Finalidad:</strong> Organización y seguimiento de jornadas presenciales en campus y de trabajo en casa en WorkShift.</p>
                <p><strong>2. Autenticación con Google:</strong> Recibimos únicamente tu correo corporativo o personal y tu nombre para crear y sincronizar tu sesión de forma segura.</p>
                <p><strong>3. Privacidad y Niveles de Supervisión:</strong> Tu información es visible según tu jerarquía y área laboral, así como para aquellos compañeros que sigues mutuamente.</p>
            </div>
        </div>

        <div class="pt-4 mt-4 border-t border-white/10 flex justify-end">
            <button type="button" onclick="cerrarModalTerminos()" class="px-5 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-lg shadow-cyan-500/20">
                Aceptar y Continuar
            </button>
        </div>
    </div>
</div>

<script>
function abrirModalTerminos() { document.getElementById('modalTerminosLogin').classList.remove('hidden'); }
function cerrarModalTerminos() { document.getElementById('modalTerminosLogin').classList.add('hidden'); }
</script>