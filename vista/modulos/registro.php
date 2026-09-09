<div class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-panel w-full max-w-md rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden">
        <div class="text-center mb-8">
            <div class="inline-flex h-14 w-14 rounded-2xl bg-gradient-to-tr from-cyan-500 to-indigo-600 items-center justify-center shadow-xl shadow-cyan-500/30 mb-4">
                <i class="fa-solid fa-user-plus text-2xl text-white"></i>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-white">Crear una cuenta</h2>
            <p class="text-sm text-slate-400 mt-1">Únete para planificar tus días en campus y casa</p>
        </div>

        <form method="POST" class="space-y-4">
            <?php
            $registro = new UsuariosControlador();
            $registro->ctrRegistroUsuario();
            ?>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Nombre Completo</label>
                <div class="relative">
                    <i class="fa-regular fa-user absolute left-4 top-3.5 text-slate-400 text-sm"></i>
                    <input type="text" name="regNombre" required placeholder="Tu Nombre" class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm focus:outline-none focus:border-cyan-500 text-white placeholder:text-slate-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Nombre de Usuario (@)</label>
                <div class="relative">
                    <span class="absolute left-4 top-3 text-slate-400 text-sm font-bold">@</span>
                    <input type="text" name="regUsername" required placeholder="usuario123" class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm focus:outline-none focus:border-cyan-500 text-white placeholder:text-slate-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Correo Electrónico</label>
                <div class="relative">
                    <i class="fa-regular fa-envelope absolute left-4 top-3.5 text-slate-400 text-sm"></i>
                    <input type="email" name="regEmail" required placeholder="tu@correo.com" class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm focus:outline-none focus:border-cyan-500 text-white placeholder:text-slate-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Contraseña</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-4 top-3.5 text-slate-400 text-sm"></i>
                    <input type="password" name="regPassword" required placeholder="Mínimo 6 caracteres" class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm focus:outline-none focus:border-cyan-500 text-white placeholder:text-slate-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Confirmar Contraseña</label>
                <div class="relative">
                    <i class="fa-solid fa-shield-halved absolute left-4 top-3.5 text-slate-400 text-sm"></i>
                    <input type="password" name="regConfirmPassword" required placeholder="Repite tu contraseña" class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm focus:outline-none focus:border-cyan-500 text-white placeholder:text-slate-500 transition">
                </div>
            </div>


            <div class="pt-2">
                <label class="flex items-start gap-3 cursor-pointer select-none">
                    <input type="checkbox" name="aceptaTerminos" required class="mt-1 w-4 h-4 rounded text-cyan-500 focus:ring-0 bg-slate-900 border-white/20 cursor-pointer">
                    <span class="text-xs text-slate-400 leading-snug">
                        Acepto los <button type="button" onclick="document.getElementById('modalTerminos').classList.remove('hidden')" class="text-cyan-400 underline hover:text-cyan-300 font-medium">Términos, Condiciones</button> y la Política de Tratamiento de Datos Personales.
                    </span>
                </label>
            </div>

            <button type="submit" class="w-full mt-2 py-3.5 px-4 bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-600 hover:to-indigo-700 text-white font-semibold rounded-xl text-sm shadow-lg shadow-cyan-500/25 transition duration-200">
                Registrarse
            </button>
        </form>


        <!-- Modal de Términos y Condiciones -->
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
                        <p><strong>1. Finalidad del Tratamiento:</strong> Los datos suministrados (nombre, correo electrónico y registros de modalidad laboral) son recolectados y procesados exclusivamente para la organización y seguimiento de jornadas presenciales en campus y de trabajo en casa en la plataforma WorkShift.</p>
                        <p><strong>2. Autenticación con Google:</strong> Al iniciar sesión con Google, únicamente recibimos tu nombre y correo validado con el fin de crear o sincronizar tu perfil de forma rápida y segura.</p>
                        <p><strong>3. Privacidad entre Compañeros:</strong> Tu ubicación o modalidad diaria únicamente será visible para aquellos compañeros de trabajo registrados a quienes decidas permitir visibilidad.</p>
                        <p><strong>4. Derechos del Titular:</strong> Puedes actualizar tu información, cambiar tus preferencias de visibilidad o desvincular tus datos en cualquier momento desde el panel de Ajustes.</p>
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
            function abrirModalTerminos() {
                document.getElementById('modalTerminos').classList.remove('hidden');
            }

            function cerrarModalTerminos() {
                document.getElementById('modalTerminos').classList.add('hidden');
            }
        </script>



        <div class="mt-6 text-center text-xs text-slate-400">
            ¿Ya tienes una cuenta?
            <a href="index.php?ruta=login" class="text-cyan-400 hover:text-cyan-300 font-semibold underline underline-offset-4 ml-1 transition">
                Inicia sesión aquí
            </a>
        </div>
    </div>
</div>