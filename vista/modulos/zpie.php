<script src='vista/js/main.js'></script>
<script src="vista/js/reporte_pdf.js?v=1.0"></script>
<footer class="w-full mt-auto border-t border-white/5 bg-slate-950/60 backdrop-blur-md py-4 px-4">
    <div class="w-full max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
        <!-- Logo y subtítulo -->
        <div class="flex items-center justify-center gap-2">
            <div class="h-6 w-6 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xs">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <span class="text-xs font-bold text-white tracking-wide">Work<span class="text-cyan-400">Shift</span></span>
            <span class="text-slate-600 text-xs hidden sm:inline">•</span>
            <span class="text-[11px] text-slate-400 hidden sm:inline">Gestión de trabajo híbrido</span>
        </div>

        <!-- Copyright -->
        <p class="text-[11px] text-slate-400 font-medium">
            &copy; <?= date("Y") ?> Todos los derechos reservados.
        </p>
    </div>
</footer>
</body>
</html>