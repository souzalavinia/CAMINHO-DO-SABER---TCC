<?php
session_start();

// Garante que o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

// Converte o tipo de usuário para minúsculas para garantir a validação
$tipoUsuarioSessao = strtolower($_SESSION['tipoUsuario'] ?? '');

// Verifica se o tipo de usuário tem permissão de acesso
if ( $tipoUsuarioSessao !== 'administrador') {
    // Se não for um diretor ou administrador, destrói a sessão e redireciona
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
}

require_once '../conexao/conecta.php';

// Verifica se o ID foi passado
if (!isset($_GET['id'])) {
    header("Location: exibirProvas.php");
    exit();
}

$id = $_GET['id'];

// Primeiro obtemos o ID da prova para redirecionamento
$sql = "SELECT prova FROM tb_quest WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: exibirProvas.php");
    exit();
}

$questao = $result->fetch_assoc();
$prova_id = $questao['prova'];
$stmt->close();

// Exclui a questão
$sql = "DELETE FROM tb_quest WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Questão excluída com sucesso!";
} else {
    $_SESSION['error_message'] = "Erro ao excluir a questão: " . $stmt->error;
}

$stmt->close();
$conn->close();

header("Location: mostraQuest.php?id=" . $prova_id);
exit();
?>