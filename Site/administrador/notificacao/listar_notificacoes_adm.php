<?php
// ==================================================
// listar_notificacoes_adm.php – Lista todas as notificações
// ==================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

// --- 1. VERIFICAÇÃO DE PERMISSÃO DE ADMIN ---
if (($_SESSION['tipoUsuario'] ?? '') !== 'administrador') {
    http_response_code(403);
    echo json_encode(["erro" => "Acesso negado."]);
    exit;
}

// --- 2. CONEXÃO ---
require __DIR__ . '/../../conexao/conecta.php'; 

$notificacoes = [];

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception("Falha de Conexão: {$conn->connect_error}");
    }

    // --- 3. CONSULTA DE TODAS AS NOTIFICAÇÕES ---
    $sql = "SELECT 
                tn.id, 
                tn.titulo, 
                tn.mensagem, 
                tn.tipo, 
                tn.status, 
                DATE_FORMAT(tn.dataEnvio, '%d/%m/%Y %H:%i') AS dataEnvio_formatada,
                tn.idUsuario
            FROM 
                tb_notificacoes tn
            ORDER BY 
                tn.dataEnvio DESC
            LIMIT 100"; // Limita para evitar problemas de performance

    $resultado = $conn->query($sql);

    if ($resultado === false) {
        throw new Exception("Erro ao executar a consulta: " . $conn->error);
    }
    
    // --- 4. OBTÉM OS DADOS ---
    while ($row = $resultado->fetch_assoc()) {
        $notificacoes[] = $row;
    }
    
    $conn->close();
    
    echo json_encode($notificacoes ?: []);

} catch (Throwable $e) {
    http_response_code(500); 
    echo json_encode([
        "erro" => "ERRO CRÍTICO NO BACKEND", 
        "detalhe" => $e->getMessage()
    ]);
}
?>