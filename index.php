<?php
session_start();
date_default_timezone_set('America/Bogota');

require_once "modelo/conexion.php";
require_once "modelo/usuarioModelo.php";
require_once "modelo/turnoModelo.php";
require_once "controlador/plantillaControlador.php";
require_once "controlador/usuariosControlador.php";

require_once "modelo/adminModelo.php";
require_once "controlador/adminControlador.php";

// Atender peticiones de PDF y acciones AJAX del Admin
$adminControlador = new AdminControlador();
$adminControlador->procesarPeticiones();

// 1. Login con Google por Redirección (Soporte nativo iOS / Safari / PWA)
if (isset($_GET["action"]) && $_GET["action"] === "login_google_redirect") {
    $idToken = $_POST["credential"] ?? "";

    if (empty($idToken)) {
        header("Location: index.php?ruta=login&error=token_vacio");
        exit;
    }

    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idToken);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)');
    $respuesta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $payload = json_decode($respuesta, true);

    if ($httpCode === 200 && $payload && isset($payload["email"]) && $payload["email_verified"] === "true") {
        $email = filter_var($payload["email"], FILTER_SANITIZE_EMAIL);
        $nombre = trim($payload["name"] ?? "Usuario Google");

        $usuario = UsuarioModelo::mdlBuscarPorEmail($email);

        if (!$usuario) {
            $baseUser = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $email)[0]));
            $username = $baseUser;
            $i = 1;

            while (UsuarioModelo::mdlBuscarPorUsername($username)) {
                $username = $baseUser . $i;
                $i++;
            }

            $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

            $stmtInsert = Conexion::conectar()->prepare(
                "INSERT INTO usuarios (nombre, username, email, password, pass_temporal, color_campus, color_casa, perfil_publico, labora_festivos) 
                 VALUES (:nom, :user, :email, :pass, 1, '#06b6d4', '#6366f1', 1, 0)"
            );
            $stmtInsert->execute([
                ":nom" => $nombre,
                ":user" => $username,
                ":email" => $email,
                ":pass" => $randomPass
            ]);

            $usuario = UsuarioModelo::mdlBuscarPorEmail($email);
        }

        $_SESSION["iniciarSesion"] = "ok";
        $_SESSION["id"] = $usuario["id"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["username"] = $usuario["username"];
        $_SESSION["email"] = $usuario["email"];
        $_SESSION["color_campus"] = $usuario["color_campus"] ?? '#06b6d4';
        $_SESSION["color_casa"] = $usuario["color_casa"] ?? '#6366f1';
        $_SESSION["labora_festivos"] = (int)($usuario["labora_festivos"] ?? 0);
        $_SESSION["pass_temporal"] = (int)($usuario["pass_temporal"] ?? 0);

        header("Location: index.php?ruta=calendario");
        exit;
    } else {
        header("Location: index.php?ruta=login&error=google_rechazado");
        exit;
    }
}

// 2. Guardar Turno (Compatible con POST normal, FormData y JSON)
if (isset($_GET["action"]) && $_GET["action"] === "guardar_turno") {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION["id"])) {
        echo json_encode(["status" => "error", "message" => "Sesión cerrada"]);
        exit;
    }

    $datosJson = json_decode(file_get_contents("php://input"), true);
    $fecha = $_POST["fecha"] ?? $datosJson["fecha"] ?? "";
    $modalidad = $_POST["modalidad"] ?? $datosJson["modalidad"] ?? "";

    if (!empty($fecha) && !empty($modalidad)) {
        $resultado = TurnoModelo::mdlActualizarTurno($_SESSION["id"], $fecha, $modalidad);
        echo json_encode(["status" => $resultado ? "success" : "error", "modalidad" => $modalidad]);
    } else {
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    }
    exit;
}

