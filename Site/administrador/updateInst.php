<?php
// Inicia a sessão para verificar o login
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Inclui o arquivo de conexão centralizado
require_once '../conexao/conecta.php';

// Verifica se os dados foram enviados
if (!isset($_POST['id'], $_POST['nomeInstituicao'])) {
    die("Dados incompletos.");
}

// Sanitiza e valida os dados
$id = intval($_POST['id']);
$nome = trim($_POST['nomeInstituicao']);

// Validações adicionais
if (empty($nome)) {
    die("Nome da prova é obrigatórios.");
}

// Usa prepared statement para evitar SQL injection
$stmt = $conn->prepare("UPDATE tb_instituicao SET nome=? WHERE id=?");
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}

$stmt->bind_param("si", $nome, $id);

if ($stmt->execute()) {
    header("Location: cadastrarInst.php?success=1");
    exit();
} else {
    echo "Erro ao atualizar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>