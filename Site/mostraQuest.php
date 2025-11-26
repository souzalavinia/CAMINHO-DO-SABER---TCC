<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$idUsuario = (int)$_SESSION['id'];
require_once __DIR__ . '/conexao/conecta.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ============================
    1. VERIFICAÇÃO DO PLANO NO BANCO DE DADOS
============================ */
$sqlPlano = "SELECT plano FROM tb_usuario WHERE id = ?";
$stmtPlano = $conn->prepare($sqlPlano);
$stmtPlano->bind_param("i", $idUsuario);
$stmtPlano->execute();
$resultPlano = $stmtPlano->get_result();

$planoUsuario = 'Basico'; 
$podeBaixarPDF = false; 
$planosLiberadosPDF = ['Individual', 'Essencial', 'Pro', 'Premium', 'escolaPublica']; 

if ($resultPlano && $resultPlano->num_rows > 0) {
    $rowPlano = $resultPlano->fetch_assoc();
    $planoUsuario = htmlspecialchars($rowPlano['plano']);
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
$qtdProvasSemana = 0; 

if (!is_null($limiteSemanalProvas)) {
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
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Limite Excedido | Upgrade</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        </head>
        <body>
            <?php 
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
        $conn->close();
        exit();
    }
}
// O código HTML/PHP normal da mostraQuest.php continua a partir daqui.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROVAS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
/* --- 1. VARIÁVEIS GLOBAIS --- */
:root{
    --primary-color:#0d4b9e;
    --primary-dark:#0a3a7a;
    --primary-light:#3a6cb5;
    --gold-color:#D4AF37; 
    --gold-light:#E6C200;
    --gold-dark:#996515;
    --black:#212529;
    --dark-black:#121212;
    --white:#ffffff;
    --light-gray:#f5f7fa;
    --medium-gray:#e0e5ec;
    --dark-gray:#6c757d;
    --red-pdf: #dc3545; /* Cor para o botão de PDF */
    --success-color: #28a745; /* Cor para sucesso (Modal) */
    --warning-color: #ffc107; /* Cor para aviso (Modal) */
    
    /* Variáveis do bloco de Aviso de Limite (Tailwind screen) */
    --primary:#0d4b9e;
    --primary-800:#0a3a7a;
    --gold:#D4AF37; 
    --text:#212529;
    --bg:#f6f8fb;
    --card:#ffffff;
    --muted:#6c757d;
    --tr: all .2s ease;
}

/* Estilos de Reset e Layout Principal */
.ocultar{display:none}
body{font-family:'Montserrat',Arial,sans-serif;margin:0;padding:20px;background-color:var(--light-gray)}
.container{max-width:800px;margin:auto;background:var(--white);padding:20px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.1)}
h1{text-align:center;color:var(--primary-dark);margin-bottom:30px}

/* --- 2. ESTILO DA PROVA E QUESTÕES --- */
.questao{margin-bottom:30px;border-bottom:1px solid var(--medium-gray);padding-bottom:20px}
.questao h2{color:var(--primary-color);font-size:1.4rem}
img{display:block;margin:10px auto;border-radius:5px;max-width:100%;height:auto}
label{display:block;margin:10px 0;background:var(--light-gray);padding:12px;border-radius:5px;cursor:pointer;transition:all .2s ease}
label:hover{background:var(--medium-gray)}
input[type="radio"]{margin-right:10px;accent-color:var(--primary-color)}

/* --- 3. BOTÕES DE AÇÃO SUPERIORES (Voltar, PDF) --- */
.action-buttons {
    position: relative; 
    display: flex;
    justify-content: space-between; 
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap; 
    gap: 10px;
}

.btn-action {
    padding:10px 20px; text-decoration:none; font-size:1rem; font-weight:600; 
    border-radius:50px; box-shadow:0 4px 12px rgba(0,0,0,.1); transition:all .3s ease; 
    border:none; cursor:pointer; display:inline-flex; align-items:center; 
    gap:8px; white-space: nowrap; 
}

.btn-voltar{
    background:var(--primary-color);
    color:var(--white);
}
.btn-voltar:hover{background:var(--primary-dark);transform:translateY(-2px)}
.btn-voltar:active{transform:translateY(1px)}

