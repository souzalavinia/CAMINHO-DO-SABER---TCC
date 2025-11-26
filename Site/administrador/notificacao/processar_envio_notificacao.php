<?php
// ==========================================================
// processar_envio_notificacao.php - Lógica de Inserção em Massa
// ==========================================================

// 🚨 ATENÇÃO: DIRETIVAS DE EXIBIÇÃO DE ERRO (APENAS PARA DESENVOLVIMENTO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// REMOVA OU COMENTE AS LINHAS ACIMA EM PRODUÇÃO!

session_start();
header('Content-Type: text/html; charset=utf-8');

// --- 1. VERIFICAÇÃO DE AUTENTICAÇÃO E PERMISSÃO ---
if (!isset($_SESSION['id']) || ($_SESSION['tipoUsuario'] ?? '') !== 'administrador') {
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
}
// --------------------------------------------------------

// --- 2. CONEXÃO E VALIDAÇÃO DE DADOS ---
require __DIR__ . '/../../conexao/conecta.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['envio_global'])) {
    $_SESSION['feedback'] = ['tipo' => 'erro', 'mensagem' => 'Requisição inválida.'];
    header("Location: enviar_notificacao_adm.php");
    exit;
}

$titulo = trim($_POST['titulo'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');
$tipo = $_POST['tipo'] ?? '';

if (empty($titulo) || empty($mensagem) || !in_array($tipo, ['info', 'alerta', 'sucesso', 'erro'])) {
    $_SESSION['feedback'] = ['tipo' => 'erro', 'mensagem' => 'Todos os campos são obrigatórios e o Tipo deve ser válido.'];
    header("Location: enviar_notificacao_adm.php");
    exit;
}

$statusInicial = 'nao_lida';

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception("Falha de Conexão com o Banco de Dados: {$conn->connect_error}");
    }

    // --- 3. INSERÇÃO EM MASSA OTIMIZADA ---
    // Certifique-se que tb_usuarios.id é o ID primário da sua tabela de usuários
    $sql = "
        INSERT INTO tb_notificacoes (idUsuario, titulo, mensagem, tipo, status, dataEnvio)
        SELECT 
            tbu.id, 
            ?, 
            ?, 
            ?, 
            ?, 
            NOW() 
        FROM 
            tb_usuario tbu 
        WHERE 
            1=1; 
    ";

    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Falha na preparação da query (SQL/Tabela): " . $conn->error);
    }
    
    $stmt->bind_param('ssss', $titulo, $mensagem, $tipo, $statusInicial);
    
    $execucao = $stmt->execute();

    if ($execucao === false) {
        throw new Exception("Falha na execução da query: " . $stmt->error);
    }
    
    $linhasAfetadas = $stmt->affected_rows;
    
    $stmt->close();
    $conn->close();

    $_SESSION['feedback'] = [
        'tipo' => 'sucesso', 
        'mensagem' => "Notificação disparada com sucesso para **{$linhasAfetadas}** usuários."
    ];
    
} catch (Throwable $e) {
    $_SESSION['feedback'] = [
        'tipo' => 'erro', 
        'mensagem' => "ERRO: " . $e->getMessage()
    ];
}

header("Location: enviar_notificacao_adm.php");
exit();
?>