<?php
session_start();

// Garante que o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

// Converte o tipo de usuário para minúsculas para garantir a validação
$tipoUsuarioSessao = strtolower($_SESSION['tipoUsuario'] ?? '');

// Verifica se o tipo de usuário tem permissão de acesso
if ( $tipoUsuarioSessao !== 'administrador') {
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
}

// 1. INCLUI A CONEXÃO E DEVE MANTÊ-LA ABERTA
// ATENÇÃO: Verifique se o caminho para 'conecta.php' está correto para sua estrutura de pastas.
require_once '../conexao/conecta.php'; 

$error = null;
$success = null;

// Verifica se o ID da questão foi passado
if (!isset($_GET['id'])) {
    header("Location: exibirProvas.php");
    exit();
}

$id = $_GET['id'];

// Função para buscar a questão (apenas os dados necessários para o formulário)
function buscarQuestao($conn, $id) {
    // Busca id, id da prova, número da questão, a foto (BLOB) e o tipo MIME
    $sql = "SELECT id, prova, numQuestao, foto, tipo FROM tb_quest WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) { return null; }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $questao = $result->fetch_assoc();
    $stmt->close();
    return $questao;
}

// Buscando a questão antes do POST
$questao = buscarQuestao($conn, $id);

if (!$questao) {
    // Redireciona se a questão não for encontrada
    header("Location: exibirProvas.php");
    exit();
}

// Variável para armazenar o ID da prova para o botão de voltar
$provaId = $questao['prova'];

// --- PROCESSAMENTO DO FORMULÁRIO ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Processa a exclusão da imagem
    if (isset($_POST['apagar_imagem'])) {
        $sql_delete_img = "UPDATE tb_quest SET foto = NULL, tipo = NULL WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete_img);
        
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $id);
            if ($stmt_delete->execute()) {
                $_SESSION['success_message'] = "Imagem apagada com sucesso!";
                // Redireciona para evitar reenvio do formulário de exclusão
                header("Location: alterarImagem.php?id=" . $id);
                exit();
            } else {
                $error = "Erro ao apagar a imagem: " . $stmt_delete->error;
            }
            $stmt_delete->close();
        } else {
             $error = "Erro ao preparar a query de exclusão: " . $conn->error;
        }
        
    } 
    
    // 2. Processa o upload de uma nova imagem
    elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK && $_FILES['foto']['size'] > 0) {
        
        $tipo = $_FILES['foto']['type'];
        $temp_name = $_FILES['foto']['tmp_name'];
        
        // ⚠️ NOVO MÉTODO: LER O CONTEÚDO INTEIRO DO ARQUIVO PARA UM BIND SIMPLES
        $conteudo_foto = file_get_contents($temp_name);
        
        if ($conteudo_foto === FALSE) {
            $error = "Erro ao ler o conteúdo do arquivo temporário.";
        } else {
            
            // Query de UPDATE que insere a nova foto e o novo tipo
            $sql_update_img = "UPDATE tb_quest SET foto = ?, tipo = ? WHERE id = ?";
            
            $stmt_update = $conn->prepare($sql_update_img);
            
            if ($stmt_update === false) {
                 $error = "Erro ao preparar a query de atualização de imagem: " . $conn->error;
            } else {
                
                // ⚠️ BIND DIRETO: O 'b' representa BLOB e é o tipo recomendado pelo MySQLi.
                // Isso é muito mais simples e confiável do que send_long_data para a maioria dos uploads.
                $stmt_update->bind_param("ssi", $conteudo_foto, $tipo, $id); 
                
                // Se a imagem for muito grande, o PHP tentará executar o upload e
                // o servidor pode estourar o limite de tempo/memória, mas a chance de salvar uma imagem
                // de tamanho razoável (até 16MB, por exemplo) é maior.
                
                if ($stmt_update->execute()) {
                    $_SESSION['success_message'] = "Nova imagem carregada com sucesso!";
                    // Redireciona para evitar reenvio do formulário de upload
                    header("Location: alterarImagem.php?id=" . $id);
                    exit();
                } else {
                    // Se falhar, esta mensagem é CRÍTICA.
                    $error = "Erro ao executar o upload da imagem. MySQLi Error: " . $stmt_update->error;
                }
                $stmt_update->close();
            }
        }
    }
    
    // Recarrega os dados para exibir o estado atualizado após erro
    $questao = buscarQuestao($conn, $id);
}

