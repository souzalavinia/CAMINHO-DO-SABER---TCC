<?php
// Inclui o arquivo de conexão centralizado
require_once '../conexao/conecta.php';

// Obtém os dados do formulário
$nome = $_POST['nomeInstituicao'];

// Usa prepared statement para inserir a prova com segurança
$sql = "INSERT INTO tb_instituicao (nome) VALUES (?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo "Erro na preparação da consulta: " . $conn->error;
} else {
    $stmt->bind_param("s", $nome);
    
    if ($stmt->execute()) {
        header("Location: cadastrarInst.php?success=1");
        exit();
    } else {
        echo "Erro na execução da consulta: " . $stmt->error;
    }
    
    $stmt->close();
}

$conn->close();
?>