<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../../login.php");
    exit();
}

// Incluir o arquivo de conexão
include_once '../../conexao/conecta.php';

// Verificar se a tabela existe
$tabelaExiste = $conn->query("SHOW TABLES LIKE 'tb_assinaturas'");
if ($tabelaExiste->num_rows == 0) {
    // Criar a tabela se não existir
    $createTable = "CREATE TABLE `tb_assinaturas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nomeUsuario` varchar(150) NOT NULL,
        `telefoneUsuario` varchar(14) NOT NULL,
        `emailUsuario` varchar(300) NOT NULL,
        `idUsuario` int(11) NOT NULL,
        `nomePlano` varchar(100) NOT NULL,
        `descricaoPlano` varchar(535) NOT NULL,
        `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!$conn->query($createTable)) {
        die("Erro ao criar tabela: " . $conn->error);
    }
}

// Coletar dados do formulário
$idUsuario = $_SESSION['id'];
$nomeUsuario = $_POST['nomeUsuario'];
$telefoneUsuario = $_POST['telefoneUsuario'];
$emailUsuario = $_POST['emailUsuario'];
$plano = $_POST['plano'];
$nomePlano = $_POST['nomePlano'];
$descricaoPlano = $_POST['descricaoPlano'];
$status = 'pedente';

// Inserir dados na tabela
$query = "INSERT INTO tb_assinaturas (nomeUsuario, telefoneUsuario, emailUsuario, idUsuario, nomePlano, descricaoPlano, status) VALUES ('$nomeUsuario', '$telefoneUsuario', '$emailUsuario', '$idUsuario', '$nomePlano', '$descricaoPlano', '$status')";

if ($conn->query($query) === TRUE) {
    header("Location: ../configuracoes.php");
} else {
    echo "Erro: " . $query . "<br>" . $conn->error;
}

$conn->close();
?>