// 3. Seguir / Dejar de seguir usuario
if (isset($_GET["action"]) && $_GET["action"] === "seguir_usuario") {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION["id"])) {
        echo json_encode(["status" => "error", "message" => "No autorizado"]);
        exit;
    }

    $seguidoId = (int)($_POST["seguido_id"] ?? 0);
    if ($seguidoId > 0 && $seguidoId !== (int)$_SESSION["id"]) {
        $db = Conexion::conectar();
        $check = $db->prepare("SELECT id FROM seguidores WHERE seguidor_id = :sid AND seguido_id = :gid");
        $check->execute([":sid" => $_SESSION["id"], ":gid" => $seguidoId]);
        $existe = $check->fetch();

        if ($existe) {
            $del = $db->prepare("DELETE FROM seguidores WHERE id = :id");
            $del->execute([":id" => $existe["id"]]);
            echo json_encode(["status" => "success", "accion" => "unfollowed"]);
        } else {
            $ins = $db->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (:sid, :gid)");
            $ins->execute([":sid" => $_SESSION["id"], ":gid" => $seguidoId]);
            echo json_encode(["status" => "success", "accion" => "followed"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "ID inválido"]);
    }
    exit;
}

// Intercambio oficial de código de autorización Google OAuth 2.0
if (isset($_GET["action"]) && $_GET["action"] === "google_oauth_callback") {
    $code = $_GET["code"] ?? "";

    if (empty($code)) {
        header("Location: index.php?ruta=login&error=google_rechazado");
        exit;
    }

    $googleConfig = require __DIR__ . '/config/google.local.php';
    // PEGA AQUÍ TU CLIENT SECRET DE GOOGLE CLOUD
    $googleClientSecret = $googleConfig['client_secret'];

    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $rutaBase = strtok($_SERVER["REQUEST_URI"], '?');
    if (substr($rutaBase, -9) !== 'index.php') {
        $rutaBase = rtrim($rutaBase, '/') . '/index.php';
    }
    $redirectUri = $protocolo . $_SERVER['HTTP_HOST'] . $rutaBase . "?action=google_oauth_callback";

    // 1. Canjear 'code' por 'access_token'
    $ch = curl_init("https://oauth2.googleapis.com/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code'          => $code,
        'client_id'     => $googleConfig['client_id'],
        'client_secret' => $googleClientSecret,
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resToken = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    $tokenData = json_decode($resToken, true);

    if (!isset($tokenData["access_token"])) {
        // En caso de depuración local: si falla, puedes descomentar la siguiente línea para ver qué responde Google exactamente:
        // die("Error de Google: " . $resToken . " | Error cURL: " . $curlError);
        header("Location: index.php?ruta=login&error=google_rechazado");
        exit;
    }

    // 2. Obtener datos del perfil con el access token
    $chUser = curl_init("https://www.googleapis.com/oauth2/v3/userinfo");
    curl_setopt($chUser, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $tokenData["access_token"]]);
    curl_setopt($chUser, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chUser, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chUser, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($chUser, CURLOPT_TIMEOUT, 15);
    $resUser = curl_exec($chUser);
    curl_close($chUser);

    $userData = json_decode($resUser, true);

    if ($userData && isset($userData["email"])) {
        $email = filter_var($userData["email"], FILTER_SANITIZE_EMAIL);
        $nombre = trim($userData["name"] ?? "Usuario Google");

        $usuario = UsuarioModelo::mdlBuscarPorEmail($email);

        if (!$usuario) {
            $baseUser = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $email)[0]));
            $username = $baseUser;
            $i = 1;
            while (UsuarioModelo::mdlBuscarPorUsername($username)) {
                $username = $baseUser . $i;
                $i++;
            }

            $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
            $db = Conexion::conectar();
            $stmt = $db->prepare(
                "INSERT INTO usuarios (nombre, username, email, password, pass_temporal, color_campus, color_casa, perfil_publico, labora_festivos, area_id, cargo_id, es_admin) 
                 VALUES (:nom, :user, :email, :pass, 1, '#06b6d4', '#6366f1', 1, 0, 1, 1, 0)"
            );
            $stmt->execute([
                ":nom"   => $nombre,
                ":user"  => $username,
                ":email" => $email,
                ":pass"  => $randomPass
            ]);

            $usuario = UsuarioModelo::mdlBuscarPorEmail($email);
        }

        // Iniciar sesión con todos los privilegios (incluyendo es_admin)
        $_SESSION["iniciarSesion"] = "ok";
        $_SESSION["id"] = $usuario["id"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["username"] = $usuario["username"];
        $_SESSION["email"] = $usuario["email"];
        $_SESSION["color_campus"] = $usuario["color_campus"] ?? '#06b6d4';
        $_SESSION["color_casa"] = $usuario["color_casa"] ?? '#6366f1';
        $_SESSION["labora_festivos"] = (int)($usuario["labora_festivos"] ?? 0);
        $_SESSION["area_id"] = (int)($usuario["area_id"] ?? 1);
        $_SESSION["cargo_id"] = (int)($usuario["cargo_id"] ?? 1);
        $_SESSION["cargo_nombre"] = $usuario["cargo_nombre"] ?? "";
        $_SESSION["es_admin"] = (int)($usuario["es_admin"] ?? 0);

        header("Location: index.php?ruta=calendario");
        exit;
    } else {
        header("Location: index.php?ruta=login&error=google_rechazado");
        exit;
    }
}

