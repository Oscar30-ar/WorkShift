<?php
require_once "modelo/correoServicio.php";

class UsuariosControlador
{

    public function ctrRegistroUsuario()
    {
        if (isset($_POST["regEmail"])) {
            $nombre = trim($_POST["regNombre"] ?? "");
            $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST["regUsername"] ?? "")));
            $email = filter_var(trim($_POST["regEmail"] ?? ""), FILTER_SANITIZE_EMAIL);
            $password = trim($_POST["regPassword"] ?? "");
            $confirmPassword = trim($_POST["regConfirmPassword"] ?? "");

            if (empty($nombre) || empty($username) || empty($email) || empty($password)) {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">Todos los campos son obligatorios.</div>';
                return;
            }

            if ($password !== $confirmPassword) {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">Las contraseñas no coinciden.</div>';
                return;
            }

            if (strlen($password) < 6) {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">La contraseña debe tener al menos 6 caracteres.</div>';
                return;
            }

            if (UsuarioModelo::mdlBuscarPorEmail($email)) {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">Este correo ya se encuentra en uso.</div>';
                return;
            }

            if (UsuarioModelo::mdlBuscarPorNombre($nombre)) {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">Ya existe un usuario con este nombre completo.</div>';
                return;
            }

            if (UsuarioModelo::mdlBuscarPorUsername($username)) {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">El username @' . htmlspecialchars($username) . ' ya está en uso.</div>';
                return;
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            if (UsuarioModelo::mdlRegistrarUsuario($nombre, $username, $email, $hash)) {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-emerald-300 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">Cuenta creada exitosamente. Redirigiendo...</div>';
                echo '<script>setTimeout(function(){ window.location = "index.php?ruta=login"; }, 1400);</script>';
            }
        }
    }

    public function ctrIngresoUsuario()
    {
        if (isset($_POST["ingEmail"])) {
            $email = trim($_POST["ingEmail"]);
            $password = trim($_POST["ingPassword"]);

            // Puede loguearse por email o por username
            $usuario = filter_var($email, FILTER_VALIDATE_EMAIL)
                ? UsuarioModelo::mdlBuscarPorEmail($email)
                : UsuarioModelo::mdlBuscarPorUsername($email);

            if ($usuario && password_verify($password, $usuario["password"])) {
                $_SESSION["iniciarSesion"] = "ok";
                $_SESSION["id"] = $usuario["id"];
                $_SESSION["nombre"] = $usuario["nombre"];
                $_SESSION["username"] = $usuario["username"];
                $_SESSION["email"] = $usuario["email"];
                $_SESSION["color_campus"] = $usuario["color_campus"] ?? '#06b6d4';
                $_SESSION["color_casa"] = $usuario["color_casa"] ?? '#6366f1';
                $_SESSION["labora_festivos"] = (int)($usuario["labora_festivos"] ?? 0);

                echo '<script>window.location = "index.php?ruta=calendario";</script>';
            } else {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">Credenciales incorrectas.</div>';
            }
        }
    }

    public function ctrSolicitarRecuperacion()
    {
        if (isset($_POST["recEmail"])) {
            $email = trim($_POST["recEmail"]);
            $usuario = UsuarioModelo::mdlBuscarPorEmail($email);

            if ($usuario) {
                $token = bin2hex(random_bytes(32));
                $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));
                UsuarioModelo::mdlGuardarTokenRecuperacion($email, $token, $expira);
                CorreoServicio::enviarCodigoRecuperacion($email, $token);
            }
            // Mensaje neutro por seguridad
            echo '<div class="p-3 mb-4 text-xs font-semibold text-cyan-300 bg-cyan-500/10 border border-cyan-500/30 rounded-xl">Si el correo existe en nuestra plataforma, hemos enviado un enlace para restablecer tu contraseña.</div>';
        }
    }

    public function ctrRestablecerClave($token)
    {
        if (isset($_POST["nuevaClave"])) {
            $usuario = UsuarioModelo::mdlBuscarPorToken($token);
            if ($usuario) {
                $hash = password_hash(trim($_POST["nuevaClave"]), PASSWORD_BCRYPT);
                UsuarioModelo::mdlRestablecerClave($usuario["id"], $hash);
                echo '<div class="p-3 mb-4 text-xs font-semibold text-emerald-300 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">Contraseña actualizada. Inicia sesión con tus nuevos datos.</div>';
                echo '<script>setTimeout(function(){ window.location = "index.php?ruta=login"; }, 1500);</script>';
            } else {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">El enlace no es válido o ha expirado.</div>';
            }
        }
    }

    public function ctrActualizarAjustes()
    {
        if (isset($_POST["actNombre"])) {
            $nombre = trim($_POST["actNombre"]);
            $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST["actUsername"])));
            $email = filter_var(trim($_POST["actEmail"]), FILTER_SANITIZE_EMAIL);
            $colorCampus = trim($_POST["colorCampus"]);
            $colorCasa = trim($_POST["colorCasa"]);
            $perfilPublico = isset($_POST["perfilPublico"]) ? 1 : 0;
            $laboraFestivos = isset($_POST["laboraFestivos"]) ? 1 : 0;
            $passActual = trim($_POST["passActual"] ?? "");
            $passNueva = trim($_POST["passNueva"] ?? "");

            if (empty($nombre) || empty($username) || empty($email)) {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">Nombre, username y correo son obligatorios.</div>';
                return;
            }

            // Verificar si el username ya pertenece a otro
            $userCheck = UsuarioModelo::mdlBuscarPorUsername($username);
            if ($userCheck && $userCheck["id"] != $_SESSION["id"]) {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">El username @' . htmlspecialchars($username) . ' ya está en uso.</div>';
                return;
            }

            // Verificar si el email ya pertenece a otro
            $emailCheck = UsuarioModelo::mdlBuscarPorEmail($email);
            if ($emailCheck && $emailCheck["id"] != $_SESSION["id"]) {
                echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">El correo ya está registrado por otro usuario.</div>';
                return;
            }

            // Validación de cambio de contraseña
            $nuevoHash = null;
            if (!empty($passNueva)) {
                $stmtPass = Conexion::conectar()->prepare("SELECT password FROM usuarios WHERE id = :id");
                $stmtPass->execute([":id" => $_SESSION["id"]]);
                $hashActualBD = $stmtPass->fetchColumn();

                if (!password_verify($passActual, $hashActualBD)) {
                    echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">La contraseña actual no es correcta.</div>';
                    return;
                }

                if (strlen($passNueva) < 6) {
                    echo '<div class="p-3 mb-4 text-xs font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/30 rounded-xl">La nueva contraseña debe tener al menos 6 caracteres.</div>';
                    return;
                }

                $nuevoHash = password_hash($passNueva, PASSWORD_BCRYPT);
            }

            $ok = UsuarioModelo::mdlActualizarPerfilCompleto(
                $_SESSION["id"],
                $nombre,
                $username,
                $email,
                $colorCampus,
                $colorCasa,
                $perfilPublico,
                $laboraFestivos,
                $nuevoHash
            );

            if ($ok) {
                $_SESSION["nombre"] = $nombre;
                $_SESSION["username"] = $username;
                $_SESSION["email"] = $email;
                $_SESSION["color_campus"] = $colorCampus;
                $_SESSION["color_casa"] = $colorCasa;
                $_SESSION["labora_festivos"] = $laboraFestivos;
                echo '<div class="p-3 mb-4 text-xs font-semibold text-emerald-300 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">Datos actualizados exitosamente.</div>';
            }
        }
    }

    public static function ctrCerrarSesion()
    {
        session_destroy();
        echo '<script>window.location = "index.php?ruta=login";</script>';
    }
}