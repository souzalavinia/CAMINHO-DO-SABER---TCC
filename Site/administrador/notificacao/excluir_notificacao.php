<?php
// ==========================================================
// excluir_notificacao.php - Deleta uma ou múltiplas notificações (Em Massa)
// ==========================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

// --- 1. VERIFICAÇÃO DE PERMISSÃO DE ADMIN ---
if (($_SESSION['tipoUsuario'] ?? '') !== 'administrador') {
    http_response_code(403);
    echo json_encode(["erro" => "Acesso negado. Apenas administradores podem excluir notificações."]);
    exit;
}

// O script deve receber 'ids' que é um array (exclusão em massa) ou 'id' (exclusão individual)
$idsRaw = $_POST['ids'] ?? ($_POST['id'] ?? null);

// Garante que $idsRaw seja um array, mesmo que venha como string de um único 'id'
if (!is_array($idsRaw)) {
    $idsRaw = [$idsRaw];
}

// Filtra e valida os IDs, garantindo que sejam inteiros e maiores que zero
$idsParaExcluir = array_filter($idsRaw, function($id) {
    return is_numeric($id) && (int)$id > 0;
});

if (empty($idsParaExcluir)) {
    http_response_code(400); // Requisição inválida
    echo json_encode(["erro" => "IDs de notificação inválidos ou não fornecidos."]);
    exit;
}

// --- 2. CONEXÃO (Caminho ajustado para a pasta 'notificacao') ---
require __DIR__ . '/../../conexao/conecta.php'; 

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception("Falha de Conexão: {$conn->connect_error}");
    }

    // Cria placeholders de interrogação (?, ?, ?) para a cláusula IN
    $placeholders = implode(',', array_fill(0, count($idsParaExcluir), '?'));
    
    // Converte os IDs para o formato de parâmetro de bind (todos são inteiros 'i')
    $tiposBind = str_repeat('i', count($idsParaExcluir));
    
    // --- 3. EXCLUSÃO DA NOTIFICAÇÃO EM MASSA (DELETE WHERE IN) ---
    $sql = "DELETE FROM tb_notificacoes WHERE id IN ({$placeholders})";

    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Falha na preparação do DELETE: " . $conn->error);
    }
    
    // Vincula os parâmetros dinamicamente
    $stmt->bind_param($tiposBind, ...$idsParaExcluir);
    
    $execucao = $stmt->execute();

    if ($execucao === false) {
        throw new Exception("Falha na execução do DELETE: " . $stmt->error);
    }
    
    $linhasAfetadas = $stmt->affected_rows;
    
    $stmt->close();
    $conn->close();

    if ($linhasAfetadas > 0) {
        // Sucesso
        echo json_encode([
            "sucesso" => true, 
            "mensagem" => "{$linhasAfetadas} notificação(ões) excluída(s) com sucesso."
        ]);
    } else {
        // Nenhuma linha afetada
        http_response_code(404); 
        echo json_encode(["erro" => "Nenhuma das notificações selecionadas foi encontrada para exclusão."]);
    }

} catch (Throwable $e) {
    http_response_code(500); 
    echo json_encode([
        "sucesso" => false, 
        "erro" => "ERRO INTERNO ao excluir: " . $e->getMessage()
    ]);
}
?>