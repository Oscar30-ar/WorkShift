<?php
require_once "conexion.php";

class TurnoModelo {

    public static function mdlObtenerTurnosMes($usuarioId, $anio, $mes) {
        try {
            $stmt = Conexion::conectar()->prepare(
                "SELECT fecha, modalidad, nota FROM turnos 
                 WHERE usuario_id = :uid AND YEAR(fecha) = :anio AND MONTH(fecha) = :mes"
            );
            $stmt->bindParam(":uid", $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(":anio", $anio, PDO::PARAM_INT);
            $stmt->bindParam(":mes", $mes, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error mdlObtenerTurnosMes: " . $e->getMessage());
            return [];
        }
    }

    public static function mdlActualizarTurno($usuarioId, $fecha, $modalidad, $nota = null) {
        try {
            $db = Conexion::conectar();

            if ($modalidad === 'ninguno') {
                $stmt = $db->prepare("DELETE FROM turnos WHERE usuario_id = :uid AND fecha = :fecha");
                $stmt->bindParam(":uid", $usuarioId, PDO::PARAM_INT);
                $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
                return $stmt->execute();
            }

            $check = $db->prepare("SELECT id FROM turnos WHERE usuario_id = :uid AND fecha = :fecha LIMIT 1");
            $check->bindParam(":uid", $usuarioId, PDO::PARAM_INT);
            $check->bindParam(":fecha", $fecha, PDO::PARAM_STR);
            $check->execute();
            $existe = $check->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
                $stmt = $db->prepare("UPDATE turnos SET modalidad = :modalidad WHERE id = :id");
                $stmt->bindParam(":modalidad", $modalidad, PDO::PARAM_STR);
                $stmt->bindParam(":id", $existe["id"], PDO::PARAM_INT);
                return $stmt->execute();
            } else {
                $stmt = $db->prepare("INSERT INTO turnos (usuario_id, fecha, modalidad, nota) VALUES (:uid, :fecha, :modalidad, :nota)");
                $stmt->bindParam(":uid", $usuarioId, PDO::PARAM_INT);
                $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
                $stmt->bindParam(":modalidad", $modalidad, PDO::PARAM_STR);
                $stmt->bindParam(":nota", $nota, PDO::PARAM_STR);
                return $stmt->execute();
            }
        } catch (PDOException $e) {
            error_log("Error mdlActualizarTurno: " . $e->getMessage());
            return false;
        }
    }

    public static function mdlGuardarNota($usuarioId, $fecha, $nota) {
        try {
            $db = Conexion::conectar();
            $stmt = $db->prepare("UPDATE turnos SET nota = :nota WHERE usuario_id = :uid AND fecha = :fecha");
            $stmt->bindParam(":nota", $nota, PDO::PARAM_STR);
            $stmt->bindParam(":uid", $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}