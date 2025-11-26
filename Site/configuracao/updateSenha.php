<?php
session_start();

// O arquivo conecta.php agora é a única fonte de conexão.
require_once '../conexao/conecta.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_SESSION["id"];
    $senhaAtual = $_POST["senhaAtual"];
    $novaSenha = $_POST["new_password"];

    // Primeiro, buscar a senha atual do usuário no banco de dados
    $sql_check = "SELECT senha FROM tb_usuario WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    
    if (!$stmt_check) {
        header("Location: configuracoes.php?error=Erro na consulta de senha.");
        exit();
    }
    
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        $stmt_check->close();
        $conn->close();
        header("Location: configuracoes.php?error=Usuário não encontrado.");
        exit();
    }

    $usuario = $result->fetch_assoc();
    $senhaHash = $usuario['senha'];
    $stmt_check->close();

    // Verifica se a senha atual está correta usando password_verify
    if (!password_verify($senhaAtual, $senhaHash)) {
        header("Location: configuracoes.php?error=Senha atual incorreta.");
        exit();
    }

    // Hash da nova senha
    $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

    // Atualiza a senha no banco de dados
    $sql_update = "UPDATE tb_usuario SET senha = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    
    if (!$stmt_update) {
        header("Location: configuracoes.php?error=Erro na atualização de senha.");
        exit();
    }

    $stmt_update->bind_param("si", $novaSenhaHash, $id);
    
    if ($stmt_update->execute()) {
        header("Location: configuracoes.php?success=Senha atualizada com sucesso.");
    } else {
        header("Location: configuracoes.php?error=Erro ao atualizar a senha.");
    }
    
    $stmt_update->close();
}

$conn->close();
?>