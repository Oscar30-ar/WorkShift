<?php
session_start();
date_default_timezone_set('America/Bogota');

require_once "modelo/conexion.php";
require_once "modelo/usuarioModelo.php";
require_once "modelo/turnoModelo.php";
require_once "controlador/plantillaControlador.php";
require_once "controlador/usuariosControlador.php";

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

// Cargar plantilla principal
$plantilla = new PlantillaControlador();
$plantilla->ctrTraerPlantilla();