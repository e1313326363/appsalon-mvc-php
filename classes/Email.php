<?php

namespace Classes;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

class Email
{
    public $email;
    public $nombre;
    public $token;

    public function __construct($nombre, $email, $token)
    {
        $this->email = $email;
        $this->nombre = $nombre;
        $this->token = $token;

        // Cargar variables de entorno desde includes/.env
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../includes');
        $dotenv->safeLoad();
    }

    private function configurarMail()
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['EMAIL_HOST'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['EMAIL_USER'] ?? '';
            $mail->Password = $_ENV['EMAIL_PASS'] ?? '';
            $mail->Port = intval($_ENV['EMAIL_PORT'] ?? 2525);
            $mail->SMTPSecure = 'tls';

            // Debug SMTP solo para desarrollo (0 = desactivado)
            $mail->SMTPDebug = 0; 
            $mail->Debugoutput = 'html';

            $mail->setFrom('no-reply@appsalon.com', 'AppSalon');
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';

            return $mail;

        } catch (Exception $e) {
            // Guardar error en log
            error_log("Error al configurar PHPMailer: " . $e->getMessage());
            return null;
        }
    }

    public function enviarConfirmacion()
    {
        $mail = $this->configurarMail();
        if(!$mail) return false;

        try {
            $mail->addAddress($this->email, $this->nombre);
            $mail->Subject = 'Confirma tu cuenta';

            $contenido = "<html>";
            $contenido .= "<p><strong>Hola " . $this->nombre . "</strong>, has creado tu cuenta en AppSalon. ";
            $contenido .= "Confirma tu cuenta presionando el siguiente enlace:</p>";
            $contenido .= "<p><a href='" . ($_ENV['APP_URL'] ?? '') . "/confirmar-cuenta?token=" . $this->token . "'>Confirmar Cuenta</a></p>";
            $contenido .= "<p>Si no solicitaste esta cuenta, ignora este mensaje.</p>";
            $contenido .= "</html>";

            $mail->Body = $contenido;
            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Error al enviar correo de confirmación: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function enviarInstrucciones()
    {
        $mail = $this->configurarMail();
        if(!$mail) return false;

        try {
            $mail->addAddress($this->email, $this->nombre);
            $mail->Subject = 'Reestablece tu contraseña';

            $contenido = "<html>";
            $contenido .= "<p><strong>Hola " . $this->nombre . "</strong>, has solicitado restablecer tu contraseña. ";
            $contenido .= "Sigue el siguiente enlace para hacerlo:</p>";
            $contenido .= "<p><a href='" . ($_ENV['APP_URL'] ?? '') . "/recuperar?token=" . $this->token . "'>Restablecer Contraseña</a></p>";
            $contenido .= "<p>Si no solicitaste esta acción, ignora este mensaje.</p>";
            $contenido .= "</html>";

            $mail->Body = $contenido;
            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Error al enviar correo de instrucciones: " . $mail->ErrorInfo);
            return false;
        }
    }
}
