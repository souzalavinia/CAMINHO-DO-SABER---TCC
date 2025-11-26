<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inclui manualmente os arquivos da PHPMailer
require_once '/home2/renant49/caminhodosaber.online/email/PHPMailer-master/src/PHPMailer.php';
require_once '/home2/renant49/caminhodosaber.online/email/PHPMailer-master/src/SMTP.php';
require_once '/home2/renant49/caminhodosaber.online/email/PHPMailer-master/src/Exception.php';

function enviarLinkRedefinicao($destinatario, $link) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'mail.caminhodosaber.online';
        $mail->SMTPAuth = true;
        $mail->Username = 'naoresponda@caminhodosaber.online';
        $mail->Password = '3g><=+4MxUT5'; // ou a senha nova testada com sucesso
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('naoresponda@caminhodosaber.online', 'Caminho do Saber');
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha - Caminho do Saber';
        $mail->Body = "
            <h2>Olá!</h2>
            <p>Você solicitou uma redefinição de senha. Clique no link abaixo para continuar:</p>
            <p><a href='$link'>$link</a></p>
            <p>Se você não solicitou isso, ignore este e-mail.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar email: ' . $mail->ErrorInfo);
        return false;
    }
}
