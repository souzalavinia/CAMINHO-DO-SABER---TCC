<?php
session_start();

require_once '../conexao/conecta.php';

// A variável de conexão $conn agora vem do arquivo 'conecta.php'.
// A lógica local de conexão foi removida.

$id = $_SESSION["id"];
$senhaAtual = $_POST["senhaAtual"];
$novaSenha = $_POST["novaSenha"];

// Primeiro, buscar a senha atual do usuário no banco de dados
$sql_check = "SELECT senha FROM tb_usuario WHERE id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows === 0) {
    // Usuário não encontrado
    $stmt_check->close();
    $conn->close();
    header("Location: configuracoes.php?error=Usuário+não+encontrado.");
    exit();
}

$usuario = $result->fetch_assoc();
$stmt_check->close();

// Verificar se a senha atual está correta
if (!password_verify($senhaAtual, $usuario['senha'])) {
    $conn->close();
    header("Location: configuracoes.php?error=Senha+atual+incorreta.");
    exit();
}

// Validar a nova senha
if (empty($novaSenha)) {
    $conn->close();
    header("Location: configuracoes.php?error=Nova+senha+não+fornecida.");
    exit();
}

// Validar força da nova senha
if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*]).{8,}$/', $novaSenha)) {
    $conn->close();
    header("Location: configuracoes.php?error=A+senha+deve+conter+pelo+menos+8+caracteres,+incluindo+1+letra+maiúscula,+1+número+e+1+caractere+especial.");
    exit();
}

// Se todas as validações passarem, atualizar a senha
$senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
$sql = "UPDATE tb_usuario SET senha=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $senhaHash, $id);

if ($stmt->execute()) {
    header("Location: configuracoes.php?success=Senha+alterada+com+sucesso!");
} else {
    header("Location: configuracoes.php?error=Erro+ao+atualizar+senha:+".urlencode($stmt->error));
}

$stmt->close();
$conn->close();
?>