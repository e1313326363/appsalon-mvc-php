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

        // Cargar variables de entorno desde la raíz del proyecto
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../'); // Ajusta según la ubicación de tu .env
        $dotenv->safeLoad();
    }

    private function configurarMail()
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS

            // Leer variables con getenv() en lugar de $_ENV
            $mail->Host = getenv('EMAIL_HOST') ?: '';
            $mail->Username = getenv('EMAIL_USER') ?: '';
            $mail->Password = getenv('EMAIL_PASS') ?: '';
            $mail->Port = intval(getenv('EMAIL_PORT') ?: 2525);

            $mail->setFrom('no-reply@appsalon.com', 'AppSalon');
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';

            // Debug SMTP (0=off)
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = 'html';

            return $mail;

        } catch (Exception $e) {
            error_log("Error al configurar PHPMailer: " . $e->getMessage());
            return null;
        }
    }

    public function enviarConfirmacion()
    {
        $mail = $this->configurarMail();
        if (!$mail) return false;

        try {
            $mail->addAddress($this->email, $this->nombre);
            $mail->Subject = 'Confirma tu cuenta';

            $appUrl = getenv('APP_URL') ?: '';

            $contenido = "<html>";
            $contenido .= "<p><strong>Hola {$this->nombre}</strong>, has creado tu cuenta en AppSalon. ";
            $contenido .= "Confirma tu cuenta presionando el siguiente enlace:</p>";
            $contenido .= "<p><a href='{$appUrl}/confirmar-cuenta?token={$this->token}'>Confirmar Cuenta</a></p>";
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
        if (!$mail) return false;

        try {
            $mail->addAddress($this->email, $this->nombre);
            $mail->Subject = 'Reestablece tu contraseña';

            $appUrl = getenv('APP_URL') ?: '';

            $contenido = "<html>";
            $contenido .= "<p><strong>Hola {$this->nombre}</strong>, has solicitado restablecer tu contraseña. ";
            $contenido .= "Sigue el siguiente enlace para hacerlo:</p>";
            $contenido .= "<p><a href='{$appUrl}/recuperar?token={$this->token}'>Restablecer Contraseña</a></p>";
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
