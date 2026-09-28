<?php
require_once "conexion.php";

class UsuarioModelo
{
    public static function mdlBuscarPorEmail($email)
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "SELECT u.*, a.nombre AS area_nombre, c.nombre AS cargo_nombre, c.nivel_jerarquia 
                 FROM usuarios u 
                 LEFT JOIN areas a ON a.id = u.area_id
                 LEFT JOIN cargos c ON c.id = u.cargo_id
                 WHERE u.email = :email LIMIT 1"
            );
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error mdlBuscarPorEmail: " . $e->getMessage());
            return false;
        }
    }

    public static function mdlBuscarPorUsername($username)
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "SELECT u.*, a.nombre AS area_nombre, c.nombre AS cargo_nombre, c.nivel_jerarquia 
                 FROM usuarios u 
                 LEFT JOIN areas a ON a.id = u.area_id
                 LEFT JOIN cargos c ON c.id = u.cargo_id
                 WHERE u.username = :user LIMIT 1"
            );
            $stmt->bindParam(":user", $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error mdlBuscarPorUsername: " . $e->getMessage());
            return false;
        }
    }

    public static function mdlBuscarPorNombre($nombre)
    {
        try {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE nombre = :nom LIMIT 1");
            $stmt->bindParam(":nom", $nombre, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error mdlBuscarPorNombre: " . $e->getMessage());
            return false;
        }
    }

    public static function mdlBuscarPorId($id)
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "SELECT u.*, a.nombre AS area_nombre, c.nombre AS cargo_nombre, c.nivel_jerarquia 
                 FROM usuarios u 
                 LEFT JOIN areas a ON a.id = u.area_id
                 LEFT JOIN cargos c ON c.id = u.cargo_id
                 WHERE u.id = :id LIMIT 1"
            );
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error mdlBuscarPorId: " . $e->getMessage());
            return false;
        }
    }

    public static function mdlRegistrarUsuario($datos)
    {
        try {
            $db = Conexion::conectar();
            $stmt = $db->prepare(
                "INSERT INTO usuarios (nombre, username, email, password, pass_temporal, color_campus, color_casa, perfil_publico, labora_festivos, area_id, cargo_id, es_admin) 
                 VALUES (:nombre, :username, :email, :password, 0, '#06b6d4', '#6366f1', 1, 0, :area_id, :cargo_id, 0)"
            );

            $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":username", $datos["username"], PDO::PARAM_STR);
            $stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR);
            $stmt->bindParam(":password", $datos["password"], PDO::PARAM_STR);

            $areaId = !empty($datos["area_id"]) ? (int)$datos["area_id"] : 1;
            $cargoId = !empty($datos["cargo_id"]) ? (int)$datos["cargo_id"] : 1;
            $stmt->bindParam(":area_id", $areaId, PDO::PARAM_INT);
            $stmt->bindParam(":cargo_id", $cargoId, PDO::PARAM_INT);

            return $stmt->execute() ? "ok" : "error";
        } catch (PDOException $e) {
            error_log("Error mdlRegistrarUsuario: " . $e->getMessage());
            return "error";
        }
    }

    public static function mdlBuscarCompanerosPaginado($usuarioId, $termino = "", $tab = "todos", $pagina = 1, $limite = 10)
    {
        try {
            $fechaHoy = date("Y-m-d");
            $limiteInt = max(1, (int)$limite);
            $offsetInt = max(0, (int)(($pagina - 1) * $limiteInt));

            $sql = "SELECT u.id, u.nombre, u.username, u.color_campus, u.color_casa, u.perfil_publico,
                           u.area_id, u.cargo_id, a.nombre AS area_nombre, c.nombre AS cargo_nombre, c.nivel_jerarquia,
                           (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = :uid AND seguido_id = u.id) AS lo_sigo,
                           (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = u.id AND seguido_id = :uid) AS me_sigue,
                           t.modalidad AS turno_hoy
                    FROM usuarios u
                    LEFT JOIN areas a ON a.id = u.area_id
                    LEFT JOIN cargos c ON c.id = u.cargo_id
                    LEFT JOIN turnos t ON t.usuario_id = u.id AND t.fecha = :hoy
                    WHERE u.id != :uid_excluir 
                      AND (u.es_admin IS NULL OR u.es_admin = 0)";

            if ($tab === "siguiendo") {
                $sql .= " AND EXISTS (SELECT 1 FROM seguidores WHERE seguidor_id = :uid AND seguido_id = u.id)";
            } elseif ($tab === "seguidores") {
                $sql .= " AND EXISTS (SELECT 1 FROM seguidores WHERE seguidor_id = u.id AND seguido_id = :uid)";
            }

            if (!empty($termino)) {
                $sql .= " AND (u.nombre LIKE :filtro_nom OR u.username LIKE :filtro_user)";
            }

            $sql .= " ORDER BY lo_sigo DESC, u.nombre ASC LIMIT " . $limiteInt . " OFFSET " . $offsetInt;

            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindValue(":uid", (int)$usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(":hoy", $fechaHoy, PDO::PARAM_STR);
            $stmt->bindValue(":uid_excluir", (int)$usuarioId, PDO::PARAM_INT);

            if (!empty($termino)) {
                $likeVal = "%" . $termino . "%";
                $stmt->bindValue(":filtro_nom", $likeVal, PDO::PARAM_STR);
                $stmt->bindValue(":filtro_user", $likeVal, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error mdlBuscarCompanerosPaginado: " . $e->getMessage());
            return [];
        }
    }

    public static function mdlContarCompaneros($usuarioId, $termino = "", $tab = "todos")
    {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios u WHERE u.id != :uid AND (u.es_admin IS NULL OR u.es_admin = 0)";

            if ($tab === "siguiendo") {
                $sql .= " AND EXISTS (SELECT 1 FROM seguidores WHERE seguidor_id = :uid AND seguido_id = u.id)";
            } elseif ($tab === "seguidores") {
                $sql .= " AND EXISTS (SELECT 1 FROM seguidores WHERE seguidor_id = u.id AND seguido_id = :uid)";
            }

            if (!empty($termino)) {
                $sql .= " AND (u.nombre LIKE :filtro_nom OR u.username LIKE :filtro_user)";
            }

            $stmt = Conexion::conectar()->prepare($sql);
            $stmt->bindValue(":uid", (int)$usuarioId, PDO::PARAM_INT);

            if (!empty($termino)) {
                $likeVal = "%" . $termino . "%";
                $stmt->bindValue(":filtro_nom", $likeVal, PDO::PARAM_STR);
                $stmt->bindValue(":filtro_user", $likeVal, PDO::PARAM_STR);
            }

            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error mdlContarCompaneros: " . $e->getMessage());
            return 0;
        }
    }

    public static function mdlContadoresSociales($usuarioId)
    {
        try {
            $db = Conexion::conectar();
            $siguiendo = $db->query("SELECT COUNT(*) FROM seguidores WHERE seguidor_id = " . (int)$usuarioId)->fetchColumn();
            $seguidores = $db->query("SELECT COUNT(*) FROM seguidores WHERE seguido_id = " . (int)$usuarioId)->fetchColumn();
            return ['siguiendo' => (int)$siguiendo, 'seguidores' => (int)$seguidores];
        } catch (Exception $e) {
            return ['siguiendo' => 0, 'seguidores' => 0];
        }
    }

    public static function mdlActualizarAjustes($datos)
    {
        try {
            $db = Conexion::conectar();

            $sql = "UPDATE usuarios SET 
                        nombre = :nombre,
                        username = :username,
                        color_campus = :campus, 
                        color_casa = :casa, 
                        direccion_casa = :dir_casa,
                        perfil_publico = :publico, 
                        labora_festivos = :festivos";

            if (!empty($datos["password"])) {
                $sql .= ", password = :pass";
            }

            $sql .= " WHERE id = :id";

            $stmt = $db->prepare($sql);

            $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":username", $datos["username"], PDO::PARAM_STR);
            $stmt->bindParam(":campus", $datos["color_campus"], PDO::PARAM_STR);
            $stmt->bindParam(":casa", $datos["color_casa"], PDO::PARAM_STR);
            $stmt->bindParam(":dir_casa", $datos["direccion_casa"], PDO::PARAM_STR);
            $stmt->bindParam(":publico", $datos["perfil_publico"], PDO::PARAM_INT);
            $stmt->bindParam(":festivos", $datos["labora_festivos"], PDO::PARAM_INT);
            $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);

            if (!empty($datos["password"])) {
                $stmt->bindParam(":pass", $datos["password"], PDO::PARAM_STR);
            }

            return $stmt->execute() ? "ok" : "error";
        } catch (PDOException $e) {
            error_log("Error mdlActualizarAjustes: " . $e->getMessage());
            return "error";
        }
    }
    /*=============================================
    GUARDAR CÓDIGO OTP / TOKEN DE RECUPERACIÓN (POR EMAIL)
    =============================================*/
    public static function mdlGuardarCodigoOtp($email, $codigoOtp, $expiracion = null)
    {
        try {
            // Guardamos la fecha de expiración calculada
            if ($expiracion === null) {
                $expiracion = date("Y-m-d H:i:s", strtotime("+15 minutes"));
            }

            $emailLimpo = trim(strtolower($email));
            $otpLimpio = trim((string)$codigoOtp);

            $stmt = Conexion::conectar()->prepare(
                "UPDATE usuarios 
                 SET token_recuperacion = :otp, 
                     token_expira = :expira 
                 WHERE LOWER(TRIM(email)) = :email"
            );

            $stmt->bindParam(":otp", $otpLimpio, PDO::PARAM_STR);
            $stmt->bindParam(":expira", $expiracion, PDO::PARAM_STR);
            $stmt->bindParam(":email", $emailLimpo, PDO::PARAM_STR);

            return $stmt->execute() ? "ok" : "error";
        } catch (PDOException $e) {
            error_log("Error mdlGuardarCodigoOtp: " . $e->getMessage());
            return "error";
        }
    }

    /*=============================================
    VERIFICAR CÓDIGO OTP ACTIVO (POR EMAIL)
    =============================================*/
    public static function mdlVerificarCodigoOtp($email, $codigoOtp)
    {
        try {
            $emailLimpo = trim(strtolower($email));
            $otpLimpio = trim((string)$codigoOtp);

            // Buscamos el usuario por correo y código exacto
            $stmt = Conexion::conectar()->prepare(
                "SELECT * FROM usuarios 
                 WHERE LOWER(TRIM(email)) = :email 
                   AND TRIM(token_recuperacion) = :otp 
                 LIMIT 1"
            );

            $stmt->bindParam(":email", $emailLimpo, PDO::PARAM_STR);
            $stmt->bindParam(":otp", $otpLimpio, PDO::PARAM_STR);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                return false; // El código no coincide
            }

            // Validamos la expiración en PHP usando la misma zona horaria
            if (!empty($usuario["token_expira"])) {
                $timestampExpira = strtotime($usuario["token_expira"]);
                if ($timestampExpira < time()) {
                    return false; // Expiró
                }
            }

            return $usuario;
        } catch (PDOException $e) {
            error_log("Error mdlVerificarCodigoOtp: " . $e->getMessage());
            return false;
        }
    }
    /*=============================================
    BUSCAR USUARIO POR EMAIL
    =============================================*/
    static public function mdlMostrarUsuarioPorEmail($tabla, $email)
    {
        try {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE email = :email LIMIT 1");
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    /*=============================================
    GUARDAR TOKEN DE RECUPERACIÓN Y FECHA DE EXPIRACIÓN (POR ID)
    =============================================*/
    static public function mdlGuardarTokenRecuperacion($tabla, $id, $token, $expiracion)
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "UPDATE $tabla 
                 SET token_recuperacion = :token, 
                     token_expiracion = :expiracion 
                 WHERE id = :id"
            );

            $stmt->bindParam(":token", $token, PDO::PARAM_STR);
            $stmt->bindParam(":expiracion", $expiracion, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);

            return $stmt->execute() ? "ok" : "error";
        } catch (Exception $e) {
            return "error";
        }
    }

    /*=============================================
    VERIFICAR TOKEN VÁLIDO (QUE COINCIDA Y NO HAYA EXPIRADO)
    =============================================*/
    static public function mdlVerificarTokenRecuperacion($tabla, $token)
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "SELECT * FROM $tabla 
                 WHERE token_recuperacion = :token 
                   AND token_expiracion >= NOW() 
                 LIMIT 1"
            );

            $stmt->bindParam(":token", $token, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    /*=============================================
    ACTUALIZAR CONTRASEÑA Y LIMPIAR TOKEN USADO
    =============================================*/
    public static function mdlActualizarPasswordRecuperada($tabla, $id, $nuevoPasswordHash)
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "UPDATE $tabla 
                 SET password = :password, 
                     pass_temporal = 0,
                     token_recuperacion = NULL, 
                     token_expira = NULL 
                 WHERE id = :id"
            );

            $stmt->bindParam(":password", $nuevoPasswordHash, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar password: " . $e->getMessage());
            return false;
        }
    }
}
