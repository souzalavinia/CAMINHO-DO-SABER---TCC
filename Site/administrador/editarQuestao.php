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
require_once '../conexao/conecta.php'; // Assumindo que $conn é criado aqui

$error = null;
$success = null;

// Verifica se o ID foi passado
if (!isset($_GET['id'])) {
    header("Location: exibirProvas.php");
    exit();
}

$id = $_GET['id'];

// Função para buscar a questão (para ser reutilizada após o POST)
function buscarQuestao($conn, $id) {
    // Busca todos os campos, incluindo a foto
    $sql = "SELECT id, quest, alt_a, alt_b, alt_c, alt_d, alt_e, alt_corre, prova, numQuestao, foto, tipo FROM tb_quest WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) { return null; }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $questao = $result->fetch_assoc();
    $stmt->close();
    return $questao;
}

// Buscando a questão antes do POST para ter os dados iniciais
$questao = buscarQuestao($conn, $id);

if (!$questao) {
    header("Location: exibirProvas.php");
    exit();
}


// --- PROCESSAMENTO DO FORMULÁRIO DE EDIÇÃO ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ATENÇÃO: A LÓGICA DE EXCLUSÃO/UPLOAD DE IMAGEM FOI REMOVIDA DAQUI, 
    // AGORA DEVE SER IMPLEMENTADA EM 'alterarImagem.php'

    // 2. Processa a edição normal da questão (Texto e Meta-dados)
    
    // Coleta dados dos campos (TEXTO, NÚMERO, SELEÇÃO)
    $pergunta = $_POST['pergunta'];
    $alt_a = $_POST['alternativaA'];
    $alt_b = $_POST['alternativaB'];
    $alt_c = $_POST['alternativaC'];
    $alt_d = $_POST['alternativaD'];
    $alt_e = $_POST['alternativaE'];
    $alt_corre = $_POST['correta'];
    $prova = $_POST['prova'];
    $numQuestao = $_POST['numQuestao'];

    // Define os campos UPDATE fixos (9 campos de texto/número). Imagem não é atualizada aqui.
    $update_fields = "quest = ?, alt_a = ?, alt_b = ?, alt_c = ?, alt_d = ?, alt_e = ?, alt_corre = ?, prova = ?, numQuestao = ?";
    $bind_types = "sssssssii"; // 7 strings (s) + 2 inteiros (i)
    $bind_params = [$pergunta, $alt_a, $alt_b, $alt_c, $alt_d, $alt_e, $alt_corre, $prova, $numQuestao];
    
    
    // 4. Finaliza a Query com o ID no WHERE
    $sql_update = "UPDATE tb_quest SET " . $update_fields . " WHERE id = ?";
    $bind_types .= "i";
    $bind_params[] = $id;

    $stmt_update = $conn->prepare($sql_update);
    
    if ($stmt_update === false) {
          $error = "Erro ao preparar a query de atualização: " . $conn->error . " | Query: " . $sql_update;
    } else {
        // --- BIND DINÂMICO USANDO REFERÊNCIAS ---
        $bind_args = array($bind_types);
        // Cria referências
        for ($i = 0; $i < count($bind_params); $i++) { 
            $bind_args[] = &$bind_params[$i]; 
        }
        
        // Chama bind_param com o array dinâmico
        if (!call_user_func_array(array($stmt_update, 'bind_param'), $bind_args)) {
              $error = "Erro ao vincular parâmetros. Erro: " . $stmt_update->error;
        } else {
            
            // 6. Executa a query
            if ($stmt_update->execute()) {
                $_SESSION['success_message'] = "Questão (texto e dados) atualizada com sucesso!";
                // Recarrega os dados (não a página completa, para evitar reenvio do formulário)
                $questao = buscarQuestao($conn, $id); 
            } else {
                $error = "Erro ao executar a atualização da questão: " . $stmt_update->error;
            }
        }
        $stmt_update->close();
    }
}

