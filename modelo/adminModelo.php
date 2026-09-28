<?php
require_once "conexion.php";

class AdminModelo
{

    // Métricas del Dashboard
    public static function mdlObtenerMetricas()
    {
        try {
            $db = Conexion::conectar();
            $hoy = date("Y-m-d");

            $totalUsuarios = (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE es_admin = 0 OR es_admin IS NULL")->fetchColumn();
            $enCampusHoy = (int)$db->query("SELECT COUNT(*) FROM turnos WHERE fecha = '$hoy' AND modalidad = 'campus'")->fetchColumn();
            $enCasaHoy = (int)$db->query("SELECT COUNT(*) FROM turnos WHERE fecha = '$hoy' AND modalidad = 'casa'")->fetchColumn();
            $totalAreas = (int)$db->query("SELECT COUNT(*) FROM areas")->fetchColumn();

            return [
                "usuarios" => $totalUsuarios,
                "campus_hoy" => $enCampusHoy,
                "casa_hoy" => $enCasaHoy,
                "areas" => $totalAreas
            ];
        } catch (PDOException $e) {
            error_log("Error mdlObtenerMetricas: " . $e->getMessage());
            return ["usuarios" => 0, "campus_hoy" => 0, "casa_hoy" => 0, "areas" => 0];
        }
    }

    // CRUD: Usuarios
    public static function mdlListarUsuarios()
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "SELECT u.*, a.nombre AS area_nombre, c.nombre AS cargo_nombre, c.nivel_jerarquia 
                 FROM usuarios u
                 LEFT JOIN areas a ON a.id = u.area_id
                 LEFT JOIN cargos c ON c.id = u.cargo_id
                 ORDER BY u.id DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public static function mdlGuardarUsuario($datos)
    {
        try {
            $db = Conexion::conectar();
            if (!empty($datos["id"])) {
                // Actualizar
                $sql = "UPDATE usuarios SET nombre = :nom, username = :user, email = :email, 
                        area_id = :area, cargo_id = :cargo, es_admin = :admin, labora_festivos = :festivos";
                if (!empty($datos["password"])) {
                    $sql .= ", password = :pass";
                }
                $sql .= " WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
            } else {
                // Crear
                $stmt = $db->prepare(
                    "INSERT INTO usuarios (nombre, username, email, password, area_id, cargo_id, es_admin, labora_festivos, color_campus, color_casa)
                     VALUES (:nom, :user, :email, :pass, :area, :cargo, :admin, :festivos, '#06b6d4', '#6366f1')"
                );
            }

            $stmt->bindParam(":nom", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":user", $datos["username"], PDO::PARAM_STR);
            $stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR);
            $stmt->bindParam(":area", $datos["area_id"], PDO::PARAM_INT);
            $stmt->bindParam(":cargo", $datos["cargo_id"], PDO::PARAM_INT);
            $stmt->bindParam(":admin", $datos["es_admin"], PDO::PARAM_INT);
            $stmt->bindParam(":festivos", $datos["labora_festivos"], PDO::PARAM_INT);

            if (!empty($datos["password"])) {
                $hash = password_hash($datos["password"], PASSWORD_BCRYPT);
                $stmt->bindParam(":pass", $hash, PDO::PARAM_STR);
            } elseif (empty($datos["id"])) {
                $hashDef = password_hash("WorkShift2026*", PASSWORD_BCRYPT);
                $stmt->bindParam(":pass", $hashDef, PDO::PARAM_STR);
            }

            return $stmt->execute() ? "ok" : "error";
        } catch (PDOException $e) {
            error_log("Error mdlGuardarUsuario: " . $e->getMessage());
            return "error";
        }
    }

    public static function mdlEliminarUsuario($id)
    {
        try {
            $stmt = Conexion::conectar()->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute() ? "ok" : "error";
        } catch (PDOException $e) {
            return "error";
        }
    }

