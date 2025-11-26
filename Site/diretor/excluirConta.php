<?php
session_start();
require_once '../../conexao/conecta.php'; // mesma conexão usada no resto do sistema

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$idUsuario = (int) $_SESSION['id'];
echo($idUsuario);

// Excluir redações vinculadas
$sql = "DELETE FROM tb_redacao WHERE idUsuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->close();

// Excluir tentativas de provas vinculadas
$sql = "DELETE FROM tb_tentativas WHERE idUsuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->close();

// Excluir o usuário
$sql = "DELETE FROM tb_usuario WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);

if ($stmt->execute()) {
    // destruir sessão antes de redirecionar
    session_destroy();
    echo "<script>alert('Conta e todos os dados vinculados foram excluídos com sucesso!'); window.location='../index.php';</script>";
} else {
    echo "<script>alert('Erro ao excluir conta: " . $stmt->error . "'); window.location='configuracoes.php';</script>";
}

$stmt->close();
$conn->close();
?>
