<?php
// salvaRascunho.php - Grava ou atualiza a resposta do rascunho no banco de dados.

session_start();
// Assumindo que seu arquivo de conexão está em 'conexao/conecta.php'
require_once __DIR__ . '/conexao/conecta.php'; 

// Define o cabeçalho para indicar que a resposta será JSON
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado.']);
    exit();
}

$idUsuario = (int) $_SESSION['id'];

// Obtém os dados via POST do AJAX
$idProva   = isset($_POST['idProva'])   ? (int) $_POST['idProva']   : 0;
$idQuestao = isset($_POST['idQuestao']) ? (int) $_POST['idQuestao'] : 0;
// Normaliza a resposta para um único caractere maiúsculo ('A' a 'E')
$resposta  = isset($_POST['resposta'])  ? trim(strtoupper(substr($_POST['resposta'], 0, 1))) : '';

// Validação de entrada
if ($idProva <= 0 || $idQuestao <= 0 || !in_array($resposta, ['A', 'B', 'C', 'D', 'E'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dados de rascunho inválidos.']);
    exit();
}

try {
    // Usa INSERT ... ON DUPLICATE KEY UPDATE para inserir (se novo) ou atualizar (se já existir) o rascunho.
    $sql = "INSERT INTO tb_rascunho (id_usuario, id_prova, id_questao, resposta_marcada) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            resposta_marcada = VALUES(resposta_marcada), 
            data_rascunho = CURRENT_TIMESTAMP";

    $stmt = $conn->prepare($sql);

    // Tipos de dados: i (integer), i (integer), i (integer), s (string)
    $stmt->bind_param("iiis", $idUsuario, $idProva, $idQuestao, $resposta);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Rascunho salvo.']);
    } else {
        http_response_code(500);
        // Em um ambiente real, evite exibir $stmt->error publicamente
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar rascunho no banco de dados.']);
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    error_log("Erro ao salvar rascunho: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro interno do servidor.']);
}
?>