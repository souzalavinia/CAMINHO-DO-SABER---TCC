<?php
// ==========================================================
// enviar_notificacao_adm.php - Tela de Envio e Gerenciamento
// ==========================================================
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

// Mensagens de feedback (sucesso ou erro após o processamento)
$feedback = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']); 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração - Notificações Globais</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* INÍCIO DO CSS COMPLETO (BASEADO NO SEU LAYOUT ORIGINAL) */
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

        /* --- Header / Menu (Estilos para a estrutura que será incluída) --- */
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

        /* --- Conteúdo Principal / Formulário (Estilos gerais) --- */
        main {
            flex: 1;
            padding: 20px;
            max-width: 1200px; /* Adaptação para a largura da tabela de notificações */
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
        
        /* Estilos do Formulário de Envio */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-dark);
        }
        
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
            box-sizing: border-box;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
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

        /* --- Estilos Específicos da Tabela de Notificações --- */
        #tabela-notificacoes {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }
        #tabela-notificacoes th, #tabela-notificacoes td { 
            padding: 10px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
            font-size: 0.9em; 
            vertical-align: top;
            word-wrap: break-word;
        }
        #tabela-notificacoes th { 
            background-color: var(--primary-color); 
            color: white; 
        }
        
        /* Larguras das colunas */
        #tabela-notificacoes th:nth-child(1), #tabela-notificacoes td:nth-child(1) { width: 4%; text-align: center; }
        #tabela-notificacoes th:nth-child(2), #tabela-notificacoes td:nth-child(2) { width: 5%; }
        #tabela-notificacoes th:nth-child(3), #tabela-notificacoes td:nth-child(3) { width: 7%; }
        #tabela-notificacoes th:nth-child(4), #tabela-notificacoes td:nth-child(4) { width: 15%; } 
        #tabela-notificacoes th:nth-child(5), #tabela-notificacoes td:nth-child(5) { width: 25%; } 
        #tabela-notificacoes th:nth-child(6), #tabela-notificacoes td:nth-child(6) { width: 9%; }
        #tabela-notificacoes th:nth-child(7), #tabela-notificacoes td:nth-child(7) { width: 10%; }
        #tabela-notificacoes th:nth-child(8), #tabela-notificacoes td:nth-child(8) { width: 15%; }
        #tabela-notificacoes th:nth-child(9), #tabela-notificacoes td:nth-child(9) { width: 10%; text-align: center; }
        
        .tipo-info { color: var(--primary-color); font-weight: 500;}
        .tipo-alerta { color: #ffc107; font-weight: 500;}
        .tipo-sucesso { color: var(--success-color); font-weight: 500;}
        .tipo-erro { color: var(--error-color); font-weight: 500;}

        /* Botão de Exclusão em Massa */
        .btn-excluir-massa { 
            background-color: var(--error-color); 
            color: var(--white); 
            padding: 10px 15px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            margin-bottom: 15px; 
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }
        .btn-excluir-massa:hover:not(:disabled) { background-color: #c82333; transform: translateY(-1px); }
        .btn-excluir-massa:disabled { 
            background-color: var(--medium-gray); 
            cursor: not-allowed; 
            color: var(--dark-gray); 
            box-shadow: none;
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

        .alert-danger, .alert-error {
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
        
        /* --- Responsividade (Mantidas do seu código) --- */
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
                max-width: 100%;
            }
            /* Esconder colunas não essenciais em telas menores */
            #tabela-notificacoes th:nth-child(3), #tabela-notificacoes td:nth-child(3), /* ID Usuário */
            #tabela-notificacoes th:nth-child(6), #tabela-notificacoes td:nth-child(6), /* Tipo */
            #tabela-notificacoes th:nth-child(7), #tabela-notificacoes td:nth-child(7)  /* Status */
            {
                display: none;
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
            .btn-excluir-massa {
                width: 100%;
                justify-content: center;
            }
            /* Esconder mais colunas */
            #tabela-notificacoes th:nth-child(4), #tabela-notificacoes td:nth-child(4) { display: none; } /* Título */
        }
        /* FIM DO CSS COMPLETO */
    </style>
</head>
<body>
    
<?php 
// 🚨 CHAMA O MENU EXISTENTE (Assumindo que está no diretório pai: administrador/menu.php)
include '../menu.php'; 
?>

<main>
    <div class="form-container">
        <h1><i class="fas fa-bell"></i> Administração de Notificações</h1>
        
        <?php if ($feedback): ?>
            <div class="alert alert-<?= $feedback['tipo'] === 'sucesso' ? 'success' : 'danger' ?>">
                <i class="fas fa-<?= $feedback['tipo'] === 'sucesso' ? 'check-circle' : 'times-circle' ?>"></i> 
                <?= htmlspecialchars($feedback['mensagem']) ?>
            </div>
        <?php endif; ?>

        <h2>Disparar Nova Notificação Global</h2>
        <form action="processar_envio_notificacao.php" method="POST">
            
            <div class="form-group">
                <label for="titulo">Título (máx. 150 caracteres):</label>
                <input type="text" id="titulo" name="titulo" maxlength="150" required>
            </div>

            <div class="form-group">
                <label for="mensagem">Mensagem:</label>
                <textarea id="mensagem" name="mensagem" required></textarea>
            </div>

            <div class="form-group">
                <label for="tipo">Tipo de Notificação:</label>
                <select id="tipo" name="tipo" required>
                    <option value="" disabled selected>Selecione o Tipo</option>
                    <option value="info">Informação</option>
                    <option value="alerta">Alerta</option>
                    <option value="sucesso">Sucesso</option>
                    <option value="erro">Erro</option>
                </select>
            </div>
            
            <input type="hidden" name="envio_global" value="true">

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Disparar Notificação para TODOS
            </button>
        </form>
        
        <hr style="margin: 40px 0; border-color: var(--medium-gray);">

        <h2>Histórico das Últimas Notificações</h2>
        
        <button id="btn-deletar-selecionados" class="btn-excluir-massa" disabled>
            <i class="fas fa-trash-alt"></i> Excluir Selecionados (<span id="count-selecionados">0</span>)
        </button>

        <p id="loading-msg">Carregando histórico...</p>
        <table id="tabela-notificacoes">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selecionar-todos"></th> 
                    <th>ID Notif.</th>
                    <th>ID Usuário</th>
                    <th>Título</th>
                    <th>Mensagem</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Data Envio</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                </tbody>
        </table>
        <p id="error-msg" class="alert alert-danger" style="display: none;"><i class="fas fa-exclamation-triangle"></i> Ocorreu um erro ao carregar as notificações.</p>
    </div>
</main>

<script>
    // --- Lógica de Seleção em Massa e Carregamento de Notificações ---
    document.addEventListener('DOMContentLoaded', function() {
        const tabelaCorpo = document.querySelector('#tabela-notificacoes tbody');
        const loadingMsg = document.querySelector('#loading-msg');
        const errorMsg = document.querySelector('#error-msg');
        const selecionarTodos = document.getElementById('selecionar-todos');
        const btnDeletarSelecionados = document.getElementById('btn-deletar-selecionados');
        const countSelecionados = document.getElementById('count-selecionados');

        function atualizarBotoesMassa() {
            const checkboxes = document.querySelectorAll('.checkbox-notificacao');
            const checkboxesChecked = document.querySelectorAll('.checkbox-notificacao:checked');
            const totalCheckboxes = checkboxes.length;
            const selecionadosCount = checkboxesChecked.length;

            countSelecionados.textContent = selecionadosCount;
            btnDeletarSelecionados.disabled = selecionadosCount === 0;
            
            selecionarTodos.checked = (totalCheckboxes > 0 && selecionadosCount === totalCheckboxes);
            selecionarTodos.indeterminate = (selecionadosCount > 0 && selecionadosCount < totalCheckboxes);
        }

        selecionarTodos.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.checkbox-notificacao');
            checkboxes.forEach(cb => {
                cb.checked = selecionarTodos.checked;
            });
            atualizarBotoesMassa();
        });

        tabelaCorpo.addEventListener('change', function(e) {
            if (e.target.classList.contains('checkbox-notificacao')) {
                atualizarBotoesMassa();
            }
        });

        btnDeletarSelecionados.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.checkbox-notificacao:checked');
            if (checkboxes.length === 0) return;
            
            const idsParaExcluir = Array.from(checkboxes).map(cb => cb.value);
            
            if (!confirm(`Tem certeza que deseja EXCLUIR as ${idsParaExcluir.length} notificação(ões) selecionada(s)? Esta ação é irreversível.`)) {
                return;
            }
            
            excluirNotificacao(idsParaExcluir);
        });

        function carregarNotificacoes() {
            tabelaCorpo.innerHTML = ''; 
            loadingMsg.style.display = 'block';
            errorMsg.style.display = 'none';
            selecionarTodos.checked = false;
            selecionarTodos.indeterminate = false;
            btnDeletarSelecionados.disabled = true;
            countSelecionados.textContent = '0';

            fetch('listar_notificacoes_adm.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Falha na requisição de dados.');
                    }
                    return response.json();
                })
                .then(notificacoes => {
                    loadingMsg.style.display = 'none';

                    if (notificacoes.erro) {
                        errorMsg.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Erro do Servidor: ${notificacoes.detalhe || notificacoes.erro}`;
                        errorMsg.style.display = 'flex';
                        return;
                    }

                    if (notificacoes.length === 0) {
                        tabelaCorpo.innerHTML = '<tr><td colspan="9" style="text-align: center; color: var(--dark-gray); padding: 20px;">Nenhuma notificação encontrada nos registros recentes.</td></tr>';
                        return;
                    }

                    notificacoes.forEach(notif => {
                        const tr = document.createElement('tr');
                        
                        tr.innerHTML = `
                            <td><input type="checkbox" class="checkbox-notificacao" value="${notif.id}"></td>
                            <td>${notif.id}</td>
                            <td>${notif.idUsuario}</td>
                            <td>${notif.titulo}</td>
                            <td>${notif.mensagem.substring(0, 50)}${notif.mensagem.length > 50 ? '...' : ''}</td> 
                            <td class="tipo-${notif.tipo}">${notif.tipo.toUpperCase()}</td>
                            <td>${notif.status.replace('_', ' ')}</td>
                            <td>${notif.dataEnvio_formatada}</td>
                            <td style="text-align: center;">
                                <button class="btn-excluir-individual" data-id="${notif.id}"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        `;
                        tabelaCorpo.appendChild(tr);
                    });
                    
                    document.querySelectorAll('.btn-excluir-individual').forEach(button => {
                        button.addEventListener('click', function(e) {
                            const id = e.currentTarget.getAttribute('data-id');
                            if (!confirm(`Tem certeza que deseja EXCLUIR a notificação ID ${id}?`)) return;
                            excluirNotificacao([id]);
                        });
                    });

                    atualizarBotoesMassa();
                })
                .catch(error => {
                    console.error('Erro ao carregar notificações:', error);
                    loadingMsg.style.display = 'none';
                    errorMsg.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Erro: Não foi possível se conectar ao script de listagem.`;
                    errorMsg.style.display = 'flex';
                });
        }

        function excluirNotificacao(ids) {
            const formData = new FormData();
            
            if (ids.length > 1) {
                ids.forEach(id => formData.append('ids[]', id));
            } else {
                formData.append('id', ids[0]);
            }

            fetch('excluir_notificacao.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 && body.sucesso) {
                    alert(body.mensagem);
                    carregarNotificacoes(); 
                } else {
                    alert('Falha ao excluir: ' + (body.erro || 'Erro desconhecido.'));
                }
            })
            .catch(error => {
                console.error('Erro de rede ou processamento:', error);
                alert('Erro de conexão ao tentar excluir a notificação.');
            });
        }

        carregarNotificacoes();
    });

    // --- Script para o Menu Dropdown (Mantido, pois a funcionalidade precisa estar em algum lugar) ---
    if (document.getElementById('userToggle')) {
        document.getElementById('userToggle').addEventListener('click', function() {
            document.getElementById('userDropdown').classList.toggle('show');
        });
            
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
    }
</script>

<footer>
    <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
    <a href="../POLITICA.php">Política de privacidade</a>
</footer>
</body>
</html>