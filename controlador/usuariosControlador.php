<?php

class UsuariosControlador
{
    // Manejo de Registro Tradicional
    public function ctrRegistroUsuario()
    {
        if (isset($_POST["nuevoNombre"]) && isset($_POST["nuevoEmail"])) {
            if (
                preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoNombre"]) &&
                preg_match('/^[a-zA-Z0-9_]+$/', $_POST["nuevoUsername"]) &&
                filter_var($_POST["nuevoEmail"], FILTER_VALIDATE_EMAIL)
            ) {
                // Comprobar que no exista email ni username
                $existeEmail = UsuarioModelo::mdlBuscarPorEmail($_POST["nuevoEmail"]);
                $existeUser = UsuarioModelo::mdlBuscarPorUsername($_POST["nuevoUsername"]);

                if ($existeEmail) {
                    echo '<div class="mt-3 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs text-center">Este correo ya está registrado.</div>';
                    return;
                }

                if ($existeUser) {
                    echo '<div class="mt-3 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs text-center">Ese nombre de usuario (@username) ya está en uso.</div>';
                    return;
                }

                $passEncriptado = password_hash($_POST["nuevoPassword"], PASSWORD_BCRYPT);

                $datos = [
                    "nombre" => trim($_POST["nuevoNombre"]),
                    "username" => strtolower(trim($_POST["nuevoUsername"])),
                    "email" => strtolower(trim($_POST["nuevoEmail"])),
                    "password" => $passEncriptado,
                    "area_id" => 1,
                    "cargo_id" => 1
                ];

                $respuesta = UsuarioModelo::mdlRegistrarUsuario($datos);

                if ($respuesta === "ok") {
                    $usuarioNuevo = UsuarioModelo::mdlBuscarPorEmail($datos["email"]);

                    $_SESSION["iniciarSesion"] = "ok";
                    $_SESSION["id"] = $usuarioNuevo["id"];
                    $_SESSION["nombre"] = $usuarioNuevo["nombre"];
                    $_SESSION["username"] = $usuarioNuevo["username"];
                    $_SESSION["email"] = $usuarioNuevo["email"];
                    $_SESSION["color_campus"] = $usuarioNuevo["color_campus"] ?? '#06b6d4';
                    $_SESSION["color_casa"] = $usuarioNuevo["color_casa"] ?? '#6366f1';
                    $_SESSION["labora_festivos"] = (int)($usuarioNuevo["labora_festivos"] ?? 0);
                    $_SESSION["area_id"] = (int)($usuarioNuevo["area_id"] ?? 1);
                    $_SESSION["cargo_id"] = (int)($usuarioNuevo["cargo_id"] ?? 1);
                    $_SESSION["cargo_nombre"] = $usuarioNuevo["cargo_nombre"] ?? "Analista";
                    $_SESSION["es_admin"] = (int)($usuarioNuevo["es_admin"] ?? 0);

                    echo '<script>window.location = "index.php?ruta=calendario";</script>';
                } else {
                    echo '<div class="mt-3 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs text-center">Ocurrió un error al registrar la cuenta.</div>';
                }
            } else {
                echo '<div class="mt-3 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs text-center">Caracteres inválidos en los campos.</div>';
            }
        }
    }

