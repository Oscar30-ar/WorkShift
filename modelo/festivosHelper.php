<?php
class FestivosHelper {
    public static function obtenerFestivosAnio($anio) {
        // Festivos de fecha fija (Mes => Día)
        $fijos = [
            '01-01' => 'Año Nuevo',
            '05-01' => 'Día del Trabajo',
            '07-20' => 'Grito de Independencia',
            '08-07' => 'Batalla de Boyacá',
            '12-08' => 'Inmaculada Concepción',
            '12-25' => 'Navidad'
        ];

        // Festivos que se trasladan al siguiente lunes (Ley Emiliani)
        $trasladables = [
            '01-06' => 'Reyes Magos',
            '03-19' => 'San José',
            '06-29' => 'San Pedro y San Pablo',
            '08-15' => 'Asunción de la Virgen',
            '10-12' => 'Día de la Raza',
            '11-01' => 'Todos los Santos',
            '11-11' => 'Independencia de Cartagena'
        ];

        $festivos = [];

        // 1. Agregar fijos
        foreach ($fijos as $fecha => $nombre) {
            $festivos["$anio-$fecha"] = $nombre;
        }

        // 2. Aplicar Ley Emiliani a los trasladables
        foreach ($trasladables as $fecha => $nombre) {
            $ts = strtotime("$anio-$fecha");
            $diaSemana = (int)date('N', $ts); // 1 = Lunes, 7 = Domingo
            if ($diaSemana !== 1) {
                // Mover al siguiente lunes
                $diasFaltantes = (8 - $diaSemana);
                $ts = strtotime("+$diasFaltantes days", $ts);
            }
            $festivos[date('Y-m-d', $ts)] = $nombre;
        }

        // 3. Festivos basados en Pascua (Semana Santa, Ascensión, Corpus Christi, Sagrado Corazón)
        $diasDesdePascua = easter_days($anio);
        $pascuaTs = strtotime("$anio-03-21 +$diasDesdePascua days");

        $festivos[date('Y-m-d', strtotime("-3 days", $pascuaTs))] = 'Jueves Santo';
        $festivos[date('Y-m-d', strtotime("-2 days", $pascuaTs))] = 'Viernes Santo';

        // Pascua trasladables al lunes
        $pascuaTrasladables = [
            '+43 days' => 'Ascensión del Señor',
            '+64 days' => 'Corpus Christi',
            '+71 days' => 'Sagrado Corazón de Jesús'
        ];

        foreach ($pascuaTrasladables as $desplazamiento => $nombre) {
            $ts = strtotime("$desplazamiento", $pascuaTs);
            $diaSemana = (int)date('N', $ts);
            if ($diaSemana !== 1) {
                $diasFaltantes = (8 - $diaSemana);
                $ts = strtotime("+$diasFaltantes days", $ts);
            }
            $festivos[date('Y-m-d', $ts)] = $nombre;
        }

        return $festivos;
    }
}