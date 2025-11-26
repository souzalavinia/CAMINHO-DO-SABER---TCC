<?php
session_start();

// Ativação temporária de erro - REMOVER DEPOIS DE CORRIGIR
 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);

require_once '../conexao/conecta.php';

// Redireciona para o login se o usuário não estiver logado
if (!isset($_SESSION['id'])) {
    header("Location: ../login.html");
    exit();
}

// Processa o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Coleta dados do POST
    $pergunta   = $_POST['pergunta'];
    $alt_a      = $_POST['alternativaA'];
    $alt_b      = $_POST['alternativaB'];
    $alt_c      = $_POST['alternativaC'];
    $alt_d      = $_POST['alternativaD'];
    $alt_e      = $_POST['alternativaE'];
    $alt_corre  = $_POST['correta'];
    $prova      = $_POST['prova'];
    $numQuest   = $_POST['numQuest'];

    // 2. Processamento Opcional da Imagem
    // Inicializa as variáveis da imagem como NULL (Necessário para o bind_param)
    $imageData = NULL; 
    $tipo  = NULL; 

    // Verifica se há um arquivo enviado E se o upload foi bem-sucedido (código de erro 0)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        
        $image_temp_name = $_FILES['foto']['tmp_name'];
        $tipo  = $_FILES['foto']['type'];

        if (is_uploaded_file($image_temp_name)) {
            $imageData = file_get_contents($image_temp_name);
            
            if ($imageData === false) {
                // Erro ao ler o conteúdo do arquivo
                header("Location: cadQuest.php?status=error&message=" . urlencode("Erro ao ler o conteúdo do arquivo."));
                exit();
            }
        } else {
            // Outros erros de upload que não são UPLOAD_ERR_NO_FILE (ex: tamanho limite excedido)
            header("Location: cadQuest.php?status=error&message=" . urlencode("O arquivo não foi enviado corretamente ou erro de upload desconhecido."));
            exit();
        }
    } 
    // Se $_FILES['foto']['error'] for UPLOAD_ERR_NO_FILE (código 4), 
    // a execução continua, mas $imageData e $tipo permanecem NULL. Correto para opcional.
    
    
    // 3. Prepara e Executa a Inserção no Banco de Dados
    
    // 🎯 CORREÇÃO CRÍTICA AQUI: Trocando 'questtext' por 'quest'
    $sql = "
        INSERT INTO tb_quest (quest, alt_a, alt_b, alt_c, alt_d, alt_e, alt_corre, foto, tipo, prova, numQuestao) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        header("Location: cadQuest.php?status=error&message=" . urlencode("Erro ao preparar a consulta: " . $conn->error));
        exit();
    }

    // 🎯 CORREÇÃO AQUI: String de tipos adaptada para 9 strings/BLOB + 2 inteiros (prova e numQuest)
    // sssssss (7 strings) + s (foto BLOB) + s (tipo MIME) + ii (prova INT + numQuestao INT)
    $stmt->bind_param(
        "sssssssssii",
        $pergunta, $alt_a, $alt_b, $alt_c, $alt_d, $alt_e, $alt_corre,
        $imageData, $tipo, // $imageData e $tipo são NULL se a foto não foi enviada
        $prova, $numQuest 
    );

    if ($stmt->execute()) {
        header("Location: cadQuest.php?status=success");
        exit();
    } else {
        header("Location: cadQuest.php?status=error&message=" . urlencode("Erro ao cadastrar a questão: " . $stmt->error));
        exit();
    }

    $stmt->close();
}
$conn->close();
?>
