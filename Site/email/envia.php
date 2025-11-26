<?php
ob_start();
require("/home2/renant49/caminhodosaber.online/email/PHPMailer-master/src/PHPMailer.php");
require("/home2/renant49/caminhodosaber.online/email/PHPMailer-master/src/SMTP.php");

function enviarEmailRedefinicao($destinatario, $link) {
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'ssl';
    $mail->Host = "smtp.titan.email";
    $mail->Port = 465;
    $mail->isHTML(true);
    $mail->Username = "naoresponda@caminhodosaber.online";
    $mail->Password = "3g><=+4MxUT5";
    $mail->setFrom("naoresponda@caminhodosaber.online", "Caminho do Saber");
    $mail->addAddress($destinatario);
    $mail->Subject = 'Redefinição de Senha - Caminho do Saber';
    $mail->Body = "
        <h2>Olá!</h2>
        <p>Você solicitou uma redefinição de senha. Clique no link abaixo para continuar:</p>
        <p><a href='$link'>$link</a></p>
        <p>Se você não solicitou isso, ignore este e-mail.</p>
    ";

    if (!$mail->send()) {
        ob_end_clean(); // limpa qualquer saída
        return "Erro ao enviar: " . $mail->ErrorInfo;
    } else {
        ob_end_clean(); // limpa qualquer saída
        return true;
    }
}
