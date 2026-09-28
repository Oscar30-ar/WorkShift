<?php
/** @var array $datos */
if (!isset($datos) || !is_array($datos)) {
    exit("Acceso no permitido");
}

$u                    = $datos["usuario"] ?? [];
$turnosConsolidados   = $datos["turnos_consolidados"] ?? [];
$nombreMes            = $datos["nombre_mes"] ?? 'Mensual';
$mesNum               = (int)($datos["mes"] ?? date("n"));
$anioNum              = (int)($datos["anio"] ?? date("Y"));
$totalCampus          = (int)($datos["total_campus"] ?? 0);
$totalCasa            = (int)($datos["total_casa"] ?? 0);
$horasCampusDecimal   = $datos["horas_campus_decimal"] ?? 0;
$totalHoras7Cumplidas = (int)($datos["total_horas_7_cumplidas"] ?? 0);

$nombreColaborador = preg_replace('/[^A-Za-z0-9_]/', '_', trim($u["nombre"] ?? 'Colaborador'));
$nombreArchivoPdf  = "Certificado_{$nombreColaborador}_{$nombreMes}_{$anioNum}.pdf";
?>

<input type="hidden" id="nombre-archivo-generado" value="<?= htmlspecialchars($nombreArchivoPdf) ?>">

<div id="hoja-reporte-a4" style="width: 750px; min-height: 1050px; background: #ffffff; color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; padding: 24px; box-sizing: border-box; font-size: 10px; line-height: 1.35;">
    
    <!-- Encabezado -->
    <table style="width: 100%; border-bottom: 2px solid #0284c7; padding-bottom: 8px; margin-bottom: 14px;">
        <tr>
            <td style="vertical-align: bottom;">
                <div style="font-size: 17px; font-weight: 900; color: #0284c7; letter-spacing: -0.5px;">WorkShift &bull; Compliance Corporativo</div>
                <div style="font-size: 8px; color: #64748b; font-weight: 700; text-transform: uppercase;">Control Institucional de Asistencia Híbrida</div>
            </td>
            <td style="text-align: right; vertical-align: bottom;">
                <div style="font-size: 13px; font-weight: 900; text-transform: uppercase; color: #0f172a;"><?= htmlspecialchars($nombreMes) ?> <?= $anioNum ?></div>
                <div style="font-size: 8px; color: #64748b;">Emisión: <?= date("d/m/Y H:i") ?></div>
            </td>
        </tr>
    </table>

    <!-- Ficha del Colaborador -->
    <table style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 14px; padding: 6px; border-collapse: separate; border-spacing: 6px;">
        <tr>
            <td style="width: 25%; vertical-align: top;">
                <span style="font-size: 7.5px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Colaborador</span>
                <strong style="font-size: 10.5px; color: #0f172a;"><?= htmlspecialchars($u["nombre"] ?? 'N/A') ?></strong>
            </td>
            <td style="width: 25%; vertical-align: top;">
                <span style="font-size: 7.5px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Usuario (@)</span>
                <strong style="font-size: 10.5px; color: #0f172a;">@<?= htmlspecialchars($u["username"] ?? 'usuario') ?></strong>
            </td>
            <td style="width: 25%; vertical-align: top;">
                <span style="font-size: 7.5px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Área / Departamento</span>
                <strong style="font-size: 10.5px; color: #0f172a;"><?= htmlspecialchars($u["area_nombre"] ?? 'Operaciones') ?></strong>
            </td>
            <td style="width: 25%; vertical-align: top;">
                <span style="font-size: 7.5px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block;">Cargo / Jerarquía</span>
                <strong style="font-size: 10.5px; color: #0f172a;"><?= htmlspecialchars($u["cargo_nombre"] ?? 'Analista') ?> (N<?= (int)($u["nivel_jerarquia"] ?? 1) ?>)</strong>
            </td>
        </tr>
    </table>

    <!-- Tarjetas de Métricas -->
    <table style="width: 100%; margin-bottom: 14px; border-collapse: separate; border-spacing: 6px;">
        <tr>
            <td style="width: 25%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center; background: #ffffff;">
                <div style="font-size: 7.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Días Campus</div>
                <div style="font-size: 16px; font-weight: 900; color: #0284c7; margin-top: 2px;"><?= $totalCampus ?></div>
            </td>
            <td style="width: 25%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center; background: #ffffff;">
                <div style="font-size: 7.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Días Remoto</div>
                <div style="font-size: 16px; font-weight: 900; color: #4f46e5; margin-top: 2px;"><?= $totalCasa ?></div>
            </td>
            <td style="width: 25%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center; background: #ffffff;">
                <div style="font-size: 7.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Horas Presenciales</div>
                <div style="font-size: 16px; font-weight: 900; color: #0f172a; margin-top: 2px;"><?= $horasCampusDecimal ?>h</div>
            </td>
            <td style="width: 25%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center; background: #ffffff;">
                <div style="font-size: 7.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Jornadas 7h Completadas</div>
                <div style="font-size: 16px; font-weight: 900; color: #16a34a; margin-top: 2px;"><?= $totalHoras7Cumplidas ?></div>
            </td>
        </tr>
    </table>

    <!-- Tabla de Asistencias -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9px;">
        <thead>
            <tr style="background: #0f172a; color: #ffffff;">
                <th style="padding: 6px 8px; text-align: left; width: 14%; text-transform: uppercase; font-size: 8px;">Fecha</th>
                <th style="padding: 6px 8px; text-align: left; width: 14%; text-transform: uppercase; font-size: 8px;">Modalidad</th>
                <th style="padding: 6px 8px; text-align: left; width: 34%; text-transform: uppercase; font-size: 8px;">Registro / Detalle Técnico</th>
                <th style="padding: 6px 8px; text-align: left; width: 14%; text-transform: uppercase; font-size: 8px;">Permanencia</th>
                <th style="padding: 6px 8px; text-align: left; width: 24%; text-transform: uppercase; font-size: 8px;">Auditoría 7 Horas</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($turnosConsolidados)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 15px;">No se registran asistencias en este periodo.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($turnosConsolidados as $i => $t): ?>
                    <?php 
                        $esConvalidado = !empty($t["justificado_por"]);
                        $minutosDia = (int)($t["minutos_campus"] ?? 0);
                        $cumple7h = ((int)($t["cumplio_7_horas"] ?? 0) === 1);
                        $bgFila = ($i % 2 === 0) ? '#ffffff' : '#f8fafc';
                    ?>
                    <tr style="background: <?= $bgFila ?>; border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 5px 8px; font-weight: 700;"><?= $t["fecha"] ?></td>
                        <td style="padding: 5px 8px;">
                            <?php if ($t["modalidad"] === "campus"): ?>
                                <span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 800; font-size: 7.5px;">CAMPUS</span>
                            <?php elseif ($t["modalidad"] === "festivo"): ?>
                                <span style="background: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 4px; font-weight: 800; font-size: 7.5px;">FESTIVO</span>
                            <?php elseif ($t["modalidad"] === "incapacidad"): ?>
                                <span style="background: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 4px; font-weight: 800; font-size: 7.5px;">INCAPACIDAD</span>
                            <?php elseif ($t["modalidad"] === "permiso"): ?>
                                <span style="background: #f3e8ff; color: #7e22ce; padding: 2px 6px; border-radius: 4px; font-weight: 800; font-size: 7.5px;">PERMISO</span>
                            <?php elseif ($t["modalidad"] === "casa"): ?>
                                <span style="background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-weight: 800; font-size: 7.5px;">REMOTO</span>
                            <?php else: ?>
                                <span style="color: #94a3b8;">Libre</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 5px 8px; color: #334155; font-size: 8px;">
                            <?php
                            if ($t["modalidad"] === "incapacidad") echo "Incapacidad Médica EPS (7h legales)";
                            elseif ($t["modalidad"] === "permiso") echo "Permiso Laboral Autorizado (7h legales)";
                            elseif ($t["modalidad"] === "festivo") echo htmlspecialchars($t["nota"] ?? "Feriado Oficial");
                            elseif ($esConvalidado) echo "<strong>Aprobado Jefatura:</strong> " . htmlspecialchars($t["motivo_justificacion"] ?? 'Ticket TI');
                            elseif (!empty($t["hora_primera_deteccion"])) echo htmlspecialchars($t["hora_primera_deteccion"] . ' - ' . $t["hora_ultima_deteccion"]);
                            else echo htmlspecialchars($t["nota"] ?? 'Marcación Manual');
                            ?>
                        </td>
                        <td style="padding: 5px 8px; color: #334155;">
                            <?= in_array($t["modalidad"], ['campus', 'festivo', 'incapacidad', 'permiso']) ? round($minutosDia / 60, 1) . 'h' : '-' ?>
                        </td>
                        <td style="padding: 5px 8px;">
                            <?php if ($t["modalidad"] === "campus"): ?>
                                <?php if ($esConvalidado): ?>
                                    <strong style="color: #16a34a;">&bull; Convalidado 7H</strong>
                                <?php elseif ($cumple7h): ?>
                                    <strong style="color: #16a34a;">&bull; Cumplido (&ge; 7h)</strong>
                                <?php else: ?>
                                    <span style="color: #b91c1c; font-weight: 700;">&bull; Incompleto (&lt; 7h)</span>
                                <?php endif; ?>
                            <?php elseif (in_array($t["modalidad"], ['festivo', 'incapacidad', 'permiso'])): ?>
                                <strong style="color: #16a34a;">&bull; Cumplimiento Legal</strong>
                            <?php else: ?>
                                <span style="color: #94a3b8;">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Firmas -->
    <table style="width: 100%; margin-top: 25px;">
        <tr>
            <td style="width: 45%; border-top: 1px solid #94a3b8; padding-top: 5px; text-align: center; font-size: 8px; color: #64748b;">
                <strong style="color: #0f172a; font-size: 9px;"><?= htmlspecialchars($u["nombre"] ?? 'Colaborador') ?></strong><br>
                Firma del Colaborador &bull; Documento de Identidad
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%; border-top: 1px solid #94a3b8; padding-top: 5px; text-align: center; font-size: 8px; color: #64748b;">
                <strong style="color: #0f172a; font-size: 9px;">Auditoría & Supervisión Operativa</strong><br>
                Validación Institucional &bull; Recursos Humanos
            </td>
        </tr>
    </table>

    <div style="margin-top: 16px; border-top: 1px solid #f1f5f9; padding-top: 6px; font-size: 7px; color: #94a3b8; text-align: justify;">
        Documento oficial emitido por la plataforma WorkShift. Certifica el cumplimiento de las jornadas y modalidades para efectos de liquidación y auditoría laboral.
    </div>
</div>