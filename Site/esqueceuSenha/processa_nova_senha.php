<?php
session_start();

// Verificação CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token_redefinir']) {
    die("<div class='alert alert-error'>Token CSRF inválido!</div>");
}

include_once 'conecta.php';

$conn = new conecta();
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$token = $_POST['token'];
$novaSenha = $_POST['nova_senha'];
$confirmarSenha = $_POST['confirmar_senha'];

// Validações
if (empty($novaSenha) || empty($confirmarSenha)) {
    header("Location: redefinir_senha.php?email=$email&token=$token&error=Preencha todos os campos.");
    exit();
}

if (strlen($novaSenha) < 8) {
    header("Location: redefinir_senha.php?email=$email&token=$token&error=A senha deve ter no mínimo 8 caracteres.");
    exit();
}

if ($novaSenha !== $confirmarSenha) {
    header("Location: redefinir_senha.php?email=$email&token=$token&error=As senhas não coincidem.");
    exit();
}

$valido = $conn->validarToken($email, $token);

if($valido) {
    $conn->atualizarSenha($valido['id'], $novaSenha);
    
    // Limpa os tokens de sessão
    unset($_SESSION['csrf_token_redefinir']);
    unset($_SESSION['tentativas']);
    
    // Redireciona com mensagem de sucesso
    header("Location: redefinir_senha.php?email=$email&token=$token&success=Senha alterada com sucesso!");
    exit();
} else {
    header("Location: redefinir_senha.php?email=$email&token=$token&error=Link inválido ou expirado.");
    exit();
}
?>