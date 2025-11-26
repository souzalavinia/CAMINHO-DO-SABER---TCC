<?php
session_start();

require_once '../conexao/conecta.php';

// Redireciona para o login se o usuário não estiver logado
if (!isset($_SESSION['id'])) {
    header("Location: ../login.html");
    exit();
}

// Processa o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $pergunta   = $_POST['pergunta'];
    $alt_a      = $_POST['alternativaA'];
    $alt_b      = $_POST['alternativaB'];
    $alt_c      = $_POST['alternativaC'];
    $alt_d      = $_POST['alternativaD'];
    $alt_e      = $_POST['alternativaE'];
    $alt_corre  = $_POST['correta'];
    $prova      = $_POST['prova'];
    $numQuest   = $_POST['numQuest'];

    // Processa a imagem
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $image = $_FILES['foto']['tmp_name'];
        $tipo  = $_FILES['foto']['type'];

        if (is_uploaded_file($image)) {
            $imageData = file_get_contents($image);
            if ($imageData === false) {
                header("Location: cadQuest.php?status=error&message=Erro ao ler o conteúdo do arquivo.");
                exit();
            }
        } else {
            header("Location: cadQuest.php?status=error&message=O arquivo não foi enviado corretamente.");
            exit();
        }
    } else {
        header("Location: cadQuest.php?status=error&message=Nenhum arquivo enviado ou erro no upload: " . $_FILES['foto']['error']);
        exit();
    }
    
    // Prepara a consulta
    $stmt = $conn->prepare("
        INSERT INTO tb_quest 
        (quest, alt_a, alt_b, alt_c, alt_d, alt_e, alt_corre, foto, tipo, prova, numQuestao) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt === false) {
        header("Location: cadQuest.php?status=error&message=Erro ao preparar a consulta: " . $conn->error);
        exit();
    }

    // bind_param → 'b' seria ideal para binário (foto), mas o MySQLi trata como string mesmo
    $stmt->bind_param(
        "sssssssssss",
        $pergunta, $alt_a, $alt_b, $alt_c, $alt_d, $alt_e, $alt_corre,
        $imageData, $tipo, $prova, $numQuest
    );

    if ($stmt->execute()) {
        header("Location: cadQuest.php?status=success");
        exit();
    } else {
        header("Location: cadQuest.php?status=error&message=Erro ao cadastrar a questão: " . $stmt->error);
        exit();
    }

    $stmt->close();
}
$conn->close();
?>