if (isset($_GET["action"]) && $_GET["action"] === "guardar_presencia_geo") {
    header('Content-Type: application/json');

    if (!isset($_SESSION["id"])) {
        echo json_encode(['status' => 'error', 'message' => 'Sesión expirada']);
        exit;
    }

    $usuarioId = (int)$_SESSION["id"];
    $fechaHoy = date("Y-m-d");
    $horaActual = date("H:i:s");

    $tipoLugar = $_POST["tipo_lugar"] ?? ""; // 'campus' o 'casa'
    $sedeNombre = $_POST["sede_nombre"] ?? "Campus";

    if (!in_array($tipoLugar, ['campus', 'casa'])) {
        echo json_encode(['status' => 'error', 'message' => 'Tipo de lugar inválido']);
        exit;
    }

    $db = Conexion::conectar();

    if ($tipoLugar === 'campus') {
        // Consultar si ya existe registro de hoy
        $stmt = $db->prepare("SELECT * FROM registro_presencia WHERE usuario_id = :uid AND fecha = :fecha LIMIT 1");
        $stmt->execute([':uid' => $usuarioId, ':fecha' => $fechaHoy]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            // Primer check-in del día
            $ins = $db->prepare(
                "INSERT INTO registro_presencia (usuario_id, fecha, hora_primera_deteccion, hora_ultima_deteccion, conteo_aperturas, sede_detectada, minutos_campus, cumplio_7_horas) 
                 VALUES (:uid, :fecha, :hora1, :hora2, 1, :sede, 0, 0)"
            );
            $ins->execute([
                ':uid' => $usuarioId,
                ':fecha' => $fechaHoy,
                ':hora1' => $horaActual,
                ':hora2' => $horaActual,
                ':sede' => $sedeNombre
            ]);

            echo json_encode([
                'status' => 'success',
                'modalidad_asignada' => 'en_progreso',
                'mensaje' => 'Primer check-in registrado en ' . $sedeNombre . '. Recuerda volver a abrir la app al finalizar tu jornada (mínimo 7h).'
            ]);
            exit;
        } else {
            // Check-in posterior (segunda o más aperturas)
            $horaPrimera = $registro["hora_primera_deteccion"];
            $aperturas = (int)$registro["conteo_aperturas"] + 1;

            $tsInicio = strtotime($registro["fecha"] . ' ' . $horaPrimera);
            $tsFin = strtotime($fechaHoy . ' ' . $horaActual);
            $minutosDiferencia = max(0, round(($tsFin - $tsInicio) / 60));

            // Regla: 7 horas = 420 minutos y al menos 2 aperturas
            $cumplio7Horas = ($minutosDiferencia >= 420 && $aperturas >= 2) ? 1 : 0;

            $upd = $db->prepare(
                "UPDATE registro_presencia 
                 SET hora_ultima_deteccion = :hora_ult, 
                     conteo_aperturas = :aperturas, 
                     minutos_campus = :minutos, 
                     cumplio_7_horas = :cumplio 
                 WHERE id = :reg_id"
            );
            $upd->execute([
                ':hora_ult' => $horaActual,
                ':aperturas' => $aperturas,
                ':minutos' => $minutosDiferencia,
                ':cumplio' => $cumplio7Horas,
                ':reg_id' => $registro["id"]
            ]);

            // Si cumplió las 7 horas, se consolida oficialmente como "campus" en la tabla turnos
            if ($cumplio7Horas) {
                TurnoModelo::mdlActualizarTurno($usuarioId, $fechaHoy, 'campus');
                $modalidadFinal = 'campus';
            } else {
                $modalidadFinal = 'en_progreso';
            }

            echo json_encode([
                'status' => 'success',
                'modalidad_asignada' => $modalidadFinal,
                'minutos_acumulados' => $minutosDiferencia,
                'aperturas' => $aperturas,
                'cumplio_7_horas' => (bool)$cumplio7Horas
            ]);
            exit;
        }
    } elseif ($tipoLugar === 'casa') {
        // En casa no se sobrescribe si ya se cumplieron las 7h en campus
        $check = $db->prepare("SELECT cumplio_7_horas FROM registro_presencia WHERE usuario_id = :uid AND fecha = :fecha LIMIT 1");
        $check->execute([':uid' => $usuarioId, ':fecha' => $fechaHoy]);
        $reg = $check->fetch(PDO::FETCH_ASSOC);

        if ($reg && (int)$reg["cumplio_7_horas"] === 1) {
            echo json_encode(['status' => 'ignored', 'message' => 'Jornada de Campus ya consolidada']);
            exit;
        }

        // Si no estuvo en Campus, registra Casa
        TurnoModelo::mdlActualizarTurno($usuarioId, $fechaHoy, 'casa');
        echo json_encode(['status' => 'success', 'modalidad_asignada' => 'casa']);
        exit;
    }
}

