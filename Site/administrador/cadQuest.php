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
    // Se não for um administrador, destrói a sessão e redireciona
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
}

require_once '../conexao/conecta.php';

$success_message = '';
$error_message = '';

// Captura mensagens de status do redirecionamento
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $success_message = "Questão cadastrada com sucesso!";
    } elseif ($_GET['status'] === 'error' && isset($_GET['message'])) {
        $error_message = htmlspecialchars($_GET['message']);
    }
}

$provas = [];

// Consulta para buscar as provas cadastradas
$sql_provas = "SELECT id, nome FROM tb_prova";
$result_provas = $conn->query($sql_provas);
if ($result_provas === false) {
    die("Erro na consulta de provas: " . $conn->error);
}
if ($result_provas->num_rows > 0) {
    while ($row = $result_provas->fetch_assoc()) {
        $provas[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Questões</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* INÍCIO DO CSS COMPLETO (BASEADO NO CSS UNIFICADO ANTERIOR) */
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
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --gold-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --success-color: #28a745;
            --error-color: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background-color: var(--light-gray);
            color: var(--black);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- Header / Menu --- */
        header {
            width: 100%;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color), var(--dark-black));
            padding: 20px;
            border-bottom: 5px solid var(--gold-color);
            box-shadow: var(--box-shadow);
            position: relative;
        }

        .header-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            height: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo img {
            height: 70px;
            transition: var(--transition);
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        .site-title, .title {
            font-size: 2rem;
            color: var(--white);
            font-weight: 600;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        /* Menu do Usuário */
        .user-menu {
            position: absolute;
            right: 20px;
            top: 20px;
            z-index: 100;
        }

        .user-toggle {
            background-color: var(--primary-dark);
            color: var(--white);
            border: none;
            padding: 10px 15px;
            border-radius: var(--border-radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
        }

        .user-toggle:hover {
            background-color: var(--gold-color);
            color: var(--black);
        }

        .user-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            background-color: var(--white);
            min-width: 200px;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            overflow: hidden;
            z-index: 1000;
        }

        .user-dropdown.show {
            display: block;
        }

        .user-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: var(--black);
            text-decoration: none;
            transition: var(--transition);
        }

        .user-dropdown a:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .user-dropdown a i {
            width: 20px;
            text-align: center;
        }
        
        /* Menu de Navegação */
        nav {
            background-color: var(--primary-dark);
            padding: 15px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        nav ul {
            list-style: none;
            display: flex;
            justify-content: center;
            gap: 30px;
        }

        nav ul li a {
            color: var(--white);
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            position: relative;
        }

        nav ul li a:hover {
            background-color: var(--gold-color);
            color: var(--dark-black);
        }

        nav ul li a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--gold-color);
            transition: var(--transition);
        }

        nav ul li a:hover::after {
            width: 100%;
        }

        /* --- Formulário Principal --- */
        main {
            flex: 1;
            padding: 20px;
            max-width: 900px;
            width: 100%;
            margin: 30px auto;
        }

        .form-container {
            background-color: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 2px solid var(--primary-color);
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
            font-weight: 500;
            color: var(--primary-dark);
        }
        
        /* Estilo para o grupo de alternativas */
        .alternativa-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alternativa-group input[type="text"] {
            flex-grow: 1;
        }
        
        /* Estilo específico para o checkbox de Correta */
        .alternativa-group .check-label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            color: var(--success-color);
            white-space: nowrap; /* Impede que a palavra "Correta" quebre */
            padding-right: 10px;
        }
        
        .alternativa-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--success-color); /* Colore o checkbox no navegador */
            margin: 0;
        }


        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--gold-color);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px dashed var(--medium-gray);
            border-radius: var(--border-radius);
            background-color: var(--light-gray);
            transition: var(--transition);
        }

        .form-group input[type="file"]:hover {
            border-color: var(--gold-color);
        }

        .btn-submit {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: var(--white);
            border: none;
            padding: 12px 25px;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: 10px;
            box-shadow: var(--box-shadow);
        }

        .btn-submit:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
        }

        /* --- Feedback / Alertas --- */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            color: var(--success-color);
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: var(--error-color);
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert i {
            font-size: 1.2rem;
        }
        
        /* --- Footer --- */
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

        /* --- Responsividade --- */
        @media screen and (max-width: 992px) {
            .header-container {
                flex-direction: column;
                height: auto;
            }
            header {
                height: auto;
                padding-bottom: 10px;
            }
            .user-menu {
                position: static;
                margin-top: 10px;
            }
            .user-toggle {
                width: 100%;
                justify-content: center;
            }
            .user-dropdown {
                width: 100%;
                left: 0;
                right: 0;
                top: 50px;
            }
            .site-title {
                font-size: 1.8rem;
            }
            nav ul {
                flex-wrap: wrap;
                gap: 15px;
            }
            main {
                padding: 15px;
                margin: 20px auto;
            }
        }

        @media (max-width: 576px) {
            .site-title {
                font-size: 1.5rem;
            }
            .form-container {
                padding: 15px;
            }
            h1 {
                font-size: 1.5rem;
            }
            .form-group input, .form-group textarea, .form-group select {
                padding: 10px 12px;
            }
            .alternativa-group {
                flex-wrap: wrap; /* Permite quebrar em telas muito pequenas */
                gap: 5px;
            }
            .alternativa-group input[type="text"] {
                width: 100%; /* Ocupa a largura total em modo quebra */
            }
            .alternativa-group .check-label {
                order: 3; /* Move a checkbox para baixo se quebrar (opcional) */
                margin-top: 5px;
            }
        }
        /* FIM DO CSS COMPLETO */
    </style>
