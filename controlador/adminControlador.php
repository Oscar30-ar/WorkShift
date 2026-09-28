<?php
require_once "modelo/adminModelo.php";
require_once "controlador/festivosHelper.php";

class AdminControlador
{
    public static function verificarAccesoAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok" || empty($_SESSION["es_admin"]) || (int)$_SESSION["es_admin"] !== 1) {
            echo '<script>window.location = "index.php?ruta=calendario";</script>';
            exit;
        }
    }

    public function procesarPeticiones()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_GET["action"])) {
            return;
        }

        $action = $_GET["action"];

        // 1. REPORTES PDF
        if ($action === "descargar_reporte_pdf") {
            $datos = $this->prepararDatosReporte();
            require_once "vista/modulos/reporte_pdf.php";
            exit;
        }

        if ($action === "obtener_html_reporte") {
            $datos = $this->prepararDatosReporte();
            require "vista/modulos/plantilla_reporte_pdf.php";
            exit;
        }

        // 3. RESPUESTA DEL SUPERVISOR A LA NOVEDAD
        if ($action === "admin_responder_solicitud") {
            self::verificarAccesoAdmin();
            header('Content-Type: application/json');

            $solId = (int)($_POST["solicitud_id"] ?? 0);
            $decision = ($_POST["decision"] ?? '') === 'aprobado' ? 'aprobado' : 'rechazado';
            $respuesta = trim($_POST["respuesta"] ?? '');
            $supervisorId = (int)($_SESSION["id"] ?? 1);

            if ($solId <= 0) {
                echo json_encode(["status" => "error", "message" => "ID de solicitud no válido"]);
                exit;
            }

            if (empty($respuesta)) {
                $respuesta = ($decision === 'aprobado') ? 'Aprobado y Convalidado por Supervisión' : 'Rechazado por Jefatura';
            }

            $db = Conexion::conectar();

            try {
                $db->beginTransaction();

                $stmtSol = $db->prepare("SELECT * FROM solicitudes_novedades WHERE id = :id LIMIT 1");
                $stmtSol->execute([':id' => $solId]);
                $sol = $stmtSol->fetch(PDO::FETCH_ASSOC);

                if (!$sol) {
                    $db->rollBack();
                    echo json_encode(["status" => "error", "message" => "La solicitud no existe"]);
                    exit;
                }

                $stmtUp = $db->prepare(
                    "UPDATE solicitudes_novedades 
                     SET estado = :est, respuesta_admin = :resp, revisado_por = :sup 
                     WHERE id = :id"
                );
                $stmtUp->execute([
                    ':est'  => $decision,
                    ':resp' => $respuesta,
                    ':sup'  => $supervisorId,
                    ':id'   => $solId
                ]);

                if ($decision === 'aprobado') {
                    $motivoFinal = $sol["ticket_soporte"] . " - " . ($sol["mensaje_usuario"] ?: 'Convalidado');

                    $stmtCheckTurno = $db->prepare("SELECT id FROM turnos WHERE usuario_id = :uid AND fecha = :fec LIMIT 1");
                    $stmtCheckTurno->execute([':uid' => $sol["usuario_id"], ':fec' => $sol["fecha_turno"]]);
                    $turnoId = $stmtCheckTurno->fetchColumn();

                    if ($turnoId) {
                        $stmtTurno = $db->prepare(
                            "UPDATE turnos 
                             SET modalidad = 'campus', horas_computadas = 420, justificado_por = :sup, motivo_justificacion = :mot 
                             WHERE id = :id"
                        );
                        $stmtTurno->execute([':sup' => $supervisorId, ':mot' => $motivoFinal, ':id' => $turnoId]);
                    } else {
                        $stmtTurno = $db->prepare(
                            "INSERT INTO turnos (usuario_id, fecha, modalidad, horas_computadas, justificado_por, motivo_justificacion)
                             VALUES (:uid, :fec, 'campus', 420, :sup, :mot)"
                        );
                        $stmtTurno->execute([
                            ':uid' => $sol["usuario_id"],
                            ':fec' => $sol["fecha_turno"],
                            ':sup' => $supervisorId,
                            ':mot' => $motivoFinal
                        ]);
                    }

                    $checkRp = $db->query("SHOW TABLES LIKE 'registro_presencia'")->fetch();
                    if ($checkRp) {
                        $stmtRp = $db->prepare(
                            "INSERT INTO registro_presencia (usuario_id, fecha, minutos_campus, cumplio_7_horas, sede_detectada)
                             VALUES (:uid, :fec, 420, 1, 'Convalidado por Ticket')
                             ON DUPLICATE KEY UPDATE minutos_campus = 420, cumplio_7_horas = 1, sede_detectada = 'Convalidado por Ticket'"
                        );
                        $stmtRp->execute([':uid' => $sol["usuario_id"], ':fec' => $sol["fecha_turno"]]);
                    }
                }

                $db->commit();
                echo json_encode(["status" => "success"]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Error en admin_responder_solicitud: " . $e->getMessage());
                echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            }
            exit;
        }

        // 4. RESTO DE ACCIONES AJAX ADMIN
        if (strpos($action, "admin_") === 0) {
            $this->procesarAjaxAdmin($action);
            exit;
        }
    }

    private function prepararDatosReporte()
    {
        if (!isset($_SESSION["id"])) {
            header("Location: index.php?ruta=login");
            exit;
        }

        $miId = (int)$_SESSION["id"];
        $targetUserId = isset($_GET["usuario_id"]) ? (int)$_GET["usuario_id"] : $miId;
        $mes = isset($_GET["mes"]) ? (int)$_GET["mes"] : (int)date("n");
        $anio = isset($_GET["anio"]) ? (int)$_GET["anio"] : (int)date("Y");

        $db = Conexion::conectar();

        $stmtYo = $db->prepare("SELECT id, es_admin, area_id, cargo_id FROM usuarios WHERE id = :id LIMIT 1");
        $stmtYo->execute([':id' => $miId]);
        $yo = $stmtYo->fetch(PDO::FETCH_ASSOC);

        $esAdmin = (!empty($yo["es_admin"]) && (int)$yo["es_admin"] === 1) || (!empty($_SESSION["es_admin"]) && (int)$_SESSION["es_admin"] === 1);

        $stmtTarget = $db->prepare(
            "SELECT u.*, 
                    COALESCE(a.nombre, 'General') AS area_nombre, 
                    COALESCE(c.nombre, 'Analista') AS cargo_nombre, 
                    COALESCE(c.nivel_jerarquia, 1) AS nivel_jerarquia
             FROM usuarios u
             LEFT JOIN areas a ON a.id = u.area_id
             LEFT JOIN cargos c ON c.id = u.cargo_id
             WHERE u.id = :uid LIMIT 1"
        );
        $stmtTarget->execute([':uid' => $targetUserId]);
        $usuarioTarget = $stmtTarget->fetch(PDO::FETCH_ASSOC);

        if (!$usuarioTarget) {
            die("<div style='font-family:sans-serif; padding:20px; text-align:center;'><h3>El usuario solicitado no existe.</h3><a href='javascript:history.back()'>Volver</a></div>");
        }

        $permitido = false;
        if ($targetUserId === $miId || $esAdmin) {
            $permitido = true;
        } else {
            $miNivel = (int)($yo["cargo_id"] ?? 1);
            $suNivel = (int)($usuarioTarget["cargo_id"] ?? 1);
            $mismaArea = ((int)$yo["area_id"] === (int)$usuarioTarget["area_id"]);
            if ($miNivel >= 3 && $miNivel > $suNivel && $mismaArea) {
                $permitido = true;
            }
        }

        if (!$permitido) {
            die("<div style='font-family:sans-serif; padding:20px; text-align:center;'><h3>Acceso Denegado.</h3><a href='javascript:history.back()'>Volver</a></div>");
        }

        $fechaInicio = sprintf("%04d-%02d-01", $anio, $mes);
        $fechaFin = date("Y-m-t", strtotime($fechaInicio));

        $stmtTurnos = $db->prepare(
            "SELECT t.fecha, t.modalidad, t.nota, t.horas_computadas, t.justificado_por, t.motivo_justificacion,
                    rp.hora_primera_deteccion, rp.hora_ultima_deteccion, 
                    COALESCE(rp.minutos_campus, t.horas_computadas, 0) AS minutos_campus,
                    COALESCE(rp.cumplio_7_horas, CASE WHEN t.horas_computadas >= 420 THEN 1 ELSE 0 END, 0) AS cumplio_7_horas
             FROM turnos t
             LEFT JOIN registro_presencia rp ON rp.usuario_id = t.usuario_id AND rp.fecha = t.fecha
             WHERE t.usuario_id = :uid AND t.fecha BETWEEN :inicio AND :fin
             ORDER BY t.fecha ASC"
        );
        $stmtTurnos->execute([':uid' => $targetUserId, ':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        $turnos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

        $meses = [
            1 => "Enero",
            2 => "Febrero",
            3 => "Marzo",
            4 => "Abril",
            5 => "Mayo",
            6 => "Junio",
            7 => "Julio",
            8 => "Agosto",
            9 => "Septiembre",
            10 => "Octubre",
            11 => "Noviembre",
            12 => "Diciembre"
        ];
        $nombreMes = $meses[$mes] ?? "Mensual";
        $laboraFestivos = (int)($usuarioTarget["labora_festivos"] ?? 0);
        $festivosDelAnio = FestivosHelper::getFestivosColombia($anio);

        $mapaTurnos = [];
        foreach ($turnos as $t) {
            $mapaTurnos[$t["fecha"]] = $t;
        }

        $diasEnMes = (int)date('t', strtotime($fechaInicio));
        $turnosConsolidados = [];
        $totalCampus = 0;
        $totalCasa = 0;
        $totalMinutosCampus = 0;
        $totalHoras7Cumplidas = 0;

        for ($d = 1; $d <= $diasEnMes; $d++) {
            $fechaDia = sprintf("%04d-%02d-%02d", $anio, $mes, $d);
            $esFestivo = isset($festivosDelAnio[$fechaDia]);
            $nombreFestivo = $festivosDelAnio[$fechaDia] ?? "";

            if (isset($mapaTurnos[$fechaDia])) {
                $turno = $mapaTurnos[$fechaDia];
                $modalidad = $turno["modalidad"];
            } else {
                if ($esFestivo && !$laboraFestivos) {
                    $modalidad = "festivo";
                    $turno = [
                        "fecha" => $fechaDia,
                        "modalidad" => "festivo",
                        "nota" => "Festivo: $nombreFestivo",
                        "hora_primera_deteccion" => null,
                        "hora_ultima_deteccion" => null,
                        "minutos_campus" => 420,
                        "cumplio_7_horas" => 1,
                        "sede_detectada" => "Festivo Ley / Decreto"
                    ];
                } else {
                    continue;
                }
            }

            if (in_array($modalidad, ['campus', 'festivo', 'incapacidad', 'permiso'])) {
                $totalCampus++;

                if (in_array($modalidad, ['festivo', 'incapacidad', 'permiso'])) {
                    $turno["minutos_campus"] = 420;
                    $turno["cumplio_7_horas"] = 1;
                    $totalMinutosCampus += 420;
                    $totalHoras7Cumplidas++;
                } else {
                    $minReg = (int)($turno["minutos_campus"] ?? 0);
                    $cumpleReg = (int)($turno["cumplio_7_horas"] ?? 0);

                    if (!empty($turno["justificado_por"]) || !empty($turno["horas_computadas"])) {
                        $minReg = max($minReg, 420);
                        $cumpleReg = 1;
                        $turno["minutos_campus"] = $minReg;
                        $turno["cumplio_7_horas"] = 1;
                    }

                    $totalMinutosCampus += $minReg;
                    if ($cumpleReg === 1) $totalHoras7Cumplidas++;
                }
            } elseif ($modalidad === "casa") {
                $totalCasa++;
            }

            $turnosConsolidados[] = $turno;
        }

        return [
            "usuario"                 => $usuarioTarget,
            "turnos_consolidados"     => $turnosConsolidados,
            "mes"                     => $mes,
            "anio"                    => $anio,
            "nombre_mes"              => $nombreMes,
            "total_campus"            => $totalCampus,
            "total_casa"              => $totalCasa,
            "horas_campus_decimal"    => round($totalMinutosCampus / 60, 1),
            "total_horas_7_cumplidas" => $totalHoras7Cumplidas
        ];
    }

    private function procesarAjaxAdmin($action)
    {
        self::verificarAccesoAdmin();
        header('Content-Type: application/json');
        $db = Conexion::conectar();

        // 1. GUARDAR / EDITAR USUARIO
        if ($action === "admin_guardar_usuario") {
            $id = !empty($_POST["id"]) ? (int)$_POST["id"] : null;
            $nombre = trim($_POST["nombre"] ?? '');
            $username = trim($_POST["username"] ?? '');
            $email = trim($_POST["email"] ?? '');
            $password = trim($_POST["password"] ?? '');
            $area_id = (int)($_POST["area_id"] ?? 1);
            $cargo_id = (int)($_POST["cargo_id"] ?? 1);
            $es_admin = (int)($_POST["es_admin"] ?? 0);
            $labora_festivos = (int)($_POST["labora_festivos"] ?? 0);

            if (empty($nombre) || empty($username)) {
                echo json_encode(["status" => "error", "message" => "Nombre y usuario obligatorios"]);
                exit;
            }

            $datos = [
                "id" => $id,
                "nombre" => $nombre,
                "username" => $username,
                "email" => $email,
                "password" => $password,
                "area_id" => $area_id,
                "cargo_id" => $cargo_id,
                "es_admin" => $es_admin,
                "labora_festivos" => $labora_festivos
            ];

            $res = AdminModelo::mdlGuardarUsuario($datos);
            echo json_encode(["status" => $res === "ok" ? "success" : "error"]);
            exit;
        }

        // 2. ELIMINAR USUARIO
        if ($action === "admin_eliminar_usuario") {
            $id = (int)($_POST["id"] ?? 0);
            $res = AdminModelo::mdlEliminarUsuario($id);
            echo json_encode(["status" => $res === "ok" ? "success" : "error"]);
            exit;
        }

        // 3. CONVALIDAR MANUAL EN AUDITORÍA 7H
        if ($action === "admin_justificar_jornada") {
            $turnoId = (int)($_POST["turno_id"] ?? 0);
            $motivo = trim($_POST["motivo"] ?? "Ticket ServiceNow / Aprobado Jefatura");
            $supervisorId = (int)($_SESSION["id"] ?? 1);

            if ($turnoId <= 0) {
                echo json_encode(["status" => "error", "message" => "ID inválido"]);
                exit;
            }

            try {
                $db->beginTransaction();
                $stmtInfo = $db->prepare("SELECT usuario_id, fecha FROM turnos WHERE id = :id LIMIT 1");
                $stmtInfo->execute([':id' => $turnoId]);
                $turnoInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                if (!$turnoInfo) {
                    $db->rollBack();
                    echo json_encode(["status" => "error", "message" => "El turno no existe"]);
                    exit;
                }

                $stmtTurno = $db->prepare(
                    "UPDATE turnos 
                     SET modalidad = 'campus', horas_computadas = 420, justificado_por = :sup, motivo_justificacion = :mot 
                     WHERE id = :id"
                );
                $stmtTurno->execute([':sup' => $supervisorId, ':mot' => $motivo, ':id' => $turnoId]);

                $checkRp = $db->query("SHOW TABLES LIKE 'registro_presencia'")->fetch();
                if ($checkRp) {
                    $stmtRp = $db->prepare(
                        "INSERT INTO registro_presencia (usuario_id, fecha, minutos_campus, cumplio_7_horas, sede_detectada)
                         VALUES (:uid, :fec, 420, 1, 'Convalidado por Ticket')
                         ON DUPLICATE KEY UPDATE minutos_campus = 420, cumplio_7_horas = 1, sede_detectada = 'Convalidado por Ticket'"
                    );
                    $stmtRp->execute([':uid' => $turnoInfo["usuario_id"], ':fec' => $turnoInfo["fecha"]]);
                }

                $db->commit();
                echo json_encode(["status" => "success"]);
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            }
            exit;
        }

        // 4. GUARDAR / EDITAR CARGO
        if ($action === "admin_guardar_cargo") {
            $nombre = trim($_POST["nombre"] ?? "");
            $nivel = (int)($_POST["nivel_jerarquia"] ?? 1);
            if (empty($nombre)) {
                echo json_encode(["status" => "error", "message" => "Nombre requerido"]);
                exit;
            }
            $datos = ["id" => !empty($_POST["id"]) ? (int)$_POST["id"] : null, "nombre" => $nombre, "nivel_jerarquia" => max(1, min(6, $nivel))];
            $res = AdminModelo::mdlGuardarCargo($datos);
            echo json_encode(["status" => $res === "ok" ? "success" : "error"]);
            exit;
        }

        if ($action === "admin_eliminar_cargo") {
            $res = AdminModelo::mdlEliminarCargo((int)($_POST["id"] ?? 0));
            echo json_encode(["status" => $res === "ok" ? "success" : "error"]);
            exit;
        }

        // 5. GUARDAR / EDITAR ÁREA
        if ($action === "admin_guardar_area") {
            $nombre = trim($_POST["nombre"] ?? "");
            $desc = trim($_POST["descripcion"] ?? "");

            if (empty($nombre)) {
                echo json_encode(["status" => "error", "message" => "El nombre del área es obligatorio"]);
                exit;
            }

            $datos = [
                "id"          => !empty($_POST["id"]) ? (int)$_POST["id"] : null,
                "nombre"      => $nombre,
                "descripcion" => $desc
            ];

            $res = AdminModelo::mdlGuardarArea($datos);

            if ($res === "ok") {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => $res]);
            }
            exit;
        }
        // 6. FESTIVOS POR DECRETO
        if ($action === "admin_guardar_festivo_decreto") {
            $fecha = trim($_POST["fecha"] ?? "");
            $desc = trim($_POST["descripcion"] ?? "");
            $decreto = trim($_POST["decreto"] ?? "");
            if (!empty($fecha) && !empty($desc)) {
                $stmt = $db->prepare("INSERT INTO festivos_personalizados (fecha, descripcion, decreto) VALUES (:f, :d, :dec) ON DUPLICATE KEY UPDATE descripcion = :d, decreto = :dec");
                $res = $stmt->execute([':f' => $fecha, ':d' => $desc, ':dec' => $decreto]);
                echo json_encode(["status" => $res ? "success" : "error"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
            }
            exit;
        }

        if ($action === "admin_eliminar_festivo_decreto") {
            $stmt = $db->prepare("DELETE FROM festivos_personalizados WHERE id = :id");
            echo json_encode(["status" => $stmt->execute([':id' => (int)($_POST["id"] ?? 0)]) ? "success" : "error"]);
            exit;
        }
    }
}