// Captura a mensagem de sucesso e a remove da sessão
$success_message = $_SESSION['success_message'] ?? null;
if (isset($_SESSION['success_message'])) {
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Imagem da Questão #<?php echo htmlspecialchars($questao['numQuestao']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* INÍCIO DO CSS (MANTIDO PARA CONSISTÊNCIA VISUAL) */
        :root {
            --primary-color: #0d4b9e;
            --primary-dark: #0a3a7a;
            --gold-color: #D4AF37;
            --black: #212529;
            --dark-black: #121212;
            --white: #ffffff;
            --light-gray: #f5f7fa;
            --medium-gray: #e0e5ec;
            --dark-gray: #6c757d;
            --success-green: #28a745;
            --danger-red: #dc3545;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        main {
            flex: 1;
            padding: 20px;
            max-width: 700px;
            width: 100%;
            margin: 30px auto;
        }

        .container {
            background-color: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 2px solid var(--primary-color);
            text-align: center;
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        .upload-form-section, .image-status-section {
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
            background-color: var(--light-gray);
        }

        .current-image {
            margin: 15px 0;
            text-align: center;
            padding: 10px;
            background-color: var(--white);
            border-radius: var(--border-radius);
            border: 1px solid var(--medium-gray);
        }

        .current-image img {
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .no-image {
            color: var(--dark-gray);
            font-style: italic;
            padding: 20px;
            border: 2px dashed var(--medium-gray);
            border-radius: var(--border-radius);
            text-align: center;
        }
        
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            margin-top: 15px;
            width: 100%;
            justify-content: center;
        }

        .btn-upload {
            background-color: var(--success-green);
            color: var(--white);
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
        }
        .btn-upload:hover { background-color: #1e7e34; transform: translateY(-2px); }
        
        .btn-delete {
            background-color: var(--danger-red);
            color: var(--white);
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
            font-size: 0.9rem;
            padding: 10px 20px;
            width: auto;
            display: block; /* Para ocupar a largura completa do contêiner */
            margin-left: auto;
            margin-right: auto;
        }
        .btn-delete:hover { background-color: #c82333; transform: translateY(-1px); }

        .btn-back {
            background-color: var(--dark-gray);
            color: var(--white);
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
            margin-top: 20px;
        }
        .btn-back:hover { background-color: var(--black); transform: translateY(-2px); }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px dashed var(--medium-gray);
            border-radius: var(--border-radius);
            background-color: var(--white);
            transition: var(--transition);
        }

        /* Mensagens de feedback */
        .feedback-message {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: 500;
            text-align: left;
        }

        .error-message {
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        
        footer {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-black));
            color: var(--white);
            text-align: center;
            padding: 20px 0;
            width: 100%;
            border-top: 3px solid var(--gold-color);
            position: relative;
            bottom: 0;
            margin-top: auto;
        }

        footer p {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        footer a {
            color: var(--gold-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        footer a:hover {
            color: var(--gold-light);
            text-decoration: underline;
        }
        /* FIM DO CSS */
    </style>
</head>
<body>
    <main>
        <div class="container">
            <h1><i class="fas fa-image"></i> Gerenciar Imagem da Questão #<?php echo htmlspecialchars($questao['numQuestao']); ?></h1>
            
            <?php if (isset($error)): ?>
                <div class="feedback-message error-message">
                    <strong>Erro:</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success_message)): ?>
                <div class="feedback-message success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <div class="image-status-section">
                <h2>Imagem Atual</h2>
                <?php if (!empty($questao['foto'])): ?>
                    <div class="current-image">
                        <img src="data:<?php echo htmlspecialchars($questao['tipo']); ?>;base64,<?php echo base64_encode($questao['foto']); ?>" alt="Imagem da questão">
                    </div>
                    
                    <p style="text-align: center; margin-bottom: 15px; color: var(--dark-gray); font-size: 0.9em;">**Atenção:** O novo upload substituirá a imagem atual.</p>

                    <form method="post" action="alterarImagem.php?id=<?php echo $id; ?>" onsubmit="return confirm('Tem certeza que deseja APAGAR permanentemente esta imagem? Esta ação não pode ser desfeita.');">
                        <input type="hidden" name="apagar_imagem" value="1">
                        <button type="submit" class="btn-delete">
                            <i class="fas fa-trash"></i> Apagar Imagem Atual
                        </button>
                    </form>
                <?php else: ?>
                    <div class="no-image">
                        <i class="fas fa-times-circle"></i> Nenhuma imagem associada a esta questão.
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="upload-form-section">
                <h2><?php echo !empty($questao['foto']) ? 'Substituir Imagem' : 'Adicionar Imagem'; ?></h2>
                <p style="margin-bottom: 15px; color: var(--primary-dark); font-size: 0.9em;">Selecione um novo arquivo de imagem (JPG, PNG, GIF) para fazer o upload.</p>
                
                <form action="alterarImagem.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="foto"><i class="fas fa-file-upload"></i> Selecionar Novo Arquivo:</label>
                        <input type="file" id="foto" name="foto" accept="image/jpeg, image/png, image/gif" required>
                    </div>
                    
                    <button type="submit" class="btn-action btn-upload">
                        <i class="fas fa-cloud-upload-alt"></i> Upload e Salvar
                    </button>
                </form>
            </div>
            
            <hr style="margin: 20px 0; border-color: #eee;">
            
            <a href="editarQuestao.php?id=<?php echo $id; ?>" class="btn-action btn-back">
                <i class="fas fa-arrow-left"></i> Voltar para Edição da Questão
            </a>
            
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
        <a href="../POLITICA.php">Política de privacidade</a>
    </footer>

</body>
</html>