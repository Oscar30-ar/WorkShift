<?php
require_once "conexion.php";

class UsuarioModelo
{
    public static function mdlBuscarPorEmail($email)
    {
        try {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function mdlBuscarPorUsername($username)
    {
        try {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE username = :user LIMIT 1");
            $stmt->bindParam(":user", $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function mdlBuscarPorId($id)
    {
        try {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function mdlBuscarCompanerosPaginado($usuarioId, $termino = "", $pagina = 1, $limite = 10)
    {
        try {
            $fechaHoy = date("Y-m-d");
            $limiteInt = max(1, (int)$limite);
            $offsetInt = max(0, (int)(($pagina - 1) * $limiteInt));

            $sql = "SELECT u.id, u.nombre, u.username, u.color_campus, u.color_casa, u.perfil_publico,
                           (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = :uid AND seguido_id = u.id) AS lo_sigo,
                           t.modalidad AS turno_hoy
                    FROM usuarios u
                    LEFT JOIN turnos t ON t.usuario_id = u.id AND t.fecha = :hoy
                    WHERE u.id != :uid_excluir";

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
            error_log("Error en mdlBuscarCompanerosPaginado: " . $e->getMessage());
            return [];
        }
    }

    public static function mdlContarCompaneros($usuarioId, $termino = "")
    {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE id != :uid";
            if (!empty($termino)) {
                $sql .= " AND (nombre LIKE :filtro_nom OR username LIKE :filtro_user)";
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
            return 0;
        }
    }
}