/* ESTILO DO BOTÃO PDF */
.btn-pdf {
    background: linear-gradient(135deg, var(--red-pdf), #a71d2a);
    color: var(--white);
    font-weight: 700;
    padding: 12px 24px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(220, 53, 69, 0.4);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.btn-pdf i { transition: transform 0.3s ease; font-size: 1.2rem; }
.btn-pdf:hover {
    background: linear-gradient(135deg, #e63946, #c82333);
    box-shadow: 0 8px 20px rgba(220, 53, 69, 0.5);
    transform: translateY(-2px);
}
.btn-pdf:hover i { transform: scale(1.2) rotate(-5deg); }
.btn-pdf:active { transform: translateY(1px); box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4); }
.btn-pdf::after { 
    content: ""; position: absolute; top: 0; left: -75%; width: 50%; height: 100%; 
    background: rgba(255, 255, 255, 0.3); transform: skewX(-25deg); transition: left 0.6s ease;
}
.btn-pdf:hover::after { left: 125%; }

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
.btn-pdf-disabled i { font-size: 1.2rem; }

/* Tooltip */
.tooltip {
    visibility: hidden; background: rgba(0, 0, 0, 0.9); color: #fff; text-align: center; 
    border-radius: 8px; padding: 8px 12px; position: absolute; z-index: 1; 
    top: 125%; left: 50%; transform: translateX(-50%); opacity: 0; 
    transition: opacity 0.3s, transform 0.3s; width: 280px; font-size: 0.9rem; 
    line-height: 1.4; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
}
.tooltip::after { 
    content: ""; position: absolute; bottom: 100%; left: 50%; 
    transform: translateX(-50%); border-width: 6px; border-style: solid; 
    border-color: transparent transparent rgba(0, 0, 0, 0.9) transparent;
}
.btn-pdf-disabled:hover .tooltip {
    visibility: visible; opacity: 1; transform: translateX(-50%) translateY(5px);
}

/* --- 4. BOTÃO ENVIAR (NOVO ESTILO) --- */
#btnEnviar{
    display:block;width:100%;padding:15px;background-color:var(--primary-color);
    color:var(--white);border:none;border-radius:50px;cursor:pointer;font-size:1.1rem;
    margin-top:20px;font-weight:700;transition:all .3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,.1);
}
#btnEnviar:hover{background-color:var(--primary-dark)}


/* --- 5. ESTILOS DOS MODAIS (Confirmação, Loading, Sucesso) --- */
.modal {
    display: none; 
    position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; 
    overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(3px); 
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.modal-content {
    background-color: var(--white); margin: 15% auto; padding: 30px; width: 90%; 
    max-width: 400px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); 
    text-align: center; 
    animation: zoomIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.modal-content h3 { margin-top: 0; color: var(--primary-dark); font-size: 1.5rem; }
.modal-content p { margin-bottom: 25px; color: var(--dark-gray); line-height: 1.6; }

.modal-btn {
    padding: 10px 20px; margin: 0 5px; border: none; border-radius: 5px; 
    cursor: pointer; font-weight: 600; transition: background-color 0.2s;
}

/* Confirmação */
#modalConfirmacao i { font-size: 2.5rem; color: var(--warning-color); margin-bottom: 10px; }
#btnConfirmarEnvio { background-color: var(--red-pdf); color: var(--white); }
#btnConfirmarEnvio:hover { background-color: #a71d2a; }
#btnCancelarEnvio { background-color: var(--medium-gray); color: var(--black); }
#btnCancelarEnvio:hover { background-color: var(--dark-gray); color: var(--white); }

/* Sucesso */
#modalSucesso .modal-content { border-top: 5px solid var(--success-color); }
#modalSucesso h3 { color: var(--success-color); }
#modalSucesso .fas { 
    font-size: 3rem; color: var(--success-color); margin-bottom: 15px; 
    animation: bounceIn 0.5s; 
}
@keyframes bounceIn { 0% { transform: scale(0.3); opacity: 0; } 50% { transform: scale(1.2); opacity: 1; } 100% { transform: scale(1); } }

/* Loading */
#modalLoader .modal-content { 
    border-top: 5px solid var(--primary-color);
    max-width: 300px; 
    padding: 40px;
}
#modalLoader h3 { color: var(--primary-color); }
#modalLoader .loader-spinner {
    border: 4px solid var(--medium-gray); 
    border-top: 4px solid var(--primary-color);
    border-radius: 50%; width: 30px; height: 30px; 
    animation: spin 1s linear infinite; margin: 0 auto 10px;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* --- 6. ESTILOS DO AVISO DE LIMITE EXCEDIDO --- */
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

/* --- 7. RESPONSIVO --- */
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
    
    /* Ajustes no Tooltip para mobile */
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
    $nomeProva = "Prova";  // Valor padrão
    $jsIdProva = $idProva;
    $jsIdUsuario = $idUsuario;

    /* ===============================================
    // LÓGICA ESSENCIAL: BUSCA O NOME DA PROVA NO BANCO DE DADOS
    // =============================================== */
    if ($idProva > 0) {
        // Consulta simplificada, buscando apenas o nome
        $sqlNome = "SELECT nome FROM tb_prova WHERE id = ?";
        $stmtNome = $conn->prepare($sqlNome);
        
        if ($stmtNome) {
            $stmtNome->bind_param("i", $idProva);
            $stmtNome->execute();
            $resultNome = $stmtNome->get_result();
            
            if ($rowNome = $resultNome->fetch_assoc()) {
                $nomeProva = htmlspecialchars($rowNome['nome']);
            }
            $stmtNome->close();
        }
    }




    /* ===========================
        3. CARREGAR RASCUNHO DO BANCO DE DADOS
    ============================ */
    $rascunhos = [];
    $temRascunho = false;

    if ($idProva > 0) {
        $sqlRascunho = "SELECT id_questao, resposta_marcada FROM tb_rascunho WHERE id_usuario = ? AND id_prova = ?";
        if (!$conn->ping()) {
            require_once __DIR__ . '/conexao/conecta.php'; 
        }
        
        $stmtRascunho = $conn->prepare($sqlRascunho);
        $stmtRascunho->bind_param("ii", $idUsuario, $idProva);
        $stmtRascunho->execute();
        $resultRascunho = $stmtRascunho->get_result();

        while ($rowRascunho = $resultRascunho->fetch_assoc()) {
            $rascunhos[$rowRascunho['id_questao']] = $rowRascunho['resposta_marcada'];
        }
        $stmtRascunho->close();

        if (!empty($rascunhos)) {
            $temRascunho = true;
        }
    }
    // O array $rascunhos agora tem o progresso para ser injetado no JS.
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
    // Busca das questões (Mantido seu código original)
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

            if (!empty($row['foto'])) {
                $tipoImg = htmlspecialchars($row['tipo'] ?: 'image/png');
                $base64  = base64_encode($row['foto']);
                echo "<img src='data:{$tipoImg};base64,{$base64}' alt='Imagem da questão' />";
            }

            echo "<p>" . nl2br(htmlspecialchars($row['quest'])) . "</p>";

            $alts = [
                'A' => $row['alt_a'], 'B' => $row['alt_b'], 'C' => $row['alt_c'], 
                'D' => $row['alt_d'], 'E' => $row['alt_e'],
            ];
            
            $respostaRascunho = $rascunhos[$idQuestao] ?? ''; 

            foreach (['A','B','C','D','E'] as $letra) {
                $texto = $alts[$letra];
                if ($texto === null || $texto === '') continue;

                $inputName = "respostas[{$idQuestao}]";    
                $inputId  = "q{$idQuestao}_{$letra}"; 
                
                $checkedAttr = ($respostaRascunho === $letra ? 'checked' : '');

                echo "<label for='{$inputId}'>";
                echo "<input type='radio' id='{$inputId}' name='{$inputName}' value='{$letra}' {$checkedAttr} required>";
                echo htmlspecialchars($letra . ') ' . $texto); // Adicionado a letra na exibição
                echo "</label>";
            }

            echo "</div>";
        }
    } else {
        echo "<p>Nenhuma questão encontrada.</p>";
    }

    $stmt->close();
    // A conexão só deve ser fechada se não for mais usada no script 
    if ($conn->ping()) {
           $conn->close();
    }
    ?>
        <button id="btnEnviar" type="submit">Enviar Respostas</button>
    </form>
</div>

<div id="modalConfirmacao" class="modal">
    <div class="modal-content">
        <i class="fas fa-question-circle" style="font-size: 2.5rem; color: #ffc107; margin-bottom: 10px;"></i>
        <h3>Confirmar Envio da Prova?</h3>
        <p>Você tem certeza que deseja finalizar e enviar suas respostas? Após o envio, não será possível alterá-las.</p>
        <button id="btnConfirmarEnvio" class="modal-btn">Sim, Enviar Agora!</button>
        <button id="btnCancelarEnvio" class="modal-btn">Cancelar</button>
    </div>
</div>

<div id="modalLoader" class="modal">
    <div class="modal-content" style="max-width: 300px; padding: 40px;">
        <div class="loader-spinner"></div>
        <h3>Processando...</h3>
        <p style="margin-bottom: 0;">Aguarde enquanto registramos sua prova.</p>
    </div>
</div>

<div id="modalSucesso" class="modal">
    <div class="modal-content">
        <i class="fas fa-check-circle"></i>
        <h3>Prova Finalizada!</h3>
        <p>Sua avaliação foi enviada com sucesso!</p>
        <button id="btnFecharSucesso" class="modal-btn" style="background-color: var(--success-color); color: white;">Ver Resultados</button>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formRespostas');
    const modalConfirmacao = document.getElementById('modalConfirmacao');
    const btnConfirmarEnvio = document.getElementById('btnConfirmarEnvio');
    const btnCancelarEnvio = document.getElementById('btnCancelarEnvio');
    const modalLoader = document.getElementById('modalLoader'); 
    const modalSucesso = document.getElementById('modalSucesso');
    const btnFecharSucesso = document.getElementById('btnFecharSucesso');

    // Variáveis PHP injetadas
    const idProvaAtual = <?php echo json_encode($jsIdProva); ?>;
    const temRascunho = <?php echo $temRascunho ? 'true' : 'false'; ?>;
    let idTentativaSalva = null; 

    // ======================================
    // 1. FUNÇÃO PARA SALVAR O PROGRESSO VIA AJAX
    // ======================================
    function salvarProgresso(idQuestao, resposta) {
        if (!idProvaAtual || idProvaAtual <= 0) return;
        
        const formData = new FormData();
        formData.append('idProva', idProvaAtual);
        formData.append('idQuestao', idQuestao);
        formData.append('resposta', resposta);

        fetch('salvaRascunho.php', { method: 'POST', body: formData })
        .catch(error => { console.error("Falha na comunicação com salvaRascunho.php: ", error); });
    }

    // ======================================
    // 2. LISTENERS
    // ======================================

    // A. Salvar rascunho ao mudar a resposta
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

    // B. Interceptar Envio e Exibir Modal de Confirmação
    form.addEventListener('submit', function(e) {
        e.preventDefault(); 
        modalConfirmacao.style.display = 'block'; 
    });

    // C. Ação do botão CONFIRMAR: Envio via AJAX
    btnConfirmarEnvio.addEventListener('click', function() {
        modalConfirmacao.style.display = 'none'; 
        modalLoader.style.display = 'block'; 
        
        const url = form.action;
        const formData = new FormData(form);

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                // Tenta ler o JSON de erro do servidor
                return response.json().then(data => {
                    throw new Error(data.message || `Erro no servidor: Status ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            modalLoader.style.display = 'none'; 
            
            if (data.status === 'success') {
                idTentativaSalva = data.idTentativa; 
                
                // Exibe o Modal de Sucesso
                modalSucesso.style.display = 'block'; 
            } else {
                 // Erro lógico retornado pelo tentativas.php
                alert(`Erro ao finalizar a prova: ${data.message}`);
            }
        })
        .catch(error => {
            modalLoader.style.display = 'none'; // Oculta o Loader em caso de falha
            alert(`Ocorreu um erro ao enviar a prova. Detalhes: ${error.message}`);
        });
    });

    // D. Ação do botão CANCELAR (Confirmação)
    btnCancelarEnvio.addEventListener('click', function() {
        modalConfirmacao.style.display = 'none';
    });

    // E. Fechar modal de sucesso e redirecionar
    btnFecharSucesso.addEventListener('click', function() {
        // Redireciona para progresso.php, usando o ID da tentativa salva (se disponível)
        const redirectUrl = idTentativaSalva ? `progresso.php?tentativa=${idTentativaSalva}` : 'progresso.php';
        window.location.href = redirectUrl;
    });

    // F. Aviso opcional ao tentar sair 
    window.addEventListener('beforeunload', function(e) {
        // Verifica se o modal de sucesso está visível (se sim, a prova já foi enviada)
        if (modalSucesso.style.display !== 'block' && (temRascunho || form.querySelector('input[type="radio"]:checked'))) {
            e.preventDefault(); 
            e.returnValue = 'Você tem respostas salvas como rascunho. Deseja realmente sair desta página?';
        }
    });
});
</script>

</body>
</html>