// Busca a lista de provas para o select (Para preencher o dropdown)
$sql_provas = "SELECT id, nome FROM tb_prova";
$result_provas = $conn->query($sql_provas); 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Questão - Caminho do Saber</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d4b9e;
            --primary-dark: #0a3a7a;
            --primary-light: #3a6cb5;
            --gold-color: #D4AF37;
            --gold-light: #E6C200;
            --gold-dark: #996515;
            --black: #212529;
            --dark-black: #121212;
            --white: #ffffff;
            --light-gray: #f5f7fa;
            --medium-gray: #e0e5ec;
            --dark-gray: #6c757d;
            --success-green: #28a745;
            --danger-red: #dc3545; /* Novo */
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background-color: var(--light-gray);
            color: var(--black);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        main {
            flex: 1;
            padding: 20px;
            max-width: 1000px;
            width: 100%;
            margin: 30px auto;
        }

        .form-container {
            background-color: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 2px solid var(--primary-light);
        }

        h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select,
        .form-group input[type="number"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group textarea {
            min-height: 100px; 
            resize: vertical;
        }
        
        /* Estilos da Imagem e do Botão de Gerenciamento */
        .image-management-section {
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            padding: 15px;
            margin-top: 15px;
            background-color: var(--light-gray);
        }

        .current-image {
            margin: 15px 0;
            text-align: center;
            padding: 15px;
            background-color: var(--white);
            border-radius: var(--border-radius);
        }

        .current-image img {
            max-width: 100%;
            max-height: 250px;
            object-fit: contain;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .image-actions {
            display: flex;
            justify-content: center;
            margin-top: 15px;
        }
        
        .btn-manage-image {
            background-color: var(--success-green);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
        }
        
        .btn-manage-image:hover {
            background-color: #1e7e34;
            transform: translateY(-2px);
        }
        /* Fim dos estilos da Imagem */
        
        /* ... (O restante dos estilos de alternativas, botões, e responsividade foram mantidos iguais ao código original para garantir a consistência visual) ... */

        .alternativas-wrapper {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .alternativa-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            background-color: var(--white);
            transition: var(--transition);
        }
        
        .alternativa-item.is-correct {
            border-color: var(--success-green);
            background-color: #e6f5ea; 
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }

        .alternativa-input-wrapper {
            flex-grow: 1;
        }
        
        .alternativa-input-wrapper label {
            font-weight: 500;
            color: var(--black);
            margin-bottom: 5px;
            display: block;
        }
        
        .radio-container {
            flex-shrink: 0; 
            padding-top: 5px; 
        }
        
        .radio-container input[type="radio"] {
            opacity: 0;
            position: absolute;
        }

        .radio-checkmark {
            display: block;
            width: 25px;
            height: 25px;
            border: 2px solid var(--dark-gray);
            border-radius: 50%;
            cursor: pointer;
            position: relative;
            transition: var(--transition);
        }

        .radio-container input[type="radio"]:checked + .radio-checkmark {
            border-color: var(--success-green);
            background-color: var(--success-green);
        }

        .radio-container input[type="radio"]:checked + .radio-checkmark::after {
            content: "\f00c"; 
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 14px;
            color: var(--white);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Botões */
        .btn-submit {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: var(--white);
            border: none;
            padding: 15px 30px;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: 15px;
            box-shadow: var(--box-shadow);
        }

        .btn-submit:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
        }

        .btn-back {
            background: linear-gradient(to right, var(--dark-gray), var(--black));
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-back:hover {
            background: linear-gradient(to right, var(--black), var(--dark-gray));
            transform: translateY(-2px);
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            gap: 10px;
        }

        /* Mensagens de feedback */
        .feedback-message {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: 500;
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

        /* Responsividade */
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .alternativa-item {
                flex-direction: column;
            }
            
            .radio-container {
                align-self: flex-start;
                padding-top: 0;
            }
            
            .alternativa-input-wrapper {
                width: 100%;
            }

            .image-actions {
                justify-content: center;
            }

            .button-group {
                flex-direction: column;
            }
        }
        
        /* FOOTER SIMPLES E FUNCIONAL */
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

    </style>
</head>
<body>
    <?php // include 'menu.php'; ?>
    <main>
    <div class="form-container">
        <h1><i class="fas fa-edit"></i> Editar Questão #<?php echo htmlspecialchars($questao['numQuestao']); ?></h1>
        
        <?php if (isset($error)): ?>
            <div class="feedback-message error-message">
                <strong>Erro:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="feedback-message success-message">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <form action="editarQuestao.php?id=<?php echo $id; ?>" method="post"> 
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="numQuestao">Número da Questão</label>
                    <input type="number" id="numQuestao" name="numQuestao" value="<?php echo htmlspecialchars($questao['numQuestao']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="prova">Prova Associada</label>
                    <select id="prova" name="prova" required>
                        <?php 
                        // Exibindo as provas para seleção
                        if (isset($result_provas) && $result_provas && $result_provas->num_rows > 0) {
                            $result_provas->data_seek(0); 
                            while ($row = $result_provas->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>" <?php echo ($row['id'] == $questao['prova']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['nome']); ?>
                                </option>
                            <?php endwhile; 
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="pergunta">Pergunta / Enunciado da Questão</label>
                <textarea id="pergunta" name="pergunta" required><?php echo htmlspecialchars($questao['quest']); ?></textarea>
            </div>
            
            <div class="image-management-section">
                <label>Imagem Associada ao Enunciado</label>
                
                <?php if (!empty($questao['foto'])): ?>
                    <div class="current-image">
                        <p>Imagem atual:</p>
                        <img src="data:<?php echo htmlspecialchars($questao['tipo']); ?>;base64,<?php echo base64_encode($questao['foto']); ?>" alt="Imagem atual da questão">
                    </div>
                    <div class="image-actions">
                         <a href="alterarImagem.php?id=<?php echo $id; ?>" class="btn-manage-image">
                            <i class="fas fa-sync-alt"></i> Alterar ou Remover Imagem
                        </a>
                    </div>
                <?php else: ?>
                    <div class="current-image" style="padding: 30px;">
                        <p style="color: var(--danger-red); font-style: italic;">Nenhuma imagem anexada.</p>
                    </div>
                    <div class="image-actions">
                         <a href="alterarImagem.php?id=<?php echo $id; ?>" class="btn-manage-image">
                            <i class="fas fa-upload"></i> Adicionar Imagem
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group" style="margin-top: 25px;">
                <label>Alternativas e Correção (Clique no círculo para marcar)</label>
                <div class="alternativas-wrapper">
                    
                    <?php
                    $alternativas = ['A', 'B', 'C', 'D', 'E'];
                    $campos = ['alt_a', 'alt_b', 'alt_c', 'alt_d', 'alt_e'];
                    
                    for ($i = 0; $i < count($alternativas); $i++):
                        $letra = $alternativas[$i];
                        $campo_nome = $campos[$i];
                        $is_correta = ($questao['alt_corre'] == $letra);
                        $item_class = $is_correta ? 'alternativa-item is-correct' : 'alternativa-item';
                    ?>
                    
                    <div class="<?php echo $item_class; ?>" id="item-<?php echo $letra; ?>">
                        
                        <div class="radio-container">
                            <input 
                                type="radio" 
                                id="correta_<?php echo $letra; ?>" 
                                name="correta" 
                                value="<?php echo $letra; ?>" 
                                <?php echo $is_correta ? 'checked' : ''; ?> 
                                required
                            >
                            <label for="correta_<?php echo $letra; ?>" class="radio-checkmark"></label>
                        </div>

                        <div class="alternativa-input-wrapper">
                            <label for="alternativa<?php echo $letra; ?>">Alternativa <?php echo $letra; ?></label>
                            <textarea 
                                id="alternativa<?php echo $letra; ?>" 
                                name="alternativa<?php echo $letra; ?>" 
                                rows="2" 
                                required
                            ><?php echo htmlspecialchars($questao[$campo_nome]); ?></textarea>
                        </div>
                    </div>
                    
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="button-group">
                <a href="mostraQuest.php?id=<?php echo $questao['prova']; ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar para a Prova
                </a>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Salvar Alterações (Texto e Dados)
                </button>
            </div>
        </form>

    </div>
</main>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
        <a href="../POLITICA.php">Política de privacidade</a>
    </footer>

<script>
    // Script JavaScript para controle visual das alternativas
    document.addEventListener('DOMContentLoaded', function() {
        const radioButtons = document.querySelectorAll('input[name="correta"]');
        
        function updateVisualState() {
            document.querySelectorAll('.alternativa-item').forEach(item => {
                item.classList.remove('is-correct');
            });

            radioButtons.forEach(radio => {
                if (radio.checked) {
                    const letra = radio.value;
                    const parentItem = document.getElementById(`item-${letra}`);
                    if (parentItem) {
                        parentItem.classList.add('is-correct');
                    }
                }
            });
        }
        
        updateVisualState();

        radioButtons.forEach(radio => {
            radio.addEventListener('change', updateVisualState);
        });
    });
</script>
</body>
</html>