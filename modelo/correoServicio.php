<?php
require_once "modelo/conexion.php";
require_once "modelo/usuarioModelo.php";
require_once "modelo/turnoModelo.php";
require_once "modelo/correoServicio.php";
require_once "controlador/plantillaControlador.php";
require_once "controlador/usuariosControlador.php";
class CorreoServicio {
    public static function enviarCodigoRecuperacion($destinatario, $codigo) {
        $usuarioGmail = "oxcarbohorfo13@gmail.com";        // Tu cuenta de Google
        $claveApp = "wybq fdzb acea lzpd";           // Tu contraseña de aplicación de 16 dígitos de Gmail

        $socket = fsockopen("tcp://smtp.gmail.com", 587, $errno, $errstr, 15);
        if (!$socket) return false;

        $leer = function() use ($socket) {
            $datos = "";
            while ($str = fgets($socket, 515)) {
                $datos .= $str;
                if (substr($str, 3, 1) == " ") break;
            }
            return $datos;
        };

        $enviar = function($cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $leer();
        $enviar("EHLO localhost"); $leer();
        $enviar("STARTTLS"); $leer();
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $enviar("EHLO localhost"); $leer();
        $enviar("AUTH LOGIN"); $leer();
        $enviar(base64_encode($usuarioGmail)); $leer();
        $enviar(base64_encode(str_replace(' ', '', $claveApp))); $leer();
        $enviar("MAIL FROM: <$usuarioGmail>"); $leer();
        $enviar("RCPT TO: <$destinatario>"); $leer();
        $enviar("DATA"); $leer();

        $cuerpo = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        $cuerpo .= "From: WorkShift Security <$usuarioGmail>\r\nTo: <$destinatario>\r\nSubject: Codigo de Seguridad: $codigo\r\n\r\n";
        $cuerpo .= "<html><body style='font-family:sans-serif; background:#0f172a; color:#f8fafc; padding:20px;'>
            <div style='background:#1e293b; border-radius:14px; padding:24px; text-align:center;'>
                <h3 style='color:#38bdf8; margin:0;'>Código de Verificación</h3>
                <p style='color:#94a3b8; font-size:13px;'>Tu código es válido durante 10 minutos:</p>
                <div style='font-size:32px; font-weight:bold; letter-spacing:6px; color:#ffffff; background:#0f172a; padding:12px; border-radius:10px; display:inline-block; margin:15px 0;'>$codigo</div>
                <p style='color:#64748b; font-size:11px;'>Si no solicitaste este cambio, desestima este correo.</p>
            </div></body></html>\r\n.\r\n";

        $enviar($cuerpo); $leer();
        $enviar("QUIT"); $leer();
        fclose($socket);
        return true;
    }
}