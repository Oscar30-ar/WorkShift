<?php
require_once "modelo/conexion.php";

class FestivosHelper
{
    public static function getFestivosColombia($anio)
    {
        $anio = (int)$anio;
        $festivos = [];

        // 1. Festivos Fijos de Ley
        $fijos = [
            "01-01" => "Año Nuevo",
            "05-01" => "Día del Trabajo",
            "07-20" => "Día de la Independencia",
            "08-07" => "Batalla de Boyacá",
            "12-08" => "Inmaculada Concepción",
            "12-25" => "Navidad"
        ];
        foreach ($fijos as $md => $nombre) {
            $festivos["$anio-$md"] = $nombre;
        }

        // 2. Ley Emiliani (Traslado a lunes)
        $emiliani = [
            "01-06" => "Reyes Magos",
            "03-19" => "Día de San José",
            "06-29" => "San Pedro y San Pablo",
            "08-15" => "Asunción de la Virgen",
            "10-12" => "Día de la Raza",
            "11-01" => "Todos los Santos",
            "11-11" => "Independencia de Cartagena"
        ];
        foreach ($emiliani as $md => $nombre) {
            $festivos[self::trasladarAlSiguienteLunes("$anio-$md")] = $nombre;
        }

        // 3. Fiestas Móviles (Pascua)
        $pascua = self::calcularDomingoPascua($anio);
        $festivos[date("Y-m-d", strtotime("-3 days", $pascua))] = "Jueves Santo";
        $festivos[date("Y-m-d", strtotime("-2 days", $pascua))] = "Viernes Santo";
        $festivos[date("Y-m-d", strtotime("+43 days", $pascua))] = "Ascensión del Señor";
        $festivos[date("Y-m-d", strtotime("+64 days", $pascua))] = "Corpus Christi";
        $festivos[date("Y-m-d", strtotime("+71 days", $pascua))] = "Sagrado Corazón de Jesús";

        // 4. NUEVO: Consultar Decretos Extraordinarios / Días Cívicos en BD
        try {
            $db = Conexion::conectar();
            $checkTable = $db->query("SHOW TABLES LIKE 'festivos_personalizados'")->fetch();
            if ($checkTable) {
                $stmt = $db->prepare("SELECT fecha, descripcion FROM festivos_personalizados WHERE YEAR(fecha) = :anio");
                $stmt->execute([':anio' => $anio]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $festivos[$row["fecha"]] = $row["descripcion"];
                }
            }
        } catch (Exception $e) {
            error_log("Error cargando festivos personalizados: " . $e->getMessage());
        }

        ksort($festivos);
        return $festivos;
    }

    private static function trasladarAlSiguienteLunes($fechaStr)
    {
        $ts = strtotime($fechaStr);
        $diaSemana = (int)date("N", $ts);
        if ($diaSemana === 1) return $fechaStr;
        $dias = 8 - $diaSemana;
        return date("Y-m-d", strtotime("+$dias days", $ts));
    }

    private static function calcularDomingoPascua($anio)
    {
        if (function_exists('easter_date')) return easter_date($anio);
        $a = $anio % 19; $b = intdiv($anio, 100); $c = $anio % 100;
        $d = intdiv($b, 4); $e = $b % 4; $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3); $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4); $k = $c % 4; $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;
        return mktime(0, 0, 0, $mes, $dia, $anio);
    }
}