    // CRUD: Áreas
    public static function mdlListarAreas()
    {
        try {
            return Conexion::conectar()->query("SELECT a.*, (SELECT COUNT(*) FROM usuarios WHERE area_id = a.id) as total_miembros FROM areas a ORDER BY a.nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    static public function mdlGuardarArea($datos)
    {
        try {
            $db = Conexion::conectar();

            if (!empty($datos["id"])) {
                $stmt = $db->prepare("UPDATE areas SET nombre = :nombre, descripcion = :descripcion WHERE id = :id");
                $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
            } else {
                $stmt = $db->prepare("INSERT INTO areas (nombre, descripcion) VALUES (:nombre, :descripcion)");
            }

            $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);

            if ($stmt->execute()) {
                return "ok";
            } else {
                $error = $stmt->errorInfo();
                return $error[2] ?? "Error al ejecutar la consulta";
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function mdlEliminarArea($id)
    {
        try {
            $stmt = Conexion::conectar()->prepare("DELETE FROM areas WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute() ? "ok" : "error";
        } catch (PDOException $e) {
            return "error";
        }
    }

    // --- CARGOS & JERARQUÍAS (Sin descripción) ---
    public static function mdlListarCargos()
    {
        try {
            $stmt = Conexion::conectar()->query(
                "SELECT c.id, c.nombre, c.nivel_jerarquia, 
                        (SELECT COUNT(*) FROM usuarios WHERE cargo_id = c.id) as total_asignados 
                 FROM cargos c 
                 ORDER BY c.nivel_jerarquia ASC, c.nombre ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error mdlListarCargos: " . $e->getMessage());
            return [];
        }
    }
    public static function mdlGuardarCargo($datos)
    {
        try {
            $db = Conexion::conectar();
            if (!empty($datos["id"])) {
                $stmt = $db->prepare(
                    "UPDATE cargos SET nombre = :nom, nivel_jerarquia = :nivel WHERE id = :id"
                );
                $stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO cargos (nombre, nivel_jerarquia) VALUES (:nom, :nivel)"
                );
            }
            $stmt->bindParam(":nom", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":nivel", $datos["nivel_jerarquia"], PDO::PARAM_INT);
            return $stmt->execute() ? "ok" : "error";
        } catch (PDOException $e) {
            error_log("Error mdlGuardarCargo: " . $e->getMessage());
            return "error";
        }
    }

    public static function mdlEliminarCargo($id)
    {
        try {
            $db = Conexion::conectar();
            // Desvincular usuarios asignados para evitar violaciones de clave foránea
            $reset = $db->prepare("UPDATE usuarios SET cargo_id = 1 WHERE cargo_id = :id");
            $reset->bindParam(":id", $id, PDO::PARAM_INT);
            $reset->execute();

            $stmt = $db->prepare("DELETE FROM cargos WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute() ? "ok" : "error";
        } catch (PDOException $e) {
            error_log("Error mdlEliminarCargo: " . $e->getMessage());
            return "error";
        }
    }

    // --- REPORTES Y AUDITORÍA PARA PDF ---
    public static function mdlObtenerDatosReporteUsuario($usuarioId, $mes, $anio)
    {
        try {
            $db = Conexion::conectar();

            // 1. Obtener datos del usuario
            $stmtUser = $db->prepare(
                "SELECT u.*, 
                        COALESCE(a.nombre, 'Operaciones & TI') AS area_nombre, 
                        COALESCE(c.nombre, 'Analista') AS cargo_nombre, 
                        COALESCE(c.nivel_jerarquia, 1) AS nivel_jerarquia
                 FROM usuarios u
                 LEFT JOIN areas a ON a.id = u.area_id
                 LEFT JOIN cargos c ON c.id = u.cargo_id
                 WHERE u.id = :uid 
                 LIMIT 1"
            );
            $stmtUser->bindValue(':uid', (int)$usuarioId, PDO::PARAM_INT);
            $stmtUser->execute();
            $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $stmtFallback = $db->prepare("SELECT * FROM usuarios WHERE id = :uid LIMIT 1");
                $stmtFallback->bindValue(':uid', (int)$usuarioId, PDO::PARAM_INT);
                $stmtFallback->execute();
                $usuario = $stmtFallback->fetch(PDO::FETCH_ASSOC);

                if (!$usuario) return null;

                $usuario['area_nombre'] = 'Operaciones';
                $usuario['cargo_nombre'] = 'Analista';
                $usuario['nivel_jerarquia'] = 1;
            }

            // 2. Rango de fechas
            $mesInt = (int)$mes;
            $anioInt = (int)$anio;
            $fechaInicio = sprintf("%04d-%02d-01", $anioInt, $mesInt);
            $fechaFin = date("Y-m-t", strtotime($fechaInicio));

            // 3. Obtener turnos con soporte de justificación y 7 horas
            $stmtTurnos = $db->prepare(
                "SELECT t.fecha, t.modalidad, t.nota, 
                        t.horas_computadas, t.justificado_por, t.motivo_justificacion,
                        rp.hora_primera_deteccion, rp.hora_ultima_deteccion, 
                        COALESCE(rp.minutos_campus, t.horas_computadas, 0) AS minutos_campus,
                        COALESCE(rp.cumplio_7_horas, CASE WHEN t.horas_computadas >= 420 THEN 1 ELSE 0 END, 0) AS cumplio_7_horas,
                        rp.sede_detectada
                 FROM turnos t
                 LEFT JOIN registro_presencia rp ON rp.usuario_id = t.usuario_id AND rp.fecha = t.fecha
                 WHERE t.usuario_id = :uid 
                   AND t.fecha >= :inicio 
                   AND t.fecha <= :fin
                 ORDER BY t.fecha ASC"
            );
            $stmtTurnos->bindValue(':uid', (int)$usuarioId, PDO::PARAM_INT);
            $stmtTurnos->bindValue(':inicio', $fechaInicio, PDO::PARAM_STR);
            $stmtTurnos->bindValue(':fin', $fechaFin, PDO::PARAM_STR);
            $stmtTurnos->execute();
            $turnos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

            return [
                "usuario" => $usuario,
                "turnos"  => $turnos ? $turnos : [],
                "mes"     => $mesInt,
                "anio"    => $anioInt
            ];
        } catch (PDOException $e) {
            error_log("Error crítico en mdlObtenerDatosReporteUsuario: " . $e->getMessage());
            return null;
        }
    }
    // Auditoría Integral de Turnos
    public static function mdlListarAuditoriaTurnos($limite = 150)
    {
        try {
            $db = Conexion::conectar();
            $stmt = $db->prepare(
                "SELECT t.id, t.usuario_id, t.fecha, t.modalidad, t.nota,
                        t.horas_computadas, t.justificado_por, t.motivo_justificacion,
                        u.nombre, u.username,
                        COALESCE(a.nombre, 'General') AS area_nombre,
                        rp.hora_primera_deteccion, rp.hora_ultima_deteccion,
                        COALESCE(rp.minutos_campus, t.horas_computadas, 0) AS minutos_campus,
                        COALESCE(rp.cumplio_7_horas, CASE WHEN t.horas_computadas >= 420 THEN 1 ELSE 0 END) AS cumplio_7_horas
                 FROM turnos t
                 INNER JOIN usuarios u ON u.id = t.usuario_id
                 LEFT JOIN areas a ON a.id = u.area_id
                 LEFT JOIN registro_presencia rp ON rp.usuario_id = t.usuario_id AND rp.fecha = t.fecha
                 ORDER BY t.fecha DESC, t.id DESC
                 LIMIT :limite"
            );
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en mdlListarAuditoriaTurnos: " . $e->getMessage());
            return [];
        }
    }
    // Listar festivos por decreto registrados
    public static function mdlListarFestivosDecretos()
    {
        try {
            return Conexion::conectar()->query(
                "SELECT * FROM festivos_personalizados ORDER BY fecha DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    // Listar solicitudes pendientes o todas para la Consola Admin
    public static function mdlListarSolicitudesNovedades()
    {
        try {
            $db = Conexion::conectar();
            $stmt = $db->query(
                "SELECT s.*, u.nombre, u.username, COALESCE(a.nombre, 'General') AS area_nombre,
                        sup.nombre AS supervisor_nombre
                 FROM solicitudes_novedades s
                 INNER JOIN usuarios u ON u.id = s.usuario_id
                 LEFT JOIN areas a ON a.id = u.area_id
                 LEFT JOIN usuarios sup ON sup.id = s.revisado_por
                 ORDER BY CASE WHEN s.estado = 'pendiente' THEN 1 ELSE 2 END, s.created_at DESC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error mdlListarSolicitudesNovedades: " . $e->getMessage());
            return [];
        }
    }

    // Listar solicitudes propias de un colaborador ("Mis Solicitudes")
    public static function mdlListarMisSolicitudes($usuarioId)
    {
        try {
            $db = Conexion::conectar();
            $stmt = $db->prepare(
                "SELECT s.*, sup.nombre AS supervisor_nombre
                 FROM solicitudes_novedades s
                 LEFT JOIN usuarios sup ON sup.id = s.revisado_por
                 WHERE s.usuario_id = :uid
                 ORDER BY s.created_at DESC"
            );
            $stmt->execute([':uid' => (int)$usuarioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error mdlListarMisSolicitudes: " . $e->getMessage());
            return [];
        }
    }

    // Contar pendientes para badges y alertas
    public static function mdlContarSolicitudesPendientes()
    {
        try {
            return (int)Conexion::conectar()->query(
                "SELECT COUNT(*) FROM solicitudes_novedades WHERE estado = 'pendiente'"
            )->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

}