</head>
<body>
    <?php 
    // Garanta que este arquivo existe e contém a estrutura de header/menu
    include 'menu.php'; 
    ?>

    <main>
        <div class="form-container">
            <h1><i class="fas fa-question-circle"></i> Cadastro de Questões</h1>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form action="CADASTRAR_IAMGEM_E_QUESTAO.php" method="post" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="numQuest">Número da Questão</label>
                    <input type="number" id="numQuest" name="numQuest" required>
                </div>

                <div class="form-group">
                    <label for="pergunta">Título/Enunciado da Questão</label>
                    <textarea id="pergunta" name="pergunta" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="foto">Imagem da Questão (opcional)</label>
                    <input type="file" id="foto" name="foto" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="prova">Prova</label>
                    <select id="prova" name="prova" required>
                        <option value="">Selecione uma prova</option>
                        <?php if (empty($provas)): ?>
                            <option disabled>Nenhuma prova cadastrada</option>
                        <?php else: ?>
                            <?php foreach ($provas as $prova): ?>
                                <option value="<?= htmlspecialchars($prova['id']) ?>"><?= htmlspecialchars($prova['nome']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <hr style="margin: 25px 0; border-color: var(--medium-gray);">
                <h2>Alternativas e Resposta Correta</h2>

                <div class="form-group">
                    <label for="alternativaA">Alternativa A</label>
                    <div class="alternativa-group">
                        <input type="text" id="alternativaA" name="alternativaA" required>
                        <label for="corretaA" class="check-label" title="Marcar como Correta">
                            <i class="fas fa-check"></i> Correta
                            <input type="checkbox" id="corretaA" name="correta" value="A" onclick="handleCheckbox(this)">
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="alternativaB">Alternativa B</label>
                    <div class="alternativa-group">
                        <input type="text" id="alternativaB" name="alternativaB" required>
                        <label for="corretaB" class="check-label" title="Marcar como Correta">
                            <i class="fas fa-check"></i> Correta
                            <input type="checkbox" id="corretaB" name="correta" value="B" onclick="handleCheckbox(this)">
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="alternativaC">Alternativa C</label>
                    <div class="alternativa-group">
                        <input type="text" id="alternativaC" name="alternativaC" required>
                        <label for="corretaC" class="check-label" title="Marcar como Correta">
                            <i class="fas fa-check"></i> Correta
                            <input type="checkbox" id="corretaC" name="correta" value="C" onclick="handleCheckbox(this)">
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="alternativaD">Alternativa D</label>
                    <div class="alternativa-group">
                        <input type="text" id="alternativaD" name="alternativaD" required>
                        <label for="corretaD" class="check-label" title="Marcar como Correta">
                            <i class="fas fa-check"></i> Correta
                            <input type="checkbox" id="corretaD" name="correta" value="D" onclick="handleCheckbox(this)">
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="alternativaE">Alternativa E</label>
                    <div class="alternativa-group">
                        <input type="text" id="alternativaE" name="alternativaE" required>
                        <label for="corretaE" class="check-label" title="Marcar como Correta">
                            <i class="fas fa-check"></i> Correta
                            <input type="checkbox" id="corretaE" name="correta" value="E" onclick="handleCheckbox(this)">
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Cadastrar Questão
                </button>
            </form>
        </div>
    </main>
    
    <script>
    // Função para garantir que apenas um checkbox 'correta' esteja selecionado (Resposta Única)
    function handleCheckbox(checkbox) {
        var checkboxes = document.getElementsByName('correta');
        checkboxes.forEach((item) => {
            if (item !== checkbox) item.checked = false;
        });
    }

    // Validação final antes do envio para garantir que uma resposta foi marcada
    document.querySelector('form').addEventListener('submit', function(event) {
        var corretaChecked = false;
        var checkboxes = document.getElementsByName('correta');
        
        checkboxes.forEach((item) => {
            if (item.checked) {
                corretaChecked = true;
            }
        });

        if (!corretaChecked) {
            event.preventDefault(); // Impede o envio
            alert('Por favor, marque uma das alternativas como a resposta correta.');
            // Opcional: Adicionar classe de erro visual no form
        }
    });

    // Menu do usuário - Mantenha este script para que o menu funcione
        document.getElementById('userToggle').addEventListener('click', function() {
            document.getElementById('userDropdown').classList.toggle('show');
        });
        
        // Fechar menu quando clicar fora
        window.addEventListener('click', function(event) {
            if (!event.target.matches('.user-toggle') && !event.target.closest('.user-toggle')) {
                var dropdowns = document.getElementsByClassName("user-dropdown");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        });
    </script>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
        <a href="../POLITICA.php">Política de privacidade</a>
    </footer>
</body>
</html>