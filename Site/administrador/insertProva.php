<?php
session_start();

// Inclui o arquivo de conexão centralizado
require_once '../conexao/conecta.php';

// Função para gerar um serial alfanumérico único
function generateUniqueSerial($conn, $length = 10) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charsLength = strlen($chars);
    $serial = '';
    
    // Inicia um loop para garantir que o serial não exista no banco
    do {
        $serial = '';
        for ($i = 0; $i < $length; $i++) {
            $serial .= $chars[rand(0, $charsLength - 1)];
        }

        // Verifica a unicidade usando Prepared Statement
        $sql = "SELECT id FROM tb_prova WHERE serial = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $serial);
        $stmt->execute();
        $stmt->store_result();
        
        // Se 0 linhas, o serial é único
        $isUnique = $stmt->num_rows === 0;
        $stmt->close();

    } while (!$isUnique);

    return $serial;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Coleta e validação de dados
    $nome = trim($_POST['nome'] ?? '');
    $anoProva = trim($_POST['anoProva'] ?? '');
    $instituicao_id = (int)($_POST['instituicao'] ?? 0);

    // Validação básica
    if (empty($nome) || empty($anoProva) || $instituicao_id === 0) {
        echo "<script>alert('Erro: Todos os campos obrigatórios devem ser preenchidos.'); window.location.href='cadastrarProvas1.php';</script>";
        $conn->close();
        exit();
    }

    // 2. Geração da lógica automática
    $serialGerado = generateUniqueSerial($conn); // Gera o serial único
    $simulado = 'não'; // Define o campo 'simulado' como 'não' (requisito do usuário)

    // 3. Preparação do INSERT com Prepared Statement
    $sqlInsert = "INSERT INTO tb_prova (nome, anoProva, id_instituicao, serial, simulado) VALUES (?, ?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);

    if ($stmtInsert) {
        // Bind dos parâmetros: s (string), i (integer), i (integer), s (string), s (string)
        // Nota: Assumindo que anoProva é salvo como string (VARCHAR) ou se for INT, altere o "s" para "i"
        $stmtInsert->bind_param("siiss", $nome, $anoProva, $instituicao_id, $serialGerado, $simulado);
        
        if ($stmtInsert->execute()) {
            echo "<script>alert('Prova cadastrada com sucesso! Serial: {$serialGerado}'); window.location.href='cadastrarProvas1.php';</script>";
        } else {
            echo "<script>alert('Erro ao cadastrar a prova: " . $stmtInsert->error . "'); window.location.href='cadastrarProvas1.php';</script>";
        }
        $stmtInsert->close();
    } else {
        echo "<script>alert('Erro na preparação do statement: " . $conn->error . "'); window.location.href='cadastrarProvas1.php';</script>";
    }
} else {
    // Acesso direto ao arquivo sem POST
    header("Location: cadastrarProvas1.php");
}

$conn->close();
?>