<?php
// Evitar acceso directo sin pasar por el controlador
if (!isset($datos) || !isset($datos["usuario"])) {
    header("Location: index.php?ruta=calendario");
    exit;
}

$u = $datos["usuario"];
$turnosConsolidados = $datos["turnos_consolidados"];
$nombreMes = $datos["nombre_mes"];
$mesNum = $datos["mes"];
$anioNum = $datos["anio"];
$totalCampus = $datos["total_campus"];
$totalCasa = $datos["total_casa"];
$horasCampusDecimal = $datos["horas_campus_decimal"];
$totalHoras7Cumplidas = $datos["total_horas_7_cumplidas"];
?>
<?php
$usernameLimpio = preg_replace('/[^A-Za-z0-9_]/', '', $u["username"] ?? 'usuario');
$nombreColaborador = preg_replace('/[^A-Za-z0-9_]/', '_', trim($u["nombre"] ?? 'Colaborador'));
$tituloArchivo = "Certificado_{$nombreColaborador}_{$nombreMes}_{$anioNum}";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- El navegador usa esta etiqueta para bautizar el archivo PDF al guardar -->
    <title><?= $tituloArchivo ?></title>
    <link rel="stylesheet" href="vista/css/reporte.css?v=<?= time() ?>">
</head>
<body>
    <div class="contenedor-documento">
        <div class="top-actions">
            <a href="javascript:history.back()" class="btn-volver">&larr; Regresar al Sistema</a>
            <button type="button" class="btn-guardar" onclick="window.print()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                    <path d="M6 14h12v8H6z" />
                </svg>
                Guardar PDF / Imprimir
            </button>
        </div>

        <div class="header-reporte">
            <div>
                <div class="logo-banco">WorkShift &bull; Compliance Corporativo</div>
                <div class="subtitulo-banco">Control Institucional de Asistencia Híbrida</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 13px; font-weight: 800; text-transform: uppercase; color: #0f172a;"><?= htmlspecialchars($nombreMes) ?> <?= $anioNum ?></div>
                <div style="font-size: 9px; color: #64748b;">Emisión: <?= date("d/m/Y H:i") ?></div>
            </div>
        </div>

        <div class="grid-info">
            <div class="info-item">
                <label>Colaborador</label>
                <strong><?= htmlspecialchars($u["nombre"]) ?></strong>
            </div>
            <div class="info-item">
                <label>Usuario (@)</label>
                <strong>@<?= htmlspecialchars($u["username"]) ?></strong>
            </div>
            <div class="info-item">
                <label>Área / Departamento</label>
                <strong><?= htmlspecialchars($u["area_nombre"] ?? 'Operaciones') ?></strong>
            </div>
            <div class="info-item">
                <label>Cargo / Jerarquía</label>
                <strong><?= htmlspecialchars($u["cargo_nombre"] ?? 'Analista') ?> (N<?= (int)($u["nivel_jerarquia"] ?? 1) ?>)</strong>
            </div>
        </div>

        <div class="grid-kpis">
            <div class="kpi-card">
                <p>Días Campus</p>
                <h3 style="color: #0284c7;"><?= $totalCampus ?></h3>
            </div>
            <div class="kpi-card">
                <p>Días Remoto</p>
                <h3 style="color: #4f46e5;"><?= $totalCasa ?></h3>
            </div>
            <div class="kpi-card">
                <p>Horas Presenciales</p>
                <h3><?= $horasCampusDecimal ?>h</h3>
            </div>
            <div class="kpi-card">
                <p>Jornadas 7h Completadas</p>
                <h3 style="color: #16a34a;"><?= $totalHoras7Cumplidas ?></h3>
            </div>
        </div>

        <table class="tabla-datos">
            <thead>
                <tr>
                    <th style="width: 13%;">Fecha</th>
                    <th style="width: 14%;">Modalidad</th>
                    <th style="width: 33%;">Registro / Detalle Técnico</th>
                    <th style="width: 15%;">Permanencia</th>
                    <th style="width: 25%;">Auditoría 7 Horas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($turnosConsolidados)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#94a3b8; padding:20px;">No se registran marcaciones en el periodo solicitado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($turnosConsolidados as $t): ?>
                        <?php 
                            $esConvalidado = !empty($t["justificado_por"]);
                            $minutosDia = (int)($t["minutos_campus"] ?? 0);
                            $cumple7h = ((int)($t["cumplio_7_horas"] ?? 0) === 1);
                        ?>
                        <tr>
                            <td><strong><?= $t["fecha"] ?></strong></td>
                            <td>
                                <?php if ($t["modalidad"] === "campus"): ?>
                                    <span class="badge badge-campus">Campus</span>
                                <?php elseif ($t["modalidad"] === "festivo"): ?>
                                    <span class="badge badge-festivo">Festivo</span>
                                <?php elseif ($t["modalidad"] === "incapacidad"): ?>
                                    <span class="badge badge-incapacidad">Incapacidad</span>
                                <?php elseif ($t["modalidad"] === "permiso"): ?>
                                    <span class="badge badge-permiso">Permiso</span>
                                <?php elseif ($t["modalidad"] === "casa"): ?>
                                    <span class="badge badge-casa">Remoto</span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">Libre</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 9px;">
                                <?php
                                if ($t["modalidad"] === "incapacidad") {
                                    echo "Incapacidad Médica EPS (7h computadas por ley)";
                                } elseif ($t["modalidad"] === "permiso") {
                                    echo "Permiso Laboral Aprobado (7h computadas por ley)";
                                } elseif ($t["modalidad"] === "festivo") {
                                    echo htmlspecialchars($t["nota"] ?? "Feriado Oficial");
                                } elseif ($esConvalidado) {
                                    echo "<strong>Aprobado por Supervisión:</strong> " . htmlspecialchars($t["motivo_justificacion"] ?? 'Ticket TI');
                                } elseif (!empty($t["hora_primera_deteccion"])) {
                                    echo htmlspecialchars($t["hora_primera_deteccion"] . ' - ' . $t["hora_ultima_deteccion"]);
                                } else {
                                    echo htmlspecialchars($t["nota"] ?? 'Marcación Manual');
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (in_array($t["modalidad"], ['campus', 'festivo', 'incapacidad', 'permiso'])) {
                                    echo round($minutosDia / 60, 1) . 'h';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($t["modalidad"] === "campus"): ?>
                                    <?php if ($esConvalidado): ?>
                                        <strong style="color:#16a34a;">&bull; Convalidado 7H</strong>
                                    <?php elseif ($cumple7h): ?>
                                        <strong style="color:#16a34a;">&bull; Cumplido (&ge; 7h)</strong>
                                    <?php else: ?>
                                        <span style="color:#b91c1c; font-weight:700;">&bull; Incompleto (&lt; 7h)</span>
                                    <?php endif; ?>
                                <?php elseif (in_array($t["modalidad"], ['festivo', 'incapacidad', 'permiso'])): ?>
                                    <strong style="color:#16a34a;">&bull; Cumplimiento Legal</strong>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer-firmas">
            <div class="linea-firma">
                <strong><?= htmlspecialchars($u["nombre"]) ?></strong><br>
                Firma del Colaborador &bull; Documento de Identidad
            </div>
            <div class="linea-firma">
                <strong>Auditoría & Supervisión</strong><br>
                Validación de Compliance &bull; Recursos Humanos
            </div>
        </div>

        <div class="aviso-legal">
            Documento expedido de conformidad con las políticas laborales de alternancia corporativa. Los datos reflejados certifican las horas y modalidades acumuladas en el periodo para efectos de auditoría y nómina.
        </div>
    </div>

    <script>
        // En iOS Safari el usuario usa el botón nativo de Compartir o la barra; en navegadores de escritorio abre el diálogo de inmediato
        const esIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        if (!esIOS) {
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        }
    </script>
</body>
</html>