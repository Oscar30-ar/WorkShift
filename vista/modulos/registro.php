<div class="flex-1 w-full flex items-center justify-center p-4 py-8">
    <div class="glass-panel w-full max-w-sm sm:max-w-md rounded-3xl p-6 sm:p-10 shadow-2xl relative overflow-hidden">
        
        <!-- Cabecera -->
        <div class="text-center mb-6">
            <div class="inline-flex h-14 w-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-cyan-400 items-center justify-center shadow-xl shadow-indigo-500/30 mb-3">
                <i class="fa-solid fa-user-plus text-2xl text-white"></i>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-white">Crear una cuenta</h2>
            <p class="text-xs text-slate-400 mt-1">Únete para planificar tus días en campus y casa</p>
        </div>

        <!-- Formulario de Registro -->
        <form method="POST" id="formRegistro" class="space-y-4">
            <?php
            $registro = new UsuariosControlador();
            $registro->ctrRegistroUsuario();
            ?>

            <!-- Nombre Completo -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nombre Completo</label>
                <div class="relative">
                    <i class="fa-regular fa-user absolute left-4 top-3.5 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="nuevoNombre" 
                           required 
                           placeholder="Tu Nombre y Apellido" 
                           class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:outline-none focus:border-cyan-400 text-white placeholder:text-slate-500 transition">
                </div>
            </div>

            <!-- Nombre de Usuario (@) -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nombre de Usuario (@)</label>
                <div class="relative">
                    <i class="fa-solid fa-at absolute left-4 top-3.5 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="nuevoUsername" 
                           required 
                           placeholder="usuario123" 
                           pattern="[a-zA-Z0-9_]+"
                           title="Solo letras, números o guiones bajos sin espacios"
                           class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:outline-none focus:border-cyan-400 text-white placeholder:text-slate-500 transition">
                </div>
            </div>

            <!-- Correo Electrónico -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Correo Electrónico</label>
                <div class="relative">
                    <i class="fa-regular fa-envelope absolute left-4 top-3.5 text-slate-400 text-xs"></i>
                    <input type="email" 
                           name="nuevoEmail" 
                           required 
                           placeholder="tu@correo.com" 
                           class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:outline-none focus:border-cyan-400 text-white placeholder:text-slate-500 transition">
                </div>
            </div>

            <!-- Contraseña -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Contraseña</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-4 top-3.5 text-slate-400 text-xs"></i>
                    <input type="password" 
                           id="nuevoPassword"
                           name="nuevoPassword" 
                           required 
                           minlength="6"
                           placeholder="Mínimo 6 caracteres" 
                           class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:outline-none focus:border-cyan-400 text-white placeholder:text-slate-500 transition">
                </div>
            </div>

            <!-- Confirmar Contraseña -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Confirmar Contraseña</label>
                <div class="relative">
                    <i class="fa-solid fa-shield-halved absolute left-4 top-3.5 text-slate-400 text-xs"></i>
                    <input type="password" 
                           id="confirmarPassword"
                           required 
                           minlength="6"
                           placeholder="Repite tu contraseña" 
                           class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-xs focus:outline-none focus:border-cyan-400 text-white placeholder:text-slate-500 transition">
                </div>
                <span id="msgPassError" class="hidden text-[11px] text-rose-400 mt-1">Las contraseñas no coinciden.</span>
            </div>

            <!-- Checkbox Términos y Condiciones -->
            <div class="flex items-start gap-2 pt-1">
                <input type="checkbox" 
                       id="checkTerminos" 
                       required 
                       class="mt-0.5 rounded border-white/20 bg-slate-900 text-cyan-400 focus:ring-cyan-400/20 h-4 w-4 cursor-pointer">
                <label for="checkTerminos" class="text-[11px] text-slate-400 leading-tight cursor-pointer">
                    Acepto los <button type="button" onclick="abrirModalTerminos()" class="text-cyan-400 hover:underline">Términos, Condiciones</button> y la Política de Tratamiento de Datos Personales.
                </label>
            </div>

            <!-- Botón Registrarse -->
            <button type="submit" 
                    id="btnSubmitRegistro"
                    class="w-full py-3 px-4 bg-gradient-to-r from-indigo-500 to-cyan-500 hover:from-indigo-600 hover:to-cyan-600 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-lg shadow-indigo-500/25 transition duration-200">
                Registrarse
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-400">
            ¿Ya tienes una cuenta? 
            <a href="index.php?ruta=login" class="text-cyan-400 hover:underline font-semibold ml-1">Inicia sesión aquí</a>
        </div>
    </div>
</div>

<!-- Modal de Términos -->
<div id="modalTerminos" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
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
                <p><strong>1. Finalidad:</strong> Recopilación y registro de asistencia laboral presencial y remota.</p>
                <p><strong>2. Seguridad:</strong> Las contraseñas se almacenan con cifrado BCRYPT y no son accesibles por personal no autorizado.</p>
            </div>
        </div>

        <div class="pt-4 mt-4 border-t border-white/10 flex justify-end">
            <button type="button" onclick="cerrarModalTerminos()" class="px-5 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition">
                Aceptar
            </button>
        </div>
    </div>
</div>

<script>
function abrirModalTerminos() { document.getElementById('modalTerminos').classList.remove('hidden'); }
function cerrarModalTerminos() { document.getElementById('modalTerminos').classList.add('hidden'); }

// Validación de contraseñas antes del envío
document.getElementById('formRegistro').addEventListener('submit', function(e) {
    const p1 = document.getElementById('nuevoPassword').value;
    const p2 = document.getElementById('confirmarPassword').value;
    const err = document.getElementById('msgPassError');

    if (p1 !== p2) {
        e.preventDefault();
        err.classList.remove('hidden');
        document.getElementById('confirmarPassword').focus();
    } else {
        err.classList.add('hidden');
    }
});
</script>