if (isset($_GET["action"]) && $_GET["action"] === "usuario_enviar_solicitud") {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION["id"])) {
        echo json_encode(["status" => "error", "message" => "Sesión vencida"]);
        exit;
    }

    $fecha = trim($_POST["fecha"] ?? "");
    $mensaje = trim($_POST["mensaje"] ?? "");

    // SOLO VALIDAMOS QUE FECHA Y MENSAJE EXISTAN
    if (empty($fecha) || empty($mensaje)) {
        echo json_encode(["status" => "error", "message" => "Datos incompletos: falta la fecha o la justificación"]);
        exit;
    }

    require_once "modelo/conexion.php";
    require_once "modelo/turnoModelo.php";

    $res = TurnoModelo::mdlCrearSolicitudAutomatica((int)$_SESSION["id"], $fecha, $mensaje);

    if ($res["status"] === "ok") {
        echo json_encode([
            "status"     => "success",
            "numero_req" => $res["numero_req"]
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => $res["message"]
        ]);
    }
    exit;
}

// En index.php tras iniciar sesión o mediante cron nocturno
function autoConsolidarTurnosOlvidados($usuarioId)
{
    $db = Conexion::conectar();
    $fechaAyer = date("Y-m-d", strtotime("-1 day"));

    // Buscar si ayer hubo primer check-in en campus pero no se cerró
    $stmt = $db->prepare(
        "SELECT rp.*, u.hora_salida_oficial 
         FROM registro_presencia rp
         INNER JOIN usuarios u ON u.id = rp.usuario_id
         WHERE rp.usuario_id = :uid 
           AND rp.fecha = :fecha 
           AND rp.cumplio_7_horas = 0 
           AND rp.sede_detectada IS NOT NULL"
    );
    $stmt->execute([':uid' => $usuarioId, ':fecha' => $fechaAyer]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        $horaEntrada = strtotime($registro["fecha"] . ' ' . $registro["hora_primera_deteccion"]);
        // Si tiene hora de salida oficial configurada, usarla; de lo contrario, asumir 7 horas después
        $horaSalidaEstimada = !empty($registro["hora_salida_oficial"])
            ? strtotime($registro["fecha"] . ' ' . $registro["hora_salida_oficial"])
            : ($horaEntrada + (7 * 3600));

        $minutos = max(0, round(($horaSalidaEstimada - $horaEntrada) / 60));

        // Si la diferencia alcanza al menos 420 minutos, convalidar
        if ($minutos >= 420) {
            $upd = $db->prepare(
                "UPDATE registro_presencia 
                 SET cumplio_7_horas = 1, 
                     minutos_campus = :min, 
                     hora_ultima_deteccion = :hora_cierre 
                 WHERE id = :id"
            );
            $upd->execute([
                ':min' => $minutos,
                ':hora_cierre' => date("H:i:s", $horaSalidaEstimada),
                ':id' => $registro["id"]
            ]);

            TurnoModelo::mdlActualizarTurno($usuarioId, $fechaAyer, 'campus');
        }
    }
}
// Cargar plantilla principal
$plantilla = new PlantillaControlador();
$plantilla->ctrTraerPlantilla();
