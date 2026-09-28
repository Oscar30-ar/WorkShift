<?php
require_once "conexion.php";

class TurnoModelo
{
    public static function mdlObtenerTurnosMes($usuarioId, $anio, $mes)
    {
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

    public static function mdlActualizarTurno($usuarioId, $fecha, $modalidad, $nota = null)
    {
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

    public static function mdlGuardarNota($usuarioId, $fecha, $nota)
    {
        try {
            $db = Conexion::conectar();
            $stmt = $db->prepare("UPDATE turnos SET nota = :nota WHERE usuario_id = :uid AND fecha = :fecha");
            $stmt->bindParam(":nota", $nota, PDO::PARAM_STR);
            $stmt->bindParam(":uid", $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(":fecha", $fecha, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error mdlGuardarNota: " . $e->getMessage());
            return false;
        }
    }

    /*=============================================
CREAR SOLICITUD DE CONVALIDACIÓN (RADICADO ATÓMICO)
=============================================*/
    static public function mdlCrearSolicitudConvalidacion($datos)
    {
        $db = Conexion::conectar();
        try {
            $db->beginTransaction();

            // 1. Inserta la solicitud básica
            $stmt = $db->prepare(
                "INSERT INTO solicitudes_novedades 
             (usuario_id, fecha, id_ticket, mensaje, estado) 
             VALUES (:usuario_id, :fecha, :id_ticket, :mensaje, 'pendiente')"
            );

            $stmt->bindParam(":usuario_id", $datos["usuario_id"], PDO::PARAM_INT);
            $stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
            $stmt->bindParam(":id_ticket", $datos["id_ticket"], PDO::PARAM_STR);
            $stmt->bindParam(":mensaje", $datos["mensaje"], PDO::PARAM_STR);
            $stmt->execute();

            // 2. Obtiene el ID generado por la base de datos
            $nuevoId = (int)$db->lastInsertId();

            // 3. Concatena el prefijo 'REQ' garantizando que no se repita
            $numeroReq = "REQ" . (1000000 + $nuevoId);

            // 4. Asigna el número a la fila
            $stmtUpdate = $db->prepare("UPDATE solicitudes_novedades SET numero_req = :numero_req WHERE id = :id");
            $stmtUpdate->bindParam(":numero_req", $numeroReq, PDO::PARAM_STR);
            $stmtUpdate->bindParam(":id", $nuevoId, PDO::PARAM_INT);
            $stmtUpdate->execute();

            $db->commit();

            return [
                "status" => "ok",
                "numero_req" => $numeroReq
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }
    /*=============================================
    RADICAR SOLICITUD CON NÚMERO DE REQ AUTOMÁTICO
    =============================================*/
    static public function mdlCrearSolicitudAutomatica($usuarioId, $fecha, $mensaje)
    {
        $db = Conexion::conectar();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "INSERT INTO solicitudes_novedades 
             (usuario_id, fecha_turno, ticket_soporte, mensaje_usuario, estado) 
             VALUES (:usuario_id, :fecha_turno, 'PENDIENTE', :mensaje_usuario, 'pendiente')"
            );

            $stmt->bindParam(":usuario_id", $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(":fecha_turno", $fecha, PDO::PARAM_STR);
            $stmt->bindParam(":mensaje_usuario", $mensaje, PDO::PARAM_STR);
            $stmt->execute();

            $nuevoId = (int)$db->lastInsertId();
            $numeroReq = "REQ" . (1000000 + $nuevoId);

            $update = $db->prepare("UPDATE solicitudes_novedades SET ticket_soporte = :ticket WHERE id = :id");
            $update->bindParam(":ticket", $numeroReq, PDO::PARAM_STR);
            $update->bindParam(":id", $nuevoId, PDO::PARAM_INT);
            $update->execute();

            $db->commit();

            return [
                "status"     => "ok",
                "numero_req" => $numeroReq
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return [
                "status"  => "error",
                "message" => "Error BD: " . $e->getMessage()
            ];
        }
    }
}