    /*=============================================
    SOLICITAR RECUPERACIÓN DE CONTRASEÑA
    =============================================*/
    static public function ctrSolicitarRecuperacion()
    {
        if (isset($_POST["recuperarEmail"])) {

            $email = trim($_POST["recuperarEmail"]);

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                // 1. Verificar si el correo existe en la BD
                $usuario = UsuarioModelo::mdlMostrarUsuarioPorEmail("usuarios", $email);

                if ($usuario) {

                    // 2. Generar token único y fecha de expiración (1 hora)
                    $token = bin2hex(random_bytes(32));
                    $expiracion = date("Y-m-d H:i:s", strtotime("+1 hour"));

                    // 3. Guardar el token en la BD
                    $guardarToken = UsuarioModelo::mdlGuardarTokenRecuperacion("usuarios", $usuario["id"], $token, $expiracion);

                    if ($guardarToken === "ok") {

                        // 4. Construir enlace de restablecimiento dinámico
                        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                        $host = $_SERVER['HTTP_HOST'];
                        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
                        $baseUrl = rtrim($protocolo . $host . $scriptDir, '/\\');
                        $enlace = $baseUrl . "/index.php?ruta=recuperar&token=" . $token;

                        // 5. Preparar correo de recuperación
                        $asunto = "=?UTF-8?B?" . base64_encode("Restablecer tu contraseña - WorkShift") . "?=";

                        $mensaje = '
                        <!DOCTYPE html>
                        <html lang="es">
                        <head>
                            <meta charset="UTF-8">
                            <style>
                                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #020617; color: #f8fafc; padding: 20px; }
                                .card { max-width: 480px; margin: 0 auto; background-color: #0f172a; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); padding: 32px; text-align: center; }
                                .btn { display: inline-block; background-color: #06b6d4; color: #020617; font-weight: bold; text-decoration: none; padding: 12px 24px; border-radius: 12px; margin-top: 20px; }
                                .footer { margin-top: 24px; font-size: 11px; color: #64748b; }
                            </style>
                        </head>
                        <body>
                            <div class="card">
                                <h2 style="color: #ffffff; margin-bottom: 8px;">Work<span style="color: #06b6d4;">Shift</span></h2>
                                <p style="color: #94a3b8; font-size: 14px;">Solicitud de restablecimiento de contraseña</p>
                                <p style="color: #cbd5e1; font-size: 13px; line-height: 1.5; margin-top: 16px;">
                                    Hola <strong>' . htmlspecialchars($usuario["nombre"]) . '</strong>, recibimos una solicitud para restablecer la contraseña de tu cuenta.
                                </p>
                                <a href="' . $enlace . '" class="btn">Restablecer Contraseña</a>
                                <p style="color: #64748b; font-size: 12px; margin-top: 20px;">
                                    Este enlace expirará en 60 minutos.<br>Si no solicitaste este cambio, puedes ignorar este mensaje con seguridad.
                                </p>
                                <div class="footer">WorkShift Compliance & Security System</div>
                            </div>
                        </body>
                        </html>';

                        $headers  = "MIME-Version: 1.0\r\n";
                        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                        $headers .= "From: WorkShift Support <no-reply@" . parse_url($baseUrl, PHP_URL_HOST) . ">\r\n";

                        @mail($email, $asunto, $mensaje, $headers);

                        echo '<script>
                            Swal.fire({
                                icon: "success",
                                title: "Enlace enviado",
                                text: "Si el correo coincide con una cuenta activa, te hemos enviado las instrucciones.",
                                background: "#0f172a",
                                color: "#f8fafc",
                                confirmButtonColor: "#06b6d4"
                            });
                        </script>';
                    } else {
                        echo '<script>
                            Swal.fire({
                                icon: "error",
                                title: "Error en el servidor",
                                text: "No se pudo generar el token. Intenta nuevamente.",
                                background: "#0f172a",
                                color: "#f8fafc",
                                confirmButtonColor: "#06b6d4"
                            });
                        </script>';
                    }
                } else {
                    // Mensaje neutro por seguridad (evita enumeración de usuarios)
                    echo '<script>
                        Swal.fire({
                            icon: "success",
                            title: "Enlace enviado",
                            text: "Si el correo coincide con una cuenta activa, te hemos enviado las instrucciones.",
                            background: "#0f172a",
                            color: "#f8fafc",
                            confirmButtonColor: "#06b6d4"
                        });
                    </script>';
                }
            } else {
                echo '<script>
                    Swal.fire({
                        icon: "warning",
                        title: "Formato inválido",
                        text: "Por favor ingresa un correo electrónico válido.",
                        background: "#0f172a",
                        color: "#f8fafc",
                        confirmButtonColor: "#06b6d4"
                    });
                </script>';
            }
        }
    }

    // Inicio de Sesión Tradicional
    public function ctrIngresoUsuario()
    {
        if (isset($_POST["ingEmail"]) && isset($_POST["ingPassword"])) {
            $ingreso = trim($_POST["ingEmail"]);

            if (filter_var($ingreso, FILTER_VALIDATE_EMAIL)) {
                $usuario = UsuarioModelo::mdlBuscarPorEmail($ingreso);
            } else {
                $usuario = UsuarioModelo::mdlBuscarPorUsername(str_replace('@', '', $ingreso));
            }

            if ($usuario && password_verify($_POST["ingPassword"], $usuario["password"])) {
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
                $_SESSION["cargo_nombre"] = $usuario["cargo_nombre"] ?? "Analista";
                $_SESSION["es_admin"] = (int)($usuario["es_admin"] ?? 0);

                echo '<script>window.location = "index.php?ruta=calendario";</script>';
            } else {
                echo '<div class="mt-3 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs text-center">Credenciales incorrectas. Verifica correo y contraseña.</div>';
            }
        }
    }

    // Cierre de Sesión
    public static function ctrCerrarSesion()
    {
        session_destroy();
        echo '<script>window.location = "index.php?ruta=login";</script>';
    }

    // Actualizar configuración personal del usuario
    // Actualizar configuración personal del usuario
    public function ctrActualizarAjustes()
    {
        if (isset($_POST["actualizarAjustes"])) {
            $idUsuario = (int)$_SESSION["id"];

            $nombre = trim($_POST["nombre"] ?? "");
            $username = strtolower(trim($_POST["username"] ?? ""));
            $colorCampus = $_POST["color_campus"] ?? '#06b6d4';
            $colorCasa = $_POST["color_casa"] ?? '#6366f1';
            $areaId = !empty($_POST["area_id"]) ? (int)$_POST["area_id"] : (int)($_SESSION["area_id"] ?? 1);
            $direccionCasa = trim($_POST["direccion_casa"] ?? "");
            $perfilPublico = isset($_POST["perfil_publico"]) ? 1 : 0;
            $laboraFestivos = isset($_POST["labora_festivos"]) ? 1 : 0;
            $nuevoPass = !empty($_POST["nuevo_password"]) ? trim($_POST["nuevo_password"]) : null;

            // Validar que el username no esté en uso por otra persona
            if (!empty($username)) {
                $usuarioExiste = UsuarioModelo::mdlBuscarPorUsername($username);
                if ($usuarioExiste && (int)$usuarioExiste["id"] !== $idUsuario) {
                    echo '<div class="mb-4 p-3 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs text-center flex items-center justify-center gap-2">
                        <i class="fa-solid fa-circle-exclamation"></i> El nombre de usuario @' . htmlspecialchars($username) . ' ya está ocupado.
                    </div>';
                    return;
                }
            }

            $datos = [
                "id" => $idUsuario,
                "nombre" => $nombre,
                "username" => $username,
                "color_campus" => $colorCampus,
                "color_casa" => $colorCasa,
                "direccion_casa" => $direccionCasa,
                "perfil_publico" => $perfilPublico,
                "labora_festivos" => $laboraFestivos,
                "password" => $nuevoPass ? password_hash($nuevoPass, PASSWORD_BCRYPT) : null
            ];

            $respuesta = UsuarioModelo::mdlActualizarAjustes($datos);

            if ($respuesta === "ok") {
                // Sincronizar la sesión activa de inmediato
                $_SESSION["nombre"] = $nombre;
                $_SESSION["username"] = $username;
                $_SESSION["color_campus"] = $colorCampus;
                $_SESSION["color_casa"] = $colorCasa;
                $_SESSION["labora_festivos"] = $laboraFestivos;

                echo '<script>window.location = "index.php?ruta=ajustes&msg=ok";</script>';
                exit;
            } else {
                echo '<div class="mb-4 p-3 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs text-center flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i> Ocurrió un error al guardar los cambios.
                </div>';
            }
        }
    }
}
