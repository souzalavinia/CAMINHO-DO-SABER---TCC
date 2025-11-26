<?php
// deletar_notificacao.php

// Inicia a sessão para garantir que o usuário está logado
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
$response = ['success' => false];

if (!isset($_SESSION['id'])) {
    http_response_code(401); // Não autorizado
    $response['error'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit;
}

// Pega o JSON do corpo da requisição
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400); // Requisição inválida
    $response['error'] = 'ID da notificação inválido.';
    echo json_encode($response);
    exit;
}

$notificationId = (int)$data['id'];
$idUsuarioLogado = (int)$_SESSION['id'];

// Inclui a conexão com o banco de dados
// Certifique-se de que o caminho está correto
require_once __DIR__ . '/conexao/conecta.php';

if ($conn->connect_error) {
    http_response_code(500);
    $response['error'] = 'Falha na conexão com o banco de dados.';
    echo json_encode($response);
    exit;
}

// SQL para DELETAR a notificação, garantindo que pertença ao usuário logado
$sql = "DELETE FROM tb_notificacoes WHERE id = ? AND idUsuario = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    $response['error'] = 'Erro na preparação da query: ' . $conn->error;
    echo json_encode($response);
    $conn->close();
    exit;
}

$stmt->bind_param('ii', $notificationId, $idUsuarioLogado);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
    } else {
        $response['error'] = 'Notificação não encontrada ou não pertence ao usuário.';
    }
} else {
    http_response_code(500);
    $response['error'] = 'Erro ao executar a exclusão.';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>