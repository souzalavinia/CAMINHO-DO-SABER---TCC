<?php
// Inicia a sessão para verificar o login
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.html");
    exit();
}

// Inclui o arquivo de conexão centralizado
require_once '../conexao/conecta.php';

// Verifica se os dados principais foram enviados
if (!isset($_POST['id'], $_POST['nome'], $_POST['anoProva'])) {
    die("Dados incompletos.");
}

// Sanitiza e valida os dados
$id = intval($_POST['id']);
$nome = trim($_POST['nome']);
$anoProva = trim($_POST['anoProva']);
$simulado = trim($_POST['simulado'] ?? 'não');

// NOVO: Coleta o campo 'serial'. Se estiver vazio, define como NULL (apropriado para a coluna VARCHAR(255) NULL)
$serial = !empty($_POST['serial']) ? trim($_POST['serial']) : null; 

// Validações adicionais
if (empty($nome) || empty($anoProva)) {
    die("Nome e ano da prova são obrigatórios.");
}

// ATUALIZAÇÃO 1: Query de UPDATE inclui o campo 'serial'
$sql_update = "UPDATE tb_prova SET nome=?, anoProva=?, simulado=?, serial=? WHERE id=?";
$stmt = $conn->prepare($sql_update);
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}

// ATUALIZAÇÃO 2: bind_param inclui a variável '$serial'
// Tipos esperados: nome (s), anoProva (s), simulado (s), serial (s), id (i)
$stmt->bind_param("ssssi", $nome, $anoProva, $simulado, $serial, $id);

if ($stmt->execute()) {
    // Redireciona com sucesso
    header("Location: cadastrarProvas1.php?success=1");
    exit();
} else {
    // Exibe o erro se a atualização falhar
    echo "Erro ao atualizar: " . $stmt->error;
    // Opcional: Redirecionar de volta para a edição em caso de erro
    // header("Location: editProva.php?id=" . $id . "&error=" . urlencode($stmt->error));
}

$stmt->close();
$conn->close();
?>