<?php
// ==================================================
// buscar_notificacoes.php — Adaptado para MySQLi
// ==================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

// --- 1. CONEXÃO ---
// O seu 'conecta.php' deve definir a variável de conexão como $conn (do tipo mysqli)
// Certifique-se que o caminho está correto (foi resolvido no passo anterior)
// Exemplo robusto (ajuste conforme sua estrutura):
// require __DIR__ . '/conexao/conecta.php'; 
require_once __DIR__ . '/conexao/conecta.php'; // Caminho mais simples, se estiver no mesmo nível

// --- 2. VERIFICAÇÃO DE SESSÃO ---
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["erro" => "Usuário não autenticado."]);
    exit;
}

$idUsuario = intval($_SESSION['id']);
$notificacoes = [];

try {
    // Verificação de segurança da conexão MySQLi (a variável deve ser $conn)
    if (!$conn || $conn->connect_error) {
        throw new Exception("Falha de Conexão: A variável \$conn não está definida ou a conexão falhou.");
    }

    // --- 3. PREPARAÇÃO DA CONSULTA (SINTAXE MySQLi) ---
    // Consulta original
    $sql = "SELECT id, titulo, mensagem, tipo, status, dataEnvio, 
            DATE_FORMAT(dataEnvio, '%d/%m/%Y %H:%i') AS dataEnvio_formatada
            FROM tb_notificacoes
            WHERE idUsuario = ?
            ORDER BY dataEnvio DESC";

    // Prepara a query
    $stmt = $conn->prepare($sql);
    
    // Verifica se a preparação falhou (pode ser erro na query SQL)
    if ($stmt === false) {
        throw new Exception("Falha na preparação da query: " . $conn->error);
    }

    // Vincula o parâmetro (i = integer)
    $stmt->bind_param('i', $idUsuario); 
    
    // Executa
    $stmt->execute();
    
    // Obtém o resultado
    $resultado = $stmt->get_result();

    // --- 4. OBTÉM OS DADOS ---
    while ($row = $resultado->fetch_assoc()) {
        // Usa o campo formatado e garante o status
        $notificacoes[] = [
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'mensagem' => $row['mensagem'],
            'tipo' => $row['tipo'],
            'status' => $row['status'],
            'dataEnvio' => $row['dataEnvio_formatada'], // Usa o nome que o JS espera
        ];
    }
    
    $stmt->close();
    
    // Retorna os dados para o JS
    echo json_encode($notificacoes ?: []);

} catch (Throwable $e) {
    // Captura qualquer erro de execução
    http_response_code(500); 
    echo json_encode([
        "erro" => "ERRO CRÍTICO NO BACKEND (MySQLi)", 
        "detalhe" => $e->getMessage()
    ]);
}
?>