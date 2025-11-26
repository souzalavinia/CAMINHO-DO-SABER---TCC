<?php

// ======================================
// CÓDIGO PARA MOSTRAR TODOS OS ERROS NA TELA (REMova em produção)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// ======================================

// CRÍTICO: Inicia o buffer de saída no topo do script para evitar erros de headers
ob_start(); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$idUsuario = (int)$_SESSION['id'];

// O caminho para o seu arquivo de conexão
require_once __DIR__ . '/conexao/conecta.php';

/* ============================
    1. VERIFICAÇÃO DO PLANO NO BANCO DE DADOS
============================ */
$sqlPlano = "SELECT plano FROM tb_usuario WHERE id = ?";
$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bind_param("i", $idUsuario);
$stmtPlano->execute();
$resultPlano = $stmtPlano->get_result();

$planoUsuario = 'Basico'; // Define um valor padrão seguro
$podeBaixarPDF = false; // Flag para controlar o botão de PDF
$planosLiberadosPDF = ['Individual', 'Essencial', 'Pro', 'Premium', 'escolaPublica']; // Planos que podem baixar PDF

if ($resultPlano && $resultPlano->num_rows > 0) {
    $rowPlano = $resultPlano->fetch_assoc();
    $planoUsuario = htmlspecialchars($rowPlano['plano']);
    
    // Verifica se o plano do usuário está na lista de planos permitidos para PDF
    if (in_array($planoUsuario, $planosLiberadosPDF)) {
        $podeBaixarPDF = true;
    }
}
$stmtPlano->close();


/* ============================
    2. LIMITES POR PLANO (PROVAS NÃO SIMULADAS)
============================ */
$limitesProvas = [
    'Basico' => 3,
    'Individual' => null,
    'Essencial' => null,
    'Pro' => null,
    'Premium' => null,
    'escolaPublica' => null
];

$limiteSemanalProvas = $limitesProvas[$planoUsuario] ?? null; 
$qtdProvasSemana = 0; // Inicializa

if (!is_null($limiteSemanalProvas)) {
    // contar tentativas na semana atual
    $sqlLimite = "
        SELECT COUNT(*) AS total
        FROM tb_tentativas
        WHERE idUsuario = ?
        AND YEARWEEK(STR_TO_DATE(dataTentativa, '%d/%m/%Y'), 1) = YEARWEEK(CURDATE(), 1)
    ";
    $stmt = $conn->prepare($sqlLimite);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $qtdProvasSemana = (int)$res['total'];

    if ($qtdProvasSemana >= $limiteSemanalProvas) {
        // ==============================================
        // BLOCO DE AVISO DE LIMITE EXCEDIDO SEMANAL (LAYOUT PADRÃO)
        // ==============================================
        // Encerra o buffer antes do HTML
        ob_end_clean();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Limite Excedido | Upgrade</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
            <style>
                :root{
                    --primary:#0d4b9e;
                    --primary-800:#0a3a7a;
                    --gold:#D4AF37; /* Cor Ouro */
                    --text:#212529;
                    --bg:#f6f8fb;
                    --card:#ffffff;
                    --muted:#6c757d;
                    --tr: all .2s ease;
                    --error-primary: #dc3545;
                    --error-dark: #c82333;
                    --error-bg: #f8d7da; /* Fundo mais suave para erro */
                    --error-light: #ff6b6b;
                }
                body {
                    margin: 0;
                    font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
                    background: var(--bg);
                    color: var(--text);
                    line-height: 1.5;
                }
                
                /* --- ESTILOS DO AVISO DE LIMITE EXCEDIDO (PREMIUM UPGRADE SCREEN) --- */
                .container-aviso {
                    max-width: 550px; 
                    margin: 80px auto;
                    background-color: var(--card);
                    border: none; 
                    border-radius: 16px;
                    padding: 40px;
                    text-align: center;
                    box-shadow: 0 10px 40px rgba(0,0,0,.15);
                    transition: var(--tr);
                    /* Destaque visual: Linha dourada no topo */
                    border-top: 5px solid var(--gold); 
                }
                .icon-aviso {
                    font-size: 3.5rem; 
                    color: var(--gold); 
                    margin-bottom: 20px;
                    display: block;
                    animation: pulse .8s infinite alternate; 
                }
                @keyframes pulse {
                    from { transform: scale(1); opacity: 0.9; }
                    to { transform: scale(1.05); opacity: 1; }
                }
                .titulo-aviso {
                    color: var(--primary-800);
                    font-size: 2rem; 
                    margin-bottom: 15px;
                    font-weight: 800;
                    line-height: 1.2;
                }
                .mensagem-aviso {
                    color: var(--muted); 
                    font-size: 1.05rem;
                    line-height: 1.6;
                    margin-bottom: 35px; 
                }
                .mensagem-aviso strong {
                    color: var(--text); 
                }
                .btn-upgrade {
                    display: inline-block;
                    padding: 18px 35px; 
                    background: linear-gradient(145deg, #FFD700, #DAA520); 
                    color: #121212; 
                    text-decoration: none;
                    font-weight: 700;
                    font-size: 1.1rem;
                    border-radius: 50px;
                    transition: var(--tr);
                    box-shadow: 0 8px 20px rgba(218, 165, 32, 0.4); 
                    border: 1px solid #FFD700;
                }
                .btn-upgrade:hover {
                    transform: translateY(-3px); 
                    box-shadow: 0 12px 25px rgba(218, 165, 32, 0.6);
                    filter: brightness(1.05);
                }
                .link-voltar-aviso {
                    display: block;
                    margin-top: 25px;
                    color: var(--muted);
                    text-decoration: none;
                    font-weight: 500;
                    font-size: 0.9rem; 
                    opacity: 0.8;
                    transition: var(--tr);
                }
                .link-voltar-aviso:hover {
                    color: var(--primary);
                    text-decoration: underline;
                    opacity: 1;
                }
                
            </style>
        </head>
        <body>

            <?php 
            // Assume-se que 'menu.php' existe no mesmo nível
            if (file_exists(__DIR__ . '/menu.php')) {
                include __DIR__ . '/menu.php';
            }
            ?>
            <div class='container-aviso'>
                <span class='icon-aviso'>
                    <i class="fas fa-crown"></i> 
                </span>
                <h2 class='titulo-aviso'>Prática Bloqueada!</h2>
                <p class='mensagem-aviso'>
                    Seu plano <?php echo ucfirst($planoUsuario); ?> permite <?php echo $limiteSemanalProvas; ?> provas por semana.
                    <br>
                    Você já utilizou o seu limite de <?php echo $qtdProvasSemana; ?> acessos nesta semana.
                    <br><br>
                    Para liberar o acesso irrestrito a todas as provas e todos os simulados agora mesmo, faça seu upgrade!
                </p>
                <a href='configuracao/configuracoes.php?tab=plans' class='btn-upgrade'>
                    <i class="fas fa-arrow-up"></i> Quero o Acesso Ilimitado
                </a>
                
                <a href='#' onclick="history.back(); return false;" class='link-voltar-aviso'>
                    <i class="fas fa-arrow-left"></i> Entendi. Voltar para a lista de provas
                </a>
            </div>
        </body>
        </html>
        <?php
        // FECHAMENTO CORRETO
        if ($conn->ping()) {
            $conn->close();
        }
        exit(); // Encerra a execução após exibir o aviso
    }
}
// O código HTML/PHP normal da mostraQuest.php continua a partir daqui.
// ==============================================

// Encerra o buffer antes de enviar o conteúdo normal (se não houve redirecionamento/saída)
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulado: <?php echo $nomeProva ?? 'Prova'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    :root{
        --primary-color:#0d4b9e;--primary-dark:#0a3a7a;--primary-light:#3a6cb5;
        --gold-color:#D4AF37;--gold-light:#E6C200;--gold-dark:#996515;
        --black:#212529;--dark-black:#121212;--white:#ffffff;
        --light-gray:#f5f7fa;--medium-gray:#e0e5ec;--dark-gray:#6c757d;
        --red-pdf: #dc3545; /* Cor para o botão de PDF */
    }
    .ocultar{display:none !important}
    body{font-family:'Montserrat',Arial,sans-serif;margin:0;padding:20px;background-color:var(--light-gray)}
    .container{max-width:800px;margin:auto;background:var(--white);padding:20px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.1)}
    
    /* Container para os botões de ação */
    .action-buttons {
        position: relative; /* Necessário para posicionar o tooltip */
        display: flex;
        justify-content: space-between; 
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap; 
        gap: 10px;
    }

    .btn-action {
        padding:10px 20px;
        text-decoration:none;
        font-size:1rem;
        font-weight:600;
        border-radius:50px;
        box-shadow:0 4px 12px rgba(0,0,0,.1);
        transition:all .3s ease;
        border:none;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:8px;
        white-space: nowrap; 
    }
    
    .btn-voltar{
        background:var(--primary-color);
        color:var(--white);
    }
    .btn-voltar:hover{background:var(--primary-dark);transform:translateY(-2px)}
    .btn-voltar:active{transform:translateY(1px)}

    /* ESTILO DO BOTÃO PDF */
    .btn-pdf {
        background: linear-gradient(135deg, #dc3545, #a71d2a);
        color: var(--white);
        font-weight: 700;
        padding: 12px 24px;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(220, 53, 69, 0.4);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-pdf i {
        transition: transform 0.3s ease;
        font-size: 1.2rem;
    }

    .btn-pdf:hover {
        background: linear-gradient(135deg, #e63946, #c82333);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.5);
        transform: translateY(-2px);
    }

    .btn-pdf:hover i {
        transform: scale(1.2) rotate(-5deg);
    }

    .btn-pdf:active {
        transform: translateY(1px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }

    /* Efeito de brilho animado passando pelo botão */
    .btn-pdf::after {
        content: "";
        position: absolute;
        top: 0;
        left: -75%;
        width: 50%;
        height: 100%;
        background: rgba(255, 255, 255, 0.3);
        transform: skewX(-25deg);
        transition: left 0.6s ease;
    }

    .btn-pdf:hover::after {
        left: 125%;
    }

    /* Botão PDF desativado */
    .btn-pdf-disabled {
        background: linear-gradient(135deg, #888, #666);
        color: #ddd;
        cursor: not-allowed;
        position: relative;
        border-radius: 12px;
        padding: 12px 24px;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .btn-pdf-disabled i {
        font-size: 1.2rem;
    }

    /* Tooltip ajustado visualmente */
    .tooltip {
        visibility: hidden;
        background: rgba(0, 0, 0, 0.9);
        color: #fff;
        text-align: center;
        border-radius: 8px;
        padding: 8px 12px;
        position: absolute;
        z-index: 1;
        top: 125%; 
        left: 50%;
        transform: translateX(-50%);
        opacity: 0;
        transition: opacity 0.3s, transform 0.3s;
        width: 280px;
        font-size: 0.9rem;
        line-height: 1.4;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
    }

    .tooltip::after {
        content: "";
        position: absolute;
        bottom: 100%; 
        left: 50%;
        transform: translateX(-50%);
        border-width: 6px;
        border-style: solid;
        border-color: transparent transparent rgba(0, 0, 0, 0.9) transparent;
    }

    .btn-pdf-disabled:hover .tooltip {
        visibility: visible;
        opacity: 1;
        transform: translateX(-50%) translateY(5px);
    }

    h1{text-align:center;color:var(--primary-dark);margin-bottom:30px}
    .questao{margin-bottom:30px;border-bottom:1px solid var(--medium-gray);padding-bottom:20px}
    .questao h2{color:var(--primary-color);font-size:1.4rem}
    img{display:block;margin:10px auto;border-radius:5px;max-width:100%;height:auto}
    label{display:block;margin:10px 0;background:var(--light-gray);padding:12px;border-radius:5px;cursor:pointer;transition:all .2s ease}
    label:hover{background:var(--medium-gray)}
    input[type="radio"]{margin-right:10px;accent-color:var(--primary-color)}
    
    /* Estilos do botão principal */
    button#btnEnviar{display:block;width:100%;padding:15px;background-color:var(--gold-color);color:var(--black);border:none;border-radius:5px;cursor:pointer;font-size:16px;margin-top:20px;font-weight:600;transition:all .3s ease; display:flex; justify-content:center; align-items:center; gap:10px;}
    button#btnEnviar:hover{background-color:var(--gold-light)}

    /* --- Estilos do Modal de Resultado (Pop-up) --- */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000; 
        transition: opacity 0.3s ease;
    }

    .modal-content {
        background-color: var(--white);
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        max-width: 90%;
        width: 450px;
        text-align: center;
        transform: scale(0.9);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .modal-content.show {
        transform: scale(1);
        opacity: 1;
    }

    .modal-icon {
        font-size: 4rem;
        color: #28a745; /* Verde de sucesso */
        margin-bottom: 20px;
        animation: bounceIn 0.5s ease-out;
    }

    .modal-content h2 {
        color: var(--primary-dark);
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: 10px;
    }

    .modal-content p {
        color: var(--dark-gray);
        margin-bottom: 30px;
        font-size: 1rem;
    }

    .btn-resultado {
        display: inline-block;
        padding: 15px 30px;
        background: linear-gradient(45deg, #28a745, #1e7e34); 
        color: var(--white);
        text-decoration: none;
        font-weight: 700;
        font-size: 1.1rem;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4);
    }

    .btn-resultado:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(40, 167, 69, 0.6);
    }
    
    @keyframes bounceIn {
        0% { transform: scale(0.3); opacity: 0; }
        50% { transform: scale(1.1); opacity: 1; }
        70% { transform: scale(0.9); }
        100% { transform: scale(1); }
    }
    /* --- FIM DOS ESTILOS DO MODAL --- */
    
    /* MELHORIAS NO RESPONSIVO */
    @media (max-width: 600px) {
        .action-buttons {
            justify-content: space-around;
            gap: 5px; 
        }
        .btn-action {
            flex-grow: 1;
            min-width: 45%; 
            padding: 12px 10px; 
            font-size: 0.9rem;
            border-radius: 10px; 
        }
        
        /* Ajustes no Tooltip para mobile, alinhando-o acima e centralizado */
        .tooltip {
            top: unset; 
            bottom: 110%; 
            left: 50%;
            transform: translateX(-50%);
            width: 90vw; 
            padding: 10px;
        }
        .tooltip::after {
            top: 100%; 
            bottom: unset;
            border-color: rgba(0, 0, 0, 0.9) transparent transparent transparent; 
        }
        .btn-pdf-disabled:hover .tooltip {
            transform: translateX(-50%);
        }
    }
    </style>
</head>
<body>

<div class="container">

    <?php
    $idProva = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $nomeProva = "Prova"; 
    $simuladoStatus = 'não'; 

    // ===============================================
    // LÓGICA DE SEGURANÇA: CHECAGENS DE ACESSO (Simulados Ocultos & Limite Único)
    // ===============================================
    if ($idProva > 0) {
        // 1. Busca o status e nome da prova na tabela tb_prova
        $sqlStatus = "SELECT simulado, nome FROM tb_prova WHERE id = ?";
        $stmtStatus = $conn->prepare($sqlStatus);
        
        if ($stmtStatus) {
            $stmtStatus->bind_param("i", $idProva);
            $stmtStatus->execute();
            $resultStatus = $stmtStatus->get_result();
            
            if ($rowStatus = $resultStatus->fetch_assoc()) {
                $nomeProva = htmlspecialchars($rowStatus['nome']);
                $simuladoStatus = $rowStatus['simulado'];

                // 2. Se a prova é um SIMULADO ('sim')
                if ($simuladoStatus === 'sim') {
                    
                    // --- 2a. VERIFICAÇÃO DE LIMITE DE 1 TENTATIVA PARA SIMULADOS ---
                    $sqlTentativas = "
                        SELECT COUNT(id) AS total_tentativas 
                        FROM tb_tentativas 
                        WHERE idUsuario = ? AND idProva = ?
                    ";
                    $stmtTentativas = $conn->prepare($sqlTentativas);
                    $stmtTentativas->bind_param("ii", $idUsuario, $idProva);
                    $stmtTentativas->execute();
                    $resTentativas = $stmtTentativas->get_result()->fetch_assoc();
                    $stmtTentativas->close();

                    $tentativasRealizadas = (int)$resTentativas['total_tentativas'];

                    if ($tentativasRealizadas >= 1) {
                        
                        // ==============================================
                        // BLOCO DE AVISO DE LIMITE ÚNICO EXCEDIDO (Simulado)
                        // ==============================================
                        // Encerra o buffer antes do HTML
                        ob_end_clean();
                        ?>
                        <!DOCTYPE html>
                        <html lang="pt-BR">
                       <head>                                 <meta charset="UTF-8">                                 <meta name="viewport" content="width=device-width, initial-scale=1.0">                                 <title>Acesso Negado | Simulado</title>                                 <script src="https://cdn.tailwindcss.com"></script>                                 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">                               </head>
                        <body>
                            <?php 
                            if (file_exists(__DIR__ . '/menu.php')) {
                                include __DIR__ . '/menu.php';
                            }
                            ?>
                            <div class="max-w-md mx-auto p-8 sm:p-10 bg-white shadow-xl rounded-xl text-center border-t-8 border-red-600 animate-in fade-in zoom-in duration-500">
    
    <span class="inline-flex items-center justify-center p-6 mb-6 bg-red-600 text-white rounded-full shadow-lg shadow-red-500/50 transform transition duration-300 hover:scale-105">
        <i class="fas fa-lock text-4xl"></i>
    </span>
    
    <h2 class="text-3xl font-extrabold text-red-700 mb-4 tracking-tight">Tentativa Esgotada</h2>
    
    <p class="text-lg text-gray-700 leading-relaxed mb-8">
        A prova <span class="font-bold text-red-700"><?php echo $nomeProva; ?></span> é um Simulado Oficial e permite apenas 1 (uma) única tentativa por usuário.
        <br><br>
        Você já realizou este Simulado. Seu resultado deve aparecer em breve no seu historico de provas.
    </p>
    
    <a href='progresso.php' 
        class="inline-flex items-center justify-center px-8 py-3 text-lg font-bold text-white bg-red-600 border-b-4 border-red-800 rounded-lg shadow-md transition duration-300 hover:bg-red-700 hover:border-red-900 active:translate-y-1 active:border-b-0 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 gap-3">
        <i class="fas fa-arrow-left"></i> 
        Ver Histórico de Provas
    </a>
    
</div>
                        </body>
                        </html>
                        <?php
                        // FECHAMENTO CORRIGIDO
                        if ($conn->ping()) {
                            $conn->close();
                        }
                        exit(); 
                    }

                    // --- 2b. CHECAGEM DE ACESSO OCULTO (Primeira tentativa) ---
                    $acessoPermitido = 
                        isset($_SESSION['acesso_simulado_id']) && 
                        (int)$_SESSION['acesso_simulado_id'] === $idProva;
                    
                    if (!$acessoPermitido) {
                        unset($_SESSION['acesso_simulado_id']); 
                        header("Location: acessarProvaSerial.php?error=acesso-negado");
                        exit();
                    }
                    
                    // Remove o passe APÓS a primeira utilização.
                    unset($_SESSION['acesso_simulado_id']); 
                } 
            }
            $stmtStatus->close();
        }
    }
    // Prepara o ID da prova e do usuário para uso no JavaScript
    $jsIdProva = $idProva;
    $jsIdUsuario = $idUsuario;
    // ===============================================

    /* ===========================
        3. CARREGAR RASCUNHO DO BANCO DE DADOS
    ============================ */
    $rascunhos = [];
    $temRascunho = false;

    if ($idProva > 0) {
        $sqlRascunho = "SELECT id_questao, resposta_marcada FROM tb_rascunho WHERE id_usuario = ? AND id_prova = ?";
        // É importante que a conexão ainda esteja aberta aqui.
        if (!$conn->ping()) {
            // Se a conexão foi fechada (por algum bloco de exit/aviso), é preciso reabrir
            require_once __DIR__ . '/conexao/conecta.php'; 
        }
        
        $stmtRascunho = $conn->prepare($sqlRascunho);
        $stmtRascunho->bind_param("ii", $idUsuario, $idProva);
        $stmtRascunho->execute();
        $resultRascunho = $stmtRascunho->get_result();

        while ($rowRascunho = $resultRascunho->fetch_assoc()) {
            // Armazena no formato [id_questao] => resposta
            $rascunhos[$rowRascunho['id_questao']] = $rowRascunho['resposta_marcada'];
        }
        $stmtRascunho->close();

        if (!empty($rascunhos)) {
            $temRascunho = true;
        }
    }
    // ===============================================
    ?>
    
    <div class="action-buttons">
        <a href="exibirProvas.php" class="btn-action btn-voltar"><i class="fas fa-arrow-left"></i> Voltar</a>
        
        <?php if ($idProva > 0): ?>
            
            <?php if ($podeBaixarPDF): ?>
                <a href="gerar_pdf_prova.php?id=<?php echo urlencode($idProva); ?>" class="btn-action btn-pdf" target="_blank">
                    <i class="fas fa-file-pdf"></i> Baixar PDF
                </a>
            <?php else: ?>
                <span class="btn-action btn-pdf-disabled">
                    <i class="fas fa-file-pdf"></i> Baixar PDF
                    <span class="tooltip">
                        Opção Premium
                        <br><br>
                        <a href='configuracao/configuracoes.php?tab=plans' style='color:#ffe56d;font-weight:600;text-decoration:underline;'>Faça upgrade!</a>
                    </span>
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php
    echo "<h1>" . $nomeProva . "</h1>";
    ?>

    <form id="formRespostas" method="POST" action="<?php echo 'tentativas.php?prova=' . urlencode($idProva); ?>">
        <input type="hidden" name="prova" value="<?php echo htmlspecialchars($idProva); ?>">
    <?php
    // Questões da prova
    $sql = "SELECT id, quest, alt_a, alt_b, alt_c, alt_d, alt_e, alt_corre, foto, tipo, numQuestao
            FROM tb_quest
            WHERE prova = ?
            ORDER BY numQuestao";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idProva);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $idQuestao = (int)$row['id']; 
            $numQ      = htmlspecialchars($row['numQuestao']);
            $gabarito  = strtoupper(trim((string)($row['alt_corre'] ?? ''))); 

            echo "<div class='questao' data-id='{$idQuestao}' data-correta='" . htmlspecialchars($gabarito) . "'>";
            echo "<h2>Questão {$numQ}</h2>";

            // Imagem, se houver
            if (!empty($row['foto'])) {
                $tipoImg = htmlspecialchars($row['tipo'] ?: 'image/png');
                $base64  = base64_encode($row['foto']);
                echo "<img src='data:{$tipoImg};base64,{$base64}' alt='Imagem da questão' />";
            }

            // Enunciado
            echo "<p>" . nl2br(htmlspecialchars($row['quest'])) . "</p>";

            // Alternativas
            $alts = [
                'A' => $row['alt_a'],
                'B' => $row['alt_b'],
                'C' => $row['alt_c'],
                'D' => $row['alt_d'],
                'E' => $row['alt_e'],
            ];
            
            // Pega a resposta rascunhada do banco, se houver
            $respostaRascunho = $rascunhos[$idQuestao] ?? ''; 

            foreach (['A','B','C','D','E'] as $letra) {
                $texto = $alts[$letra];
                if ($texto === null || $texto === '') continue;

                $inputName = "respostas[{$idQuestao}]";    
                // O ID do input é crucial para o JavaScript carregar o progresso
                $inputId  = "q{$idQuestao}_{$letra}"; 
                
                // NOVO: Adiciona 'checked' se a letra for a resposta do rascunho
                $checkedAttr = ($respostaRascunho === $letra ? 'checked' : '');

                echo "<label for='{$inputId}'>";
                echo "<input type='radio' id='{$inputId}' name='{$inputName}' value='{$letra}' {$checkedAttr} required>";
                // LOGICA ADICIONADA: Mostra a letra da alternativa antes do texto.
                echo $letra . ") " . htmlspecialchars($texto);
                echo "</label>";
            }

            echo "</div>";
        }
    } else {
        echo "<p>Nenhuma questão encontrada.</p>";
    }

    $stmt->close();
    
    // A conexão é fechada no final, após todas as operações do mostraQuest.php.
    if ($conn->ping()) {
       $conn->close();
    }
    ?>
        <button id="btnEnviar" type="submit">Finalizar Simulado</button>
    </form>
</div>

<div id="resultModal" class="modal-overlay ocultar">
    <div class="modal-content" id="modalContent">
        <div class="modal-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Simulado Finalizado!</h2>
        <p>Suas respostas foram salvas com sucesso no nosso sistema.</p>
        
        <a href="#" id="resultLink" class="btn-resultado">
            <i class="fas fa-chart-bar"></i> Resultados
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formRespostas');
    const btnEnviar = document.getElementById('btnEnviar');
    const resultModal = document.getElementById('resultModal');
    const modalContent = document.getElementById('modalContent');

    // Variáveis PHP injetadas no JavaScript
    const rascunhosIniciais = JSON.parse('<?php echo json_encode($rascunhos); ?>');
    const idProvaAtual = <?php echo json_encode($jsIdProva); ?>;
    const temRascunho = <?php echo $temRascunho ? 'true' : 'false'; ?>;

    // ======================================
    // FUNÇÕES DE EXIBIÇÃO DO MODAL
    // ======================================
    function showResultModal(idTentativa) {
        const link = document.getElementById('resultLink');
        
        // Define o link correto para a página de resultado usando o ID da Tentativa
        link.href = `progresso.php`;
        
        // Exibe o modal
        resultModal.classList.remove('ocultar');
        // Adiciona classe para a animação CSS
        setTimeout(() => {
            modalContent.classList.add('show'); 
        }, 10);
        
        document.body.style.overflow = 'hidden'; 
    }

    // ======================================
    // 1. FUNÇÃO PARA SALVAR O PROGRESSO VIA AJAX (RASCUNHO)
    // ======================================
    function salvarProgresso(idQuestao, resposta) {
        if (!idProvaAtual || idProvaAtual <= 0) {
            console.error("ID da prova inválido para salvar rascunho.");
            return;
        }
        
        const formData = new FormData();
        formData.append('idProva', idProvaAtual);
        formData.append('idQuestao', idQuestao);
        formData.append('resposta', resposta);

        fetch('salvaRascunho.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => { 
                    throw new Error(data.message || `Erro na requisição (${response.status}): ${response.statusText}`); 
                });
            }
            return response.json();
        })
        .catch(error => {
            console.error("Falha ao salvar rascunho. Tente novamente:", error);
        });
    }

    // A função aplicarRascunhoViaJs foi removida, pois o PHP já aplica o 'checked'

    // ======================================
    // 2. LISTENERS
    // ======================================
    
    // A. Listener para salvar RASCUNHO ao marcar
    form.addEventListener('change', function(event) {
        if (event.target.type === 'radio' && event.target.name.startsWith('respostas[')) {
            const idQuestaoMatch = event.target.name.match(/\[(\d+)\]/);
            
            if (idQuestaoMatch) {
                const idQuestao = idQuestaoMatch[1];
                const resposta = event.target.value;
                salvarProgresso(idQuestao, resposta);
            }
        }
    });

    // B. Listener para SUBMISSÃO DO FORMULÁRIO (AJAX)
    form.addEventListener('submit', function(event) {
        event.preventDefault(); // IMPEDE o envio padrão do formulário
        
        const originalText = btnEnviar.textContent;
        const spinnerHtml = '<i class="fas fa-spinner fa-spin ml-2"></i>';

        // 1. Bloqueia o botão e mostra loading
        btnEnviar.disabled = true;
        btnEnviar.innerHTML = originalText + spinnerHtml;

        const formData = new FormData(form);

        // 2. Envio AJAX para tentativas.php
        fetch(form.action, { 
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || "Erro desconhecido ao processar a prova.");
                });
            }
            return response.json();
        })
        .then(data => {
            // 3. SUCCESSO
            if (data.status === 'success') {
                showResultModal(data.idTentativa); // Exibe o pop-up
            } else {
                throw new Error(data.message || "A submissão falhou (retorno inesperado do servidor).");
            }
        })
        .catch(error => {
            // 4. ERRO
            alert("❌ Erro ao enviar a prova:\n\n" + error.message);
            console.error("Erro na submissão AJAX:", error);
        })
        .finally(() => {
            // 5. Reativa o botão
            btnEnviar.disabled = false;
            btnEnviar.textContent = originalText;
        });
    });

    // C. Aviso opcional ao tentar sair
    window.addEventListener('beforeunload', function(e) {
        // Checa se o modal de resultado AINDA não estiver visível.
        if (resultModal.classList.contains('ocultar') && (temRascunho || form.querySelector('input[type="radio"]:checked'))) {
            e.preventDefault(); 
            e.returnValue = 'Você tem respostas salvas como rascunho. Deseja realmente sair desta página?';
        }
    });
});
</script>

</body>

</html>