<?php
// menu.php — Header reutilizável
// Certifique-se de que a sessão é iniciada
if (session_status() === PHP_SESSION_NONE) session_start();

// Regra simples de autenticação
$isLoggedIn = isset($_SESSION['id']) && (int)$_SESSION['id'] > 0;
// Opcional: exibir nome se existir
$userName = isset($_SESSION['nome']) ? trim($_SESSION['nome']) : '';

// Inclua a configuração (BASE_URL)
// Certifique-se de que 'config.php' existe e define BASE_URL
require_once __DIR__ . '/config.php'; 

// --- CÓDIGO: BUSCAR CONTAGEM REAL DE NOTIFICAÇÕES NÃO LIDAS (MySQLi) ---
$notificationCount = 0;
if ($isLoggedIn && isset($_SESSION['id'])) {
    $idUsuario = (int)$_SESSION['id'];
    
    require_once __DIR__ . '/conexao/conecta.php'; 

    // Assume que $conn está definido e é um objeto mysqli válido
    if (isset($conn) && $conn instanceof mysqli && $conn->connect_error === null) {
        $sql = "SELECT COUNT(*) AS total FROM tb_notificacoes WHERE idUsuario = ? AND status = 'nao_lida'";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param('i', $idUsuario);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $notificationCount = (int)$row['total'];
            $stmt->close();
        }
        // $conn permanece aberta para uso posterior pela página principal
    }
}
// --- FIM DO CÓDIGO PHP DE CONTAGEM ---

?>
<style>
    :root{
        /* Cores Primárias e Secundárias */
        --pri:#0d4b9e;--pri-d:#0a3a7a;--pri-l:#3a6cb5;
        --gold:#D4AF37;--gold-d:#996515;
        --ok:#16a34a;--warn:#f59e0b;--bad:#ef4444; 
        
        /* Cores de Texto e Fundo */
        --txt:#212529;--mut:#6b7280;--bg:#f5f7fa;--white:#fff;
        
        /* Layout e Efeitos */
        --rad:14px;--sh:0 10px 30px rgba(0,0,0,.08);
        --header-h: 120px;
        --transicao: all 0.3s ease;
        --borda-arredondada: 8px;
        --azul-claro-transparente: rgba(13,75,158,0.15); 
        
        /* Variáveis específicas do Menu */
        --azul-primario: #0d4b9e;
        --azul-claro: rgba(13, 75, 158, 0.5); /* Fundo semi-transparente do header */
        --azul-escuro: #0a3a7a;
        --gold-color: #D4AF37;
        --branco: #ffffff;
        --preto: #333333;
        --destaque: #1283c5;
        --sombra: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Montserrat', system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif;background:var(--bg);color:var(--txt)}
    
    .container{max-width:1200px;margin:0 auto;padding:24px}

    /* === Estilos Comuns (Não relacionados ao Menu/Notificações) === */
    /* ... (Mantenha o seu CSS existente) ... */
    .banner{
        background:linear-gradient(135deg,var(--pri),#152238);
        color:#fff;border-radius:var(--rad);padding:22px 22px 18px;box-shadow:var(--sh);
        display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap
    }
    .banner i{font-size:1.4rem;color:var(--gold)}
    .banner h2{font-weight:600;margin-bottom:6px;font-size:1.25rem}
    .banner p{opacity:.95;line-height:1.6}

    /* Cards genéricos */
    .card{background:var(--white);border-radius:var(--rad);box-shadow:var(--sh);padding:22px}

    /* Form */
    .grid{display:grid;gap:24px;margin-top:24px}
    @media (min-width: 992px){.grid{grid-template-columns:2fr 1fr}}
    .card h3{font-size:1.15rem;color:var(--pri);margin-bottom:14px}
    .input,.textarea,select{
        width:100%;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;font-size:0.98rem;transition:.2s;background:#fff
    }
    .input:focus,.textarea:focus,select:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 4px rgba(212,175,55,.15)}
    .textarea{min-height:260px;resize:vertical}
    .counter{font-size:.9rem;color:#6b7280;text-align:right;margin-top:6px}
    .btn{display:inline-flex;align-items:center;gap:8px;border:none;border-radius:12px;padding:12px 16px;font-weight:600;cursor:pointer;transition:.2s}
    .btn-primary{background:linear-gradient(90deg,var(--pri),var(--pri-d));color:#fff}
    .btn-primary:hover{filter:brightness(.95);transform:translateY(-1px)}
    .btn-ghost{background:#f3f4f6}
    .btn-danger{background:linear-gradient(90deg,#ef4444,#dc2626);color:#fff}
    .small{font-size:.85rem;color:#6b7280}

    /* KPIs */
    .kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:24px}
    .kpi{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:12px 14px}
    .kpi b{font-size:.8rem;color:#6b7280;display:block}
    .kpi span{font-weight:800;font-size:1.2rem;color:#111827}

    /* Lista */
    .section-title{margin:34px 6px 12px;display:flex;align-items:center;gap:10px;color:#111827}
    .section-title i{color:var(--gold)}
    .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px}
    .r-card{
        background:var(--white);border-radius:16px;box-shadow:var(--sh);padding:18px;border-left:4px solid transparent;transition:.2s;cursor:pointer
    }
    .r-card:hover{transform:translateY(-3px);border-left-color:var(--gold)}
    .r-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
    .r-title{font-size:1rem;color:#111827;font-weight:700;line-height:1.3}
    .r-sub{font-size:.88rem;color:#374151;margin-top:2px}
    .r-meta{display:flex;gap:10px;flex-wrap:wrap;color:#6b7280;font-size:.88rem;margin-top:8px}
    .badge{padding:4px 10px;border-radius:999px;font-size:.78rem;font-weight:600;color:#fff}
    .nota-ouro{background:linear-gradient(90deg,#D4AF37,#f3c969);color:#111827}
    .nota-alta{background:linear-gradient(90deg,#10b981,#059669)}
    .nota-media{background:linear-gradient(90deg,#60a5fa,#2563eb)}
    .nota-baixa{background:linear-gradient(90deg,#f87171,#ef4444)}
    .aderencia-chip{border:1px solid #e5e7eb;background:#fff;padding:3px 8px;border-radius:999px;font-size:.78rem}
    .chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
    .mini-competencias{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin-top:10px}
    .mini-bar{height:8px;background:#f1f5f9;border-radius:999px;overflow:hidden;position:relative}
    .mini-fill{position:absolute;left:0;top:0;bottom:0;background:linear-gradient(90deg,#38bdf8,#0ea5e9)}
    .mini-labels{display:flex;justify-content:space-between;color:#6b7280;font-size:.72rem;margin-top:4px}
    .redacao{background:#fbfbfd;border:1px solid #eef2f7;border-radius:12px;padding:14px;margin-top:6px;line-height:1.8;white-space:pre-wrap}
    .grid-competencias{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-top:14px}
    .comp{border:1px solid #eef2f7;border-radius:12px;padding:12px;background:#fff}
    .comp h4{font-size:.95rem;color:#0f172a;margin-bottom:8px}
    .prog{position:relative;height:10px;background:#f1f5f9;border-radius:999px;overflow:hidden;margin-top:8px}
    .prog-fill{position:absolute;left:0;top:0;bottom:0;background:linear-gradient(90deg,#38bdf8,#0ea5e9)}
    .prog-label{font-size:.8rem;color:#0f172a;margin-top:6px;display:inline-block}
    .com-item{border-left:3px solid var(--gold);background:linear-gradient(180deg,#fff, #fcfcfe);border-radius:0 12px 12px 0;padding:10px 12px;margin-top:10px}


    /* === CSS MELHORADO PARA ITENS DE NOTIFICAÇÃO (COMPACTO) === */
    .cs-notification-item {
        padding: 12px 14px;
        margin-bottom: 8px;
        border-radius: var(--borda-arredondada); 
        border-left-width: 5px; 
        border-left-style: solid;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); 
        transition: background-color 0.3s ease;
        background-color: var(--white);
        cursor: default; 
        position: relative; /* ESSENCIAL para posicionar o botão de deletar */
    }
    .cs-notification-item.nao_lida {
        background-color: var(--azul-claro-transparente); 
    }
    .cs-notification-item:hover {
        background-color: rgba(13, 75, 158, 0.08); 
    }
    .cs-notification-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px; 
        padding-right: 25px; /* ESPAÇO para o botão de deletar */
    }
    .cs-notification-title {
        font-weight: 700;
        color: var(--txt); 
        margin-bottom: 2px;
        font-size: 0.95rem;
    }
    .cs-notification-item.nao_lida .cs-notification-title {
        color: var(--azul-primario);
    }
    .cs-notification-message {
        color: var(--mut); 
        font-size: 0.85rem;
        line-height: 1.3;
    }
    .cs-notification-date {
        display: block;
        font-size: 0.7rem;
        color: #9ca3af; 
        margin-top: 4px;
    }
    .cs-notification-action {
        display: flex;
        flex-shrink: 0; 
        align-items: center;
        padding-top: 2px; 
    }
    .cs-action-btn {
        color: var(--pri); 
        font-size: 0.8rem;
        text-decoration: none;
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px 8px; 
        margin-right: -8px; 
        font-weight: 600;
        transition: color 0.2s, background-color 0.2s;
        white-space: nowrap; 
        border-radius: 6px;
    }
    .cs-action-btn:hover {
        color: var(--branco);
        background-color: var(--pri);
        text-decoration: none;
    }
    .cs-read-status {
        color: var(--ok); 
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    
    /* NOVO: Estilo do Botão de Deletar */
    .cs-delete-btn {
        position: absolute;
        top: 8px; /* Posição superior */
        right: 8px; /* Posição à direita */
        background: none;
        border: none;
        color: var(--mut); /* Cor cinza padrão */
        cursor: pointer;
        font-size: 0.8rem;
        padding: 4px;
        border-radius: 50%;
        transition: color 0.2s, background-color 0.2s;
        line-height: 1;
        z-index: 10; /* Para garantir que fique acima do conteúdo */
    }
    .cs-delete-btn:hover {
        color: var(--bad); /* Cor vermelha ao passar o mouse */
        background-color: rgba(239, 68, 68, 0.1); 
    }

    /* Estilos Específicos por TIPO de notificação */
    .cs-success { border-left-color: var(--ok); }
    .cs-success.nao_lida { background-color: #ecfdf5; }
    .cs-warning { border-left-color: var(--warn); }
    .cs-warning.nao_lida { background-color: #fffbeb; }
    .cs-error { border-left-color: var(--bad); }
    .cs-error.nao_lida { background-color: #fef2f2; }
    .cs-info { border-left-color: var(--pri); }


    /* === CSS DO MENU === */
    /* ... (Mantenha o restante do seu CSS aqui) ... */
    header.cs-header {
        position: fixed; 
        top: 0; left: 0; width: 100%;
        background: var(--azul-claro);
        padding: 15px 0;
        backdrop-filter: blur(8px);
        z-index: 9999; 
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
    .cs-buttons { display: flex; gap: 15px; }

    .cs-btn {
        background-color: var(--branco);
        color: var(--azul-primario);
        padding: 10px 20px;
        border-radius: 30px;
        font-weight: 600;
        border: 2px solid transparent;
        transition: var(--transicao);
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .cs-btn:hover {
        background-color: transparent;
        border-color: var(--branco);
        color: var(--branco);
        transform: translateY(-2px);
    }

    .cs-header-right {
        display: flex;
        align-items: center;
        gap: 20px; 
        position: relative; /* Essencial para posicionar o dropdown da notificação */
    }

    /* Botão de Notificações */
    .cs-notification-toggle {
        display: flex; align-items: center; justify-content: center;
        width: 44px; height: 44px; padding: 0;
        border-radius: 50%;
        background-color: var(--branco);
        color: var(--azul-primario);
        border: 2px solid transparent;
        transition: var(--transicao);
        cursor: pointer;
        position: relative; 
    }
    .cs-notification-toggle:hover {
        background-color: transparent;
        border-color: var(--branco);
        color: var(--branco);
        transform: translateY(-2px);
    }
    .cs-notification-toggle i { font-size: 1.2rem; }

    /* Badge de Notificações */
    .cs-notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background-color: var(--bad); 
        color: var(--white);
        border-radius: 50%;
        padding: 3px 6px;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 1;
        min-width: 20px;
        text-align: center;
        border: 2px solid var(--azul-claro); 
    }

    /* Dropdown de Notificações */
    .cs-notification-wrapper {
        position: relative;
        /* Container para posicionamento */
    }
    .cs-notification-dropdown {
        display: none;
        position: absolute;
        right: -10px; 
        top: 50px;
        background-color: var(--white);
        width: 380px; 
        max-width: 90vw; 
        max-height: 450px;
        overflow-y: auto;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        border-radius: var(--borda-arredondada);
        z-index: 1000;
        border: 1px solid rgba(13, 75, 158, 0.1);
        padding: 10px;
        animation: csFadeIn .25s ease-out;
    }
    .cs-notification-dropdown.cs-show {
        display: block;
    }
    .cs-notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 5px 10px;
        margin-bottom: 5px;
        border-bottom: 1px solid #eee;
    }
    .cs-notification-header h3 {
        font-size: 1.05rem;
        color: var(--azul-primario);
        font-weight: 700;
    }
    .cs-notification-footer {
        text-align: center;
        padding: 10px 0 0;
        border-top: 1px solid #eee;
    }
    .cs-notification-footer a {
        color: var(--pri);
        font-size: 0.85rem;
        text-decoration: none;
        font-weight: 600;
    }
    .cs-notification-footer a:hover {
        color: var(--pri-d);
        text-decoration: underline;
    }
    .cs-no-notifications {
        padding: 10px 10px 5px;
        text-align: center;
        color: var(--mut);
        font-size: 0.9rem;
    }
    .cs-notification-item .cs-delete-btn {
    /* Remove qualquer background padrão e padding que possa causar o azul */
    background: none !important;
    border: none;
    color: #aaa; /* Cor cinza para o ícone da lixeira */
    padding: 5px; 
    margin: -5px; /* Ajusta o alinhamento para o canto */
    transition: color 0.2s, background-color 0.2s;
    font-size: 14px;
}

/* Define o estilo no hover (Corrige o fundo azul e adiciona UX de exclusão) */
.cs-notification-item .cs-delete-btn:hover {
    /* O background-color que estava vindo deve ser corrigido aqui */
    background-color: #f8d7da !important; /* Fundo vermelho sutil (ajuste se quiser transparente) */
    color: #721c24 !important; /* Ícone vermelho escuro */
    border-radius: 4px;
}

    /* Menu do Usuário */
    .cs-user-menu { position: relative; margin-left: 0; }
    .cs-user-toggle {
        display: flex; align-items: center; justify-content: center;
        width: 44px; height: 44px; padding: 0;
        border-radius: 50%;
        background-color: var(--branco);
        color: var(--azul-primario);
        border: 2px solid transparent;
        transition: var(--transicao);
        cursor: pointer;
    }
    .cs-user-toggle:hover {
        background-color: transparent;
        border-color: var(--branco);
        color: var(--branco);
        transform: translateY(-2px);
    }
    .cs-user-toggle i { font-size: 1.2rem; }

    .cs-user-dropdown {
        display: none;
        position: absolute; right: 0; top: 50px;
        background-color: var(--branco);
        min-width: 220px;
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
        background-color: var(--azul-claro-transparente); 
        color: var(--azul-primario);
    }
    .cs-user-dropdown i { width: 20px; text-align: center; color: var(--azul-primario); }

    .cs-user-name {
        color: var(--branco);
        font-size: 0.95rem;
        font-weight: 600;
        white-space: nowrap;
        max-width: 240px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Botão de fechar (Melhorado) */
    .close {
        /* Padrão */
        border: none;
        background: transparent;
        font-size: 1.6rem; 
        color: #6b7280;
        cursor: pointer;
        
        /* Melhoria de Hover */
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s, color 0.2s;
        /* Posição deve ser ajustada no elemento pai se usado em modal/dropdown */
        line-height: 1;
    }
    .close:hover{
        color: var(--bad); 
        background-color: rgba(239, 68, 68, 0.1); 
    }


    /* Keyframes e Responsividade */
    @keyframes csFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
        .cs-container { flex-direction: column; text-align: center; }
        .cs-logo img { margin-bottom: 12px; }
        .cs-title { margin: 10px 0; font-size: 1.5rem; }
        .cs-header-right { flex-direction: row; justify-content: center; gap: 15px; } 
        .cs-user-name { display: none; } 
        .cs-user-dropdown { right: 50%; left: auto; transform: translateX(50%); } 
        .cs-notification-dropdown {
            right: 50%;
            transform: translateX(50%);
            width: 95vw; 
        }
    }
</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

<header class="cs-header" id="csHeader">
    <div class="cs-container">
        <div class="cs-logo">
            <a href="<?= BASE_URL ?>index.php" aria-label="Ir para a página inicial">
                <img src="<?= BASE_URL ?>imagem/logonova.png" alt="Logo Caminho do Saber">
            </a>
        </div>

        <h1 class="cs-title">CAMINHO DO SABER</h1>

        <div class="cs-header-right">
            <?php if (!$isLoggedIn): ?>
                <div class="cs-buttons" role="navigation" aria-label="Acesso">
                    <a href="<?= BASE_URL ?>login.php" class="cs-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="<?= BASE_URL ?>cadastro.html" class="cs-btn"><i class="fas fa-user-plus"></i> Cadastrar-se</a>
                </div>
            <?php else: ?>
                <?php if ($userName !== ''): ?>
                    <div class="cs-user-name" title="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fas fa-user-circle" style="margin-right:6px;"></i>
                        <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="cs-notification-wrapper">
                    <button class="cs-notification-toggle" aria-label="Notificações" id="csNotificationToggle" aria-haspopup="true" aria-expanded="false" aria-controls="csNotificationDropdown">
                        <i class="fas fa-bell" aria-hidden="true"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="cs-notification-badge" id="csNotificationCount"><?php echo $notificationCount; ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="cs-notification-dropdown" id="csNotificationDropdown" role="menu" aria-label="Lista de notificações">
                        
                        <div class="cs-notification-header">
                            <h3>Suas Notificações</h3>
                        </div>
                        <div id="listaNotificacoes" style="padding-bottom:10px;">
                            <p class="cs-no-notifications">Carregando...</p>
                        </div>
                        <div class="cs-notification-footer">
                            <!--<a href="<?= BASE_URL ?>todas_notificacoes.php">Ver todas as notificações</a>-->
                        </div>
                    </div>
                </div>
                
                <div class="cs-user-menu">
                    <button class="cs-user-toggle" id="csUserToggle" aria-haspopup="true" aria-expanded="false" aria-controls="csUserDropdown">
                        <i class="fas fa-bars" aria-hidden="true"></i>
                        <span class="sr-only">Abrir menu do usuário</span>
                    </button>
                    <div class="cs-user-dropdown" id="csUserDropdown" role="menu" aria-label="Menu do usuário">
                        <a href="<?= BASE_URL ?>home.php" role="menuitem"><i class="fas fa-home"></i> Home</a>
                        <a href="<?= BASE_URL ?>exibirProvas.php" role="menuitem"><i class="fas fa-clipboard-list"></i> Provas</a>
                        <a href="<?= BASE_URL ?>simulado.php" role="menuitem"><i class="fas fa-list-check"></i>Simulados</a>
                        <a href="<?= BASE_URL ?>corretor.php" role="menuitem"><i class="fas fa-pen-fancy"></i> Corretor</a>
                        <a href="<?= BASE_URL ?>progresso.php" role="menuitem"><i class="fas fa-chart-line"></i> Progresso</a>
                        <a href="<?= BASE_URL ?>configuracao/configuracoes.php" role="menuitem"><i class="fas fa-cog"></i> Configurações</a>
                        <hr style="margin:6px 0;border:0;border-top:1px solid rgba(13,75,158,0.15)">
                        <a href="<?= BASE_URL ?>sair.php" role="menuitem"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
    // ==============================================
    // CONFIGURAÇÃO DE CAMINHOS (CORREÇÃO DE DIRETÓRIOS)
    // ==============================================
    // Injeta a constante BASE_URL do PHP (que é '/')
    const BASE_PATH = '<?= BASE_URL ?>';
    
    // Garantindo que a URL sempre inicie na raiz do domínio (com apenas uma barra /)
    // Se BASE_PATH for '/', a URL será '/buscar_notificacoes.php'
    // Se BASE_PATH for '/meuprojeto', a URL será '/meuprojeto/buscar_notificacoes.php'
    const URL_PREFIX = BASE_PATH.replace(/\/$/, ''); // Remove barra final se existir
    
    const URL_BUSCAR = URL_PREFIX + '/buscar_notificacoes.php';
    const URL_MARCAR = URL_PREFIX + '/marcar_lida.php';
    const URL_DELETAR = URL_PREFIX + '/deletar_notificacao.php';

    // ==============================================
    // FUNÇÕES DE CONTROLE DE NOTIFICAÇÕES (Escopo Global)
    // ==============================================

    /** * Marca uma notificação específica como lida e recarrega o dropdown.
     * @param {number} id - O ID da notificação a ser marcada.
     */
    async function marcarLidaAndRefreshDropdown(id) {
        try {
            // 1. Marca como lida no servidor (USANDO URL ABSOLUTA)
            const res = await fetch(URL_MARCAR, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id })
            });
            
            if (!res.ok) throw new Error("Falha ao marcar como lida.");

            // 2. Atualiza o conteúdo do dropdown
            await updateNotificationDropdownContent();
            
        } catch (e) {
            console.error("Erro ao marcar notificação como lida:", e);
            alert("Erro ao marcar notificação como lida. Tente novamente.");
        }
    }

    // NOVA FUNÇÃO: DELETAR NOTIFICAÇÃO
    /** * DELETA uma notificação específica e recarrega o dropdown.
     * @param {number} id - O ID da notificação a ser deletada.
     */
    async function deletarNotificacaoAndRefreshDropdown(id) {
        if (!confirm('Tem certeza que deseja remover esta notificação?')) {
            return;
        }

        try {
            // 1. Deleta no servidor (USANDO URL ABSOLUTA)
            const res = await fetch(URL_DELETAR, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id })
            });
            
            const result = await res.json();

            if (!res.ok || !result.success) {
                throw new Error(result.error || "Falha desconhecida ao deletar.");
            }

            // 2. Atualiza o conteúdo do dropdown
            await updateNotificationDropdownContent();
            
        } catch (e) {
            console.error("Erro ao deletar notificação:", e);
            alert("Erro ao deletar notificação. Tente novamente.");
        }
    }

    /** * Busca as notificações via AJAX e atualiza o conteúdo do dropdown. */
    async function updateNotificationDropdownContent() {
        const lista = document.getElementById('listaNotificacoes');
        lista.innerHTML = '<p class="cs-no-notifications">Carregando...</p>';

        try {
            // USANDO URL ABSOLUTA PARA BUSCAR NOTIFICAÇÕES
            const res = await fetch(URL_BUSCAR);
            
            if (!res.ok) {
                // Se der Failed to fetch (Rede), o erro é capturado no catch
                // Se der um erro HTTP (404, 500, etc.), o erro é tratado aqui
                const erro = await res.text();
                throw new Error(`Erro HTTP ${res.status}: ${erro.substring(0, 100)}...`);
            }
            
            const dados = await res.json();
            lista.innerHTML = '';
            
            if (!dados || !dados.length) {
                lista.innerHTML = '<p class="cs-no-notifications">Nenhuma notificação no momento.</p>';
            } else {
                // Renderiza as notificações
                dados.forEach(n => {
                    const item = document.createElement('div');
                    
                    let tipoClasse = 'cs-info'; 
                    if (n.tipo === 'sucesso') tipoClasse = 'cs-success';
                    else if (n.tipo === 'alerta') tipoClasse = 'cs-warning';
                    else if (n.tipo === 'erro') tipoClasse = 'cs-error';

                    let statusClasse = n.status === 'nao_lida' ? 'nao_lida' : 'lida';

                    item.className = `cs-notification-item ${tipoClasse} ${statusClasse}`;

                    // MODIFICAÇÃO AQUI: Deletar agora usa o ícone da lixeira
                    const deleteBtn = `<button class="cs-delete-btn" onclick="deletarNotificacaoAndRefreshDropdown(${n.id})" aria-label="Remover notificação" type="button"><i class="fas fa-trash-alt"></i></button>`;

                    // MODIFICAÇÃO AQUI: Marcar como lida agora usa o ícone de 'check' (vezinho)
                    const actionContent = 
                    n.status === 'nao_lida'
                    ? `<button class="cs-action-btn" onclick="marcarLidaAndRefreshDropdown(${n.id})" type="button"><i class="fas fa-check"></i> Lida</button>`
                    : `<span class="cs-read-status">✔ Lida</span>`;


                    item.innerHTML = `
                        ${deleteBtn}
                        <div class="cs-notification-content">
                            <div>
                                <div class="cs-notification-title">${n.titulo}</div>
                                <p class="cs-notification-message">${n.mensagem}</p>
                                <small class="cs-notification-date">${n.dataEnvio}</small>
                            </div>
                            <div class="cs-notification-action">
                                ${actionContent}
                            </div>
                        </div>
                    `;
                    lista.appendChild(item);
                });
            }

            // Atualizar o badge de contagem no header
            const countBadgeElement = document.getElementById('csNotificationCount');
            const unreadCount = dados.filter(n => n.status === 'nao_lida').length;
            const toggleBtn = document.getElementById('csNotificationToggle');
            
            if (unreadCount > 0) {
                if (!countBadgeElement) {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'cs-notification-badge';
                    newBadge.id = 'csNotificationCount';
                    toggleBtn.appendChild(newBadge);
                    newBadge.textContent = unreadCount;
                } else {
                    countBadgeElement.textContent = unreadCount;
                }
            } else {
                if (countBadgeElement) countBadgeElement.remove();
            }

        } catch (e) {
            console.error("Erro ao carregar notificações:", e);
            // Captura o erro 'Failed to fetch' e exibe uma mensagem
            let mensagemErro = e.message.includes('fetch') ? 'Verifique a URL base ou a conexão de rede.' : `Detalhes: ${e.message}`;
            lista.innerHTML = `<p class="cs-no-notifications" style="color:var(--bad);">Erro ao carregar. ${mensagemErro}</p>`;
        }
    }


    /** * Abre/Fecha o dropdown de notificações e carrega o conteúdo se estiver abrindo. */
    async function toggleNotificationDropdown(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('csNotificationDropdown');
        const toggle = document.getElementById('csNotificationToggle');
        const userDropdown = document.getElementById('csUserDropdown');
        const userToggle = document.getElementById('csUserToggle');

        // Fecha o outro dropdown se estiver aberto
        if (userDropdown && userDropdown.classList.contains('cs-show')) {
            userDropdown.classList.remove('cs-show');
            userToggle.setAttribute('aria-expanded', 'false');
        }

        const isOpen = dropdown.classList.toggle('cs-show');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        if (isOpen) {
            await updateNotificationDropdownContent();
        }
    }


    // ==============================================
    // INICIALIZAÇÃO E LISTENERS (IIFE para isolamento de variáveis)
    // ==============================================
    (() => {
        const userToggle = document.getElementById('csUserToggle');
        const userDropdown = document.getElementById('csUserDropdown');
        const notificationToggle = document.getElementById('csNotificationToggle');
        const notificationDropdown = document.getElementById('csNotificationDropdown'); 

        // --- Lógica do Dropdown do Usuário ---
        if (userToggle && userDropdown) {
            userToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                // Fecha o de notificação se estiver aberto
                if (notificationDropdown && notificationDropdown.classList.contains('cs-show')) {
                    notificationDropdown.classList.remove('cs-show');
                    notificationToggle.setAttribute('aria-expanded', 'false');
                }
                const isOpen = userDropdown.classList.toggle('cs-show');
                userToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        }

        // --- Lógica do Dropdown de Notificações ---
        if (notificationToggle && notificationDropdown) {
            notificationToggle.addEventListener('click', toggleNotificationDropdown);
            notificationDropdown.addEventListener('click', (e) => {
                // Impede que clicar dentro do dropdown feche ele (a menos que seja um link/botão)
                e.stopPropagation();
            });
        }

        // --- Fechar Dropdowns ao clicar fora ---
        document.addEventListener('click', () => {
            // Fecha o dropdown do usuário
            if (userDropdown && userDropdown.classList.contains('cs-show')) {
                userDropdown.classList.remove('cs-show');
                userToggle.setAttribute('aria-expanded', 'false');
            }
            // Fecha o dropdown de notificações
            if (notificationDropdown && notificationDropdown.classList.contains('cs-show')) {
                notificationDropdown.classList.remove('cs-show');
                notificationToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // --- Fechar Dropdowns com ESC ---
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (userDropdown && userDropdown.classList.contains('cs-show')) {
                    userDropdown.classList.remove('cs-show');
                    userToggle.setAttribute('aria-expanded', 'false');
                    userToggle.focus();
                }
                if (notificationDropdown && notificationDropdown.classList.contains('cs-show')) {
                    notificationDropdown.classList.remove('cs-show');
                    notificationToggle.setAttribute('aria-expanded', 'false');
                    notificationToggle.focus();
                }
            }
        });
    })();
</script>

<script>
    // Correção de espaçamento para o menu fixo
    (function() {
        const header = document.getElementById('csHeader');
        if (!header) return;

        function applyPadding() {
            const headerHeight = header.offsetHeight;
            document.body.style.paddingTop = `${headerHeight}px`;
        }

        applyPadding();
        window.addEventListener('resize', applyPadding);
    })();
</script>