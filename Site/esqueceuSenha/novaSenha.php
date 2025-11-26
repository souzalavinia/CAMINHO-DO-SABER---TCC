<?php
session_start();

// Verificação CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: esqueceuSenha.html?error=Token CSRF inválido!");
    exit();
}

// Limite de tentativas
if (!isset($_SESSION['tentativas'])) {
    $_SESSION['tentativas'] = 0;
}
if ($_SESSION['tentativas'] > 3) {
    header("Location: esqueceuSenha.html?error=Muitas tentativas. Tente novamente mais tarde.");
    exit();
}

$_SESSION['tentativas']++;

// Validação de e-mail
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: esqueceuSenha.html?error=E-mail inválido!");
    exit();
}

require 'vendor/autoload.php';
include_once 'conecta.php';

$conn = new conecta();
$dados = $conn->geraChaveAcesso($email);

if($dados) {
    // Simulação de envio de e-mail (para desenvolvimento)
    echo "<div style='border:1px solid #ccc; padding:20px; margin:20px;'>";
    echo "<h3>E-mail de teste (em desenvolvimento)</h3>";
    echo "<p>Para: ".htmlspecialchars($dados['email'])."</p>";
    
    $link = "http://localhost/tcc_final/redefinir_senha.php?email=".urlencode($dados['email'])."&token=".$dados['chave'];
    echo "<p>Assunto: Redefinição de Senha - Scholar Support</p>";
    echo "<p>Conteúdo:</p>";
    echo "<p>Olá, ".htmlspecialchars($dados['nome'])."!</p>";
    echo "<p>Clique no link para redefinir sua senha: <a href='$link'>$link</a></p>";
    echo "<p>O link expirará em 1 hora.</p>";
    echo "</div>";
    
    // Em produção, substitua o bloco acima por:
    /*
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'seuemail@gmail.com';
        $mail->Password = 'seuapppassword';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        $mail->setFrom('seuemail@gmail.com', 'Scholar Support');
        $mail->addAddress($dados['email'], $dados['nome']);
        
        $mail->isHTML(true);
        $mail->Subject = 'Redefinicao de Senha';
        
        $mail->Body = "
        <html>
        <head>
            <title>Redefinição de senha</title>
        </head>
        <body>
            <h2>Olá, ".htmlspecialchars($dados['nome'])."!</h2>
            <p>Recebemos uma solicitação para redefinir sua senha.</p>
            <p>Clique no link abaixo para criar uma nova senha:</p>
            <p><a href='$link'>Redefinir senha</a></p>
            <p>Se você não solicitou esta alteração, ignore este e-mail.</p>
            <p>O link expirará em 1 hora.</p>
        </body>
        </html>
        ";
        
        $mail->send();
        echo "Um e-mail com instruções foi enviado para ".htmlspecialchars($dados['email']);
    } catch (Exception $e) {
        echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
    }
    */
} else {
    header("Location: esqueceuSenha.html?error=E-mail não encontrado em nosso sistema.");
    exit();
}
?>