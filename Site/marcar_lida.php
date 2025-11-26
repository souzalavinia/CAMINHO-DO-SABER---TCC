<?php
// ==================================================
// marcar_lida.php — Adaptado para MySQLi
// ==================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

// Inclui a conexão MySQLi ($conn)
require_once __DIR__ . '/conexao/conecta.php'; 

// Verifica login
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["erro" => "Usuário não autenticado."]);
    exit;
}

$idUsuario = intval($_SESSION['id']);
$data = json_decode(file_get_contents("php://input"), true);
$idNotificacao = isset($data['id']) ? intval($data['id']) : 0;

if ($idNotificacao <= 0) {
    http_response_code(400);
    echo json_encode(["erro" => "ID de notificação inválido."]);
    exit;
}

try {
    // Verificação de segurança da conexão MySQLi
    if (!$conn || $conn->connect_error) {
        throw new Exception("Falha de Conexão: A variável \$conn não está definida ou a conexão falhou.");
    }
    
    $sql = "UPDATE tb_notificacoes 
            SET status = 'lida'
            WHERE id = ? AND idUsuario = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Falha na preparação da query: " . $conn->error);
    }
    
    // Vincula os parâmetros (ii = dois integers)
    $stmt->bind_param('ii', $idNotificacao, $idUsuario);

    $stmt->execute();
    $stmt->close();

    echo json_encode(["sucesso" => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao atualizar notificação.", "detalhe" => $e->getMessage()]);
}
?>