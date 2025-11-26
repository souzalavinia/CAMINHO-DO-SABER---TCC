<?php
// menu.php — Header reutilizável
// A sessão já será iniciada e validada pelo arquivo que o inclui.

// Inclua o arquivo de configuração, se necessário
require_once __DIR__ . '/../config.php';

// Define o nome de usuário para o menu, garantindo que não haja erro se a sessão não existir
$userName = isset($_SESSION['nome']) ? htmlspecialchars(trim($_SESSION['nome'])) : 'Menu';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Principal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* ... Seu CSS (style) aqui ... */
        :root {
             /* ... (Seu CSS Variavel) ... */
             --azul-primario: #0d4b9e;
             --azul-claro: rgba(13, 75, 158, 0.5);
             --azul-escuro: #0a3a7a;
             --gold-color: #D4AF37;
             --branco: #ffffff;
             --preto: #333333;
             --destaque: #1283c5;
             --sombra: 0 4px 12px rgba(0, 0, 0, 0.1);
             --transicao: all 0.3s ease;
             --borda-arredondada: 8px;
         }
 
         /* ====== Header ====== */
         header.cs-header {
             position: fixed;
             top: 0; left: 0; width: 100%;
             background: var(--azul-claro);
             padding: 15px 0;
             backdrop-filter: blur(8px);
             z-index: 1000;
             box-shadow: var(--sombra);
             border-bottom: 4px solid var(--gold-color);
         }
 
         .cs-container {
             width: 90%;
             max-width: 1200px;
             margin: 0 auto;
             display: flex;
             justify-content: space-between;
             align-items: center;
         }
 
         .cs-logo img { height: 60px; transition: var(--transicao); }
         .cs-logo img:hover { transform: scale(1.05); }
 
         .cs-title {
             font-family: 'Merriweather', serif;
             font-weight: 700;
             font-size: 1.8rem;
             color: var(--branco);
             text-align: center;
             flex-grow: 1;
             margin: 0 20px;
             text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
         }
 
         /* ====== Menu do usuário ====== */
         .cs-user-menu { position: relative; margin-left: 10px; }
 
         .cs-user-toggle {
             display: flex; align-items: center; justify-content: center;
             gap: 8px;
             padding: 10px 14px;
             border-radius: 999px;
             background-color: var(--branco);
             color: var(--azul-primario);
             border: 2px solid transparent;
             transition: var(--transicao);
             cursor: pointer;
             font-weight: 600;
         }
         .cs-user-toggle:hover {
             background-color: transparent;
             border-color: var(--branco);
             color: var(--branco);
             transform: translateY(-2px);
         }
         .cs-user-toggle i { font-size: 1rem; }
 
         .cs-user-dropdown {
             display: none;
             position: absolute; right: 0; top: 48px;
             background-color: var(--branco);
             min-width: 240px;
             box-shadow: var(--sombra);
             border-radius: var(--borda-arredondada);
             z-index: 1000;
             overflow: hidden;
             border: 1px solid rgba(13,75,158,0.1);
             animation: csFadeIn .25s ease-out;
         }
         .cs-user-dropdown.cs-show { display: block; }
 
         .cs-user-dropdown a,
         .cs-user-dropdown button {
             display: flex; align-items: center; gap: 10px;
             padding: 12px 15px;
             color: var(--preto);
             text-decoration: none;
             transition: var(--transicao);
             font-size: 0.95rem;
             background: transparent;
             border: 0;
             width: 100%;
             text-align: left;
             cursor: pointer;
         }
         .cs-user-dropdown a:hover,
         .cs-user-dropdown button:hover {
             background-color: var(--azul-claro);
             color: var(--azul-primario);
         }
         .cs-user-dropdown i { width: 20px; text-align: center; color: var(--azul-primario); }
 
         /* Responsividade */
         @media (max-width: 768px) {
             .cs-container { flex-direction: column; text-align: center; gap: 10px; }
             .cs-logo img { margin-bottom: 6px; height: 48px; }
             .cs-title { margin: 6px 0; font-size: 1.5rem; }
             .cs-user-dropdown { right: auto; left: 0; }
         }
         @keyframes csFadeIn {
             from { opacity: 0; transform: translateY(6px); }
             to   { opacity: 1; transform: translateY(0); }
         }
         header.cs-header { position: static !important; }
    </style>
</head>
<body>

<header class="cs-header">
    <div class="cs-container">
        <div class="cs-logo">
            <img src="../imagem/logonova.png" alt="Logo Caminho do Saber">
        </div>

        <h1 class="cs-title">CAMINHO DO SABER</h1>

        <div class="cs-user-menu">
            <button class="cs-user-toggle" id="csUserToggle" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-user"></i>
                <span><?php echo $userName; ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="cs-user-dropdown" id="csUserDropdown" role="menu">
                <a href="exibirProvas.php"><i class="fas fa-clipboard-list"></i> Provas</a>
                <a href="cadastrarProvas1.php"><i class="fas fa-book-open"></i> Cadastro de Provas</a>
                <a href="cadastrarInst.php"><i class="fas fa-university"></i> Cadastro de Instituição</a>
                <a href="cadQuest.php"><i class="fas fa-book"></i> Cadastro de Questões</a>
                <a href="cadastrarPlanos.php"><i class="fas fa-cube"></i> Cadastro de Planos</a>
                <a href="gerenciarUsuarios.php" style="font-weight:;"><i class="fas fa-users-cog"></i> Gerenciar Usuários</a>
                <a href="notificacao/enviar_notificacao_adm.php" style="font-weight:;"><i class="fas fa-bell"></i> Notificações</a>
                <a href="relatorioProvas.php" role="menuitem"><i class="fas fa-chart-bar"></i> Relatórios</a>
                
                <a href="gerenciarPlanos.php"><i class="fa-solid fa-id-card"></i> Gerenciar Planos</a>
                <a href="configuracoes.php"><i class="fas fa-cog"></i> Configurações</a>
                <a href="/sair.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </div>
    </div>
</header>

<script>
(() => {
    const toggle = document.getElementById('csUserToggle');
    const dropdown = document.getElementById('csUserDropdown');

    if (toggle && dropdown) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = dropdown.classList.toggle('cs-show');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                if (dropdown.classList.contains('cs-show')) {
                    dropdown.classList.remove('cs-show');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && dropdown.classList.contains('cs-show')) {
                dropdown.classList.remove('cs-show');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.focus();
            }
        });
    }

    // Compensa o header fixo no layout
    try {
        const firstMain = document.querySelector('main');
        const computedPT = firstMain ? parseInt(getComputedStyle(firstMain).paddingTop, 10) : 0;
        if (firstMain && (isNaN(computedPT) || computedPT < 120)) {
            firstMain.style.paddingTop = '120px';
        }
    } catch(e) {
        console.error('Erro ao ajustar o padding do main:', e);
    }
})();
</script>

</body>
</html>