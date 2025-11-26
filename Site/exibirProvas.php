<?php
// exibirProvas.php
// Garante o início da sessão e verifica o login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$idUsuario = (int)$_SESSION['id'];
// Carrega o plano do usuário (usando a session como base)
$planoUsuario = isset($_SESSION['planoUsuario']) ? $_SESSION['planoUsuario'] : 'Basico';

// Inclui o arquivo de conexão com o banco de dados
// Assumindo que este caminho está correto.
require_once __DIR__ . '/conexao/conecta.php';

/* ============================
    LIMITES POR PLANO (PROVAS)
============================ */
// Define o limite de provas por semana para cada plano.
// null = ilimitado
$limitesProvas = [
    'Basico' => 3, // Exemplo: 3 provas por semana
    'Individual' => null, // Ilimitado
    'Essencial' => null,
    'Pro' => null,
    'Premium' => null,
    'escolaPublica' => null
];

$limiteSemanalProvas = $limitesProvas[$planoUsuario] ?? null;

// Define variáveis de estado
$limiteExcedido = false;
$qtdProvasSemana = 0; // Inicializa para uso no aviso

// Checagem de limite, se aplicável ao plano
if (!is_null($limiteSemanalProvas)) {
    // Conta tentativas na semana atual (segunda a domingo - o '1' em YEARWEEK)
    // OBS: O formato de data 'd/m/Y' no MySQL requer STR_TO_DATE.
    $sqlLimite = "
        SELECT COUNT(*) AS total
        FROM tb_tentativas
        WHERE idUsuario = ?
        AND YEARWEEK(STR_TO_DATE(dataTentativa, '%d/%m/%Y'), 1) = YEARWEEK(CURDATE(), 1)
    ";
    
    // Prepara e executa a consulta
    $stmt = $conn->prepare($sqlLimite);
    if ($stmt) {
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $qtdProvasSemana = (int)$res['total'];

        if ($qtdProvasSemana >= $limiteSemanalProvas) {
            $limiteExcedido = true;
        }
    } else {
        // Em caso de erro de DB, preferimos deixar o usuário acessar a lista em vez de travar
        // error_log("Erro ao preparar SQL de limite: " . $conn->error);
    }
}

$id = $_SESSION['id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provas - Caminho do Saber</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            --primary:#0d4b9e;
            --primary-800:#0a3a7a;
            --gold:#D4AF37; /* Cor Ouro */
            --text:#212529;
            --bg:#f6f8fb;
            --card:#ffffff;
            --muted:#6c757d;
            --shadow:0 8px 24px rgba(0,0,0,.08);
            --shadow-lg:0 12px 28px rgba(0,0,0,.12);
            --tr: all .2s ease;

            /* Altura do header fixo do menu.php */
            --header-h: 120px; 
        }
        *{box-sizing:border-box}
        html,body{height:100%}
        body{
            margin:0;
            font-family:'Poppins',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
            background:var(--bg);
            color:var(--text);
            line-height:1.5;
        }
        
        /* ------------------------------------------- */
        /* --- ESTILOS DO AVISO DE LIMITE EXCEDIDO --- */
        /* ------------------------------------------- */
        .container-aviso {
            max-width: 550px; /* Levemente menor para focar a atenção */
            margin: 80px auto;
            background-color: var(--card);
            border: none; 
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,.15); /* Sombra mais destacada */
            transition: var(--tr);
            
            /* Destaque visual: Linha dourada no topo */
            border-top: 5px solid var(--gold); 
        }
        .icon-aviso {
            font-size: 3.5rem; /* Ícone maior */
            color: var(--gold); /* Dourado para premium */
            margin-bottom: 20px;
            display: block;
            /* Animação sutil para o ícone */
            animation: pulse .8s infinite alternate; 
        }
        @keyframes pulse {
            from { transform: scale(1); opacity: 0.9; }
            to { transform: scale(1.05); opacity: 1; }
        }
        .titulo-aviso {
            color: var(--primary-800);
            font-size: 2rem; /* Título maior */
            margin-bottom: 15px;
            font-weight: 800; /* Mais negrito */
            line-height: 1.2;
        }
        .mensagem-aviso {
            color: var(--muted); /* Texto principal mais suave */
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 35px; /* Mais espaço antes do CTA */
        }
        .mensagem-aviso strong {
            color: var(--text); /* Destaque em negrito com cor do texto principal */
        }
        .btn-upgrade {
            display: inline-block;
            padding: 18px 35px; /* Botão maior */
            /* Gradiente de ouro mais vibrante */
            background: linear-gradient(145deg, #FFD700, #DAA520); 
            color: #121212; /* Cor escura para melhor contraste no botão dourado */
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 50px;
            transition: var(--tr);
            /* Sombra mais forte para o CTA */
            box-shadow: 0 8px 20px rgba(218, 165, 32, 0.4); 
            border: 1px solid #FFD700;
        }
        .btn-upgrade:hover {
            transform: translateY(-3px); /* Efeito de elevação mais notável */
            box-shadow: 0 12px 25px rgba(218, 165, 32, 0.6);
            filter: brightness(1.05);
        }
        .link-voltar-aviso {
            display: block;
            margin-top: 25px;
            color: var(--muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem; /* Menor para ser secundário */
            opacity: 0.8;
            transition: var(--tr);
        }
        .link-voltar-aviso:hover {
            color: var(--primary);
            text-decoration: underline;
            opacity: 1;
        }
        /* ------------------------------------------- */


        /* ====== Estilos de Listagem de Provas (Mantidos) ====== */
        .toolbar{
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(255,255,255,.95);
            backdrop-filter: saturate(180%) blur(8px);
        }
        .toolbar-inner{
            max-width:1200px; margin:0 auto; padding:12px 20px;
            display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;
        }
        .count{font-size:.95rem; color:var(--muted)}
        .controls{display:flex; gap:10px; align-items:center; flex-wrap:wrap}
        .segment{display:flex; gap:8px; background:#eef2f8; padding:6px; border-radius:999px}
        .segment button{
            border:0; padding:8px 14px; border-radius:999px; cursor:pointer;
            background:transparent; font-weight:600; color:#3b4a62; transition:var(--tr);
            white-space:nowrap;
        }
        .segment button[aria-pressed="true"]{
            background:linear-gradient(135deg,var(--primary),var(--primary-800));
            color:#fff; box-shadow:0 3px 10px rgba(13,75,158,.25);
        }
        .segment button:focus{outline:none}
        .segment button:focus-visible{outline:3px solid rgba(212,175,55,.45)}
        .search{
            display:flex; align-items:center; gap:8px;
            background:#fff; border-radius:999px; padding:6px 10px;
            box-shadow: var(--shadow);
        }
        .search input{
            border:0; outline:0; padding:8px 6px; min-width:220px; background:transparent; font-size:.95rem;
        }
        .search .icon{color:#8391a5}
        .search .btn{
            border:0; background:transparent; cursor:pointer; color:#8391a5; padding:6px; border-radius:8px;
        }
        .search .btn:hover{background:#f1f4f9}
        .sort{
            display:flex; align-items:center; gap:8px; background:#fff; border-radius:12px; padding:6px 10px;
            box-shadow: var(--shadow);
        }
        .sort select{border:0; background:transparent; padding:6px; outline:0}
        .main {
            max-width: 1200px;
            margin: 0 auto 40px;
            padding: 0 20px;
            padding-top: 20px; 
        }
        .site-header-spacer { 
            height: var(--header-h);
            visibility: hidden;
            display: none; 
        }
        .year{
            margin:14px 0 18px; border-radius:16px; overflow:hidden; background:#fff;
            box-shadow:var(--shadow);
        }
        .year summary{
            list-style:none; cursor:pointer; padding:14px 16px; font-weight:700; font-size:1.05rem;
            background:linear-gradient(90deg,var(--primary),var(--primary-800)); color:#fff;
            display:flex; align-items:center; justify-content:space-between;
        }
        .year summary::-webkit-details-marker{display:none}
        .year .year-body{padding:14px}
        .year .meta{font-size:.85rem; color:#d8e4ff; font-weight:500}
        .grid{display:grid; gap:12px; grid-template-columns:repeat(1,minmax(0,1fr))}
        @media (min-width:600px){ .grid{grid-template-columns:repeat(2,minmax(0,1fr))} }
        @media (min-width:980px){ .grid{grid-template-columns:repeat(3,minmax(0,1fr))} }
        .card{
            background:var(--card); border-radius:14px; padding:14px;
            box-shadow:var(--shadow);
            display:flex; gap:12px; align-items:flex-start; transition:var(--tr);
        }
        .card:hover, .card:focus-within{transform:translateY(-2px); box-shadow:var(--shadow-lg)}
        .card .icon{
            flex:0 0 40px; height:40px; border-radius:10px; display:grid; place-items:center;
            background:#eef3ff; color:var(--primary);
        }
        .card .content{flex:1 1 auto}
        .card a{text-decoration:none; color:var(--primary); font-weight:600; display:inline-block}
        .card a:focus-visible{outline:3px solid rgba(212,175,55,.45); border-radius:6px}
        .badges{display:flex; gap:8px; margin-top:6px; flex-wrap:wrap}
        .badge{
            display:inline-flex; align-items:center; gap:6px; padding:4px 8px; border-radius:999px; font-size:.75rem; font-weight:600;
            background:#f6f8fc; color:#50607a !important;
        }
        .empty{
            text-align:center; padding:28px; color:var(--muted); background:#fff;
            border-radius:14px; box-shadow: var(--shadow);
        }
        .empty .actions{margin-top:10px}
        .empty .actions button{
            border:0; padding:10px 14px; border-radius:10px; cursor:pointer; font-weight:600;
            background:linear-gradient(135deg,var(--primary),var(--primary-800)); color:#fff;
        }
        footer{
            background:linear-gradient(135deg,var(--primary),#121212);
            color:#fff; border-top:3px solid var(--gold); text-align:center; padding:18px;
        }
        footer a{color:var(--gold); text-decoration:none}
        footer a:hover{text-decoration:underline}

        .hidden{display:none !important}
        .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
        .main:focus { outline: none; box-shadow: none; border: none; }
    </style>
</head>
<body>

    <?php 
    // Assumindo que o arquivo menu.php existe no diretório atual ou raiz do projeto
    include __DIR__ . '/menu.php'; // Inclui o menu de navegação 
    ?>

    <div class="site-header-spacer" aria-hidden="true"></div>

    <?php 
    // ==============================================
    // BLOCO DE AVISO DE LIMITE EXCEDIDO (PRIORITÁRIO)
    // ==============================================
    if ($limiteExcedido): 
        // Variável auxiliar para a mensagem
        $provasRestantes = $limiteSemanalProvas - $qtdProvasSemana;
    ?>
        <main class="main" id="resultados" tabindex="-1">
            <div class='container-aviso'>
                <span class='icon-aviso'>
                    <i class="fas fa-crown"></i> 
                </span>
                <h2 class='titulo-aviso'>Prática Ilimitada Bloqueada!</h2>
                <p class='mensagem-aviso'>
                    Seu plano **<?php echo ucfirst($planoUsuario); ?>** permite **<?php echo $limiteSemanalProvas; ?> provas por semana**.
                    <br>
                    Você já utilizou o seu limite de **<?php echo $qtdProvasSemana; ?> acessos** nesta semana.
                    <br><br>
                    Para **liberar o acesso irrestrito** a *todas as provas* e *todos os simulados* agora mesmo, faça seu **upgrade**!
                </p>
                <a href='configuracao/configuracoes.php?tab=plans' class='btn-upgrade'>
                    <i class="fas fa-arrow-up"></i> Quero o Acesso Ilimitado
                </a>
                
                <a href='#' onclick="history.back(); return false;" class='link-voltar-aviso'>
                    <i class="fas fa-arrow-left"></i> Entendi. Voltar para a lista
                </a>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados. <a href="POLITICA.php">Política de privacidade</a></p>
        </footer>
    
    </body>
    </html>
    <?php
        // Fechamento da conexão e interrupção do script PHP
        // Se a conexão foi aberta antes do bloco 'if ($limiteExcedido)', ela precisa ser fechada aqui.
        if (isset($conn)) {
             $conn->close();
        }
        exit(); // Encerra a execução após exibir o aviso
    ?>
    <?php 
    // ==============================================
    // FIM BLOCO DE AVISO DE LIMITE
    // ==============================================
    endif; 
    
    // ==============================================
    // LÓGICA DE LISTAGEM DE PROVAS (SE O LIMITE NÃO FOI EXCEDIDO)
    // ==============================================

    // Instituições (chips de filtro)
    $instituicoes = [];
    // A query para instituições deve vir APÓS o bloco de limite, 
    // mas antes da listagem de provas
    $resInst = $conn->query("SELECT id, nome FROM tb_instituicao ORDER BY nome ASC");
    if ($resInst && $resInst->num_rows > 0) {
        while ($r = $resInst->fetch_assoc()) {
            $instituicoes[] = ['id' => (int)$r['id'], 'nome' => $r['nome']];
        }
    }

    $termoBusca = isset($_GET['nome']) ? trim($_GET['nome']) : '';

    // Lógica para busca com filtro (popula $buscaAgrupada)
    $buscaAgrupada = [];
    if ($termoBusca !== '') {
        $sqlBusca = "
        SELECT p.id, p.nome, p.anoProva, p.id_instituicao, i.nome AS instituicao
        FROM tb_prova p
        LEFT JOIN tb_instituicao i ON i.id = p.id_instituicao
        WHERE p.nome LIKE ? AND p.simulado = 'não'
        ORDER BY p.anoProva DESC, p.nome ASC
         ";
        $stmt = $conn->prepare($sqlBusca);
        if ($stmt) {
            $like = '%'.$termoBusca.'%';
            $stmt->bind_param('s', $like);
            $stmt->execute();
            $r = $stmt->get_result();
            while ($row = $r->fetch_assoc()) {
                $ano = (int)$row['anoProva'];
                if (!isset($buscaAgrupada[$ano])) $buscaAgrupada[$ano] = [];
                $buscaAgrupada[$ano][] = $row;
            }
            $stmt->close();
        }
        krsort($buscaAgrupada);
    }

    // Lógica para listagem principal (popula $agrupado)
    $agrupado = [];
    $sqlProvas = "
     SELECT p.id, p.nome, p.anoProva, p.id_instituicao, i.nome AS instituicao
    FROM tb_prova p
     LEFT JOIN tb_instituicao i ON i.id = p.id_instituicao
     WHERE p.simulado = 'não'
     ORDER BY p.anoProva DESC, p.nome ASC
     ";
    $res = $conn->query($sqlProvas);
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $ano = (int)$row['anoProva'];
            if (!isset($agrupado[$ano])) $agrupado[$ano] = [];
            $agrupado[$ano][] = $row;
        }
    }
    krsort($agrupado);

    // Fechamento da conexão após todas as consultas
    if (isset($conn)) {
        $conn->close();
    }
    ?>

    <div class="toolbar" role="region" aria-label="Filtros e busca">
        <div class="toolbar-inner">
            <div class="count">
                <span id="totalCount">0</span> provas
            </div>

            <div class="controls">
                <div class="segment" role="tablist" aria-label="Filtro por instituição">
                    <button type="button" role="tab" class="seg" data-inst="all" aria-pressed="true">Todas</button>
                    <?php foreach ($instituicoes as $inst): ?>
                        <button
                            type="button"
                            role="tab"
                            class="seg"
                            data-inst="<?php echo (int)$inst['id']; ?>"
                            title="<?php echo htmlspecialchars($inst['nome']); ?>">
                            <?php echo htmlspecialchars($inst['nome']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <form class="search" method="GET" action="#resultados" role="search" aria-label="Buscar provas">
                    <i class="fa-solid fa-magnifying-glass icon" aria-hidden="true"></i>
                    <label for="nome" class="sr-only">Pesquisar por nome da prova</label>
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Pesquisar por nome da prova..."
                        value="<?php echo htmlspecialchars($termoBusca); ?>">
                    <button class="btn" type="button" id="clearSearch" title="Limpar busca" aria-label="Limpar busca">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <button class="btn" type="submit" title="Buscar" aria-label="Executar busca">
                        <i class="fa-solid fa-arrow-turn-down"></i>
                    </button>
                </form>

                <div class="sort" aria-label="Ordenação">
                    <i class="fa-solid fa-arrow-up-wide-short" aria-hidden="true"></i>
                    <label for="ordenar" class="sr-only">Ordenar por</label>
                    <select id="ordenar">
                        <option value="anoDesc">Ano (mais recente)</option>
                        <option value="anoAsc">Ano (mais antigo)</option>
                        <option value="nomeAsc">Nome (A–Z)</option>
                        <option value="nomeDesc">Nome (Z–A)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <main class="main" id="resultados" tabindex="-1">
        <?php if ($termoBusca !== ''): // Exibe resultados da busca se houver termo ?>
            <section aria-labelledby="titulo-busca">
                <h2 id="titulo-busca" class="sr-only">Resultados da busca</h2>

                <?php if (count($buscaAgrupada) > 0): ?>
                    <div id="buscaGrid">
                    <?php foreach ($buscaAgrupada as $ano => $lista): ?>
                        <details class="year" data-year="<?php echo (int)$ano; ?>" open>
                            <summary>
                                <span><?php echo (int)$ano; ?> <span class="meta">— clique para expandir/retrair</span></span>
                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                            </summary>
                            <div class="year-body">
                                <ul class="grid">
                                    <?php foreach ($lista as $row): ?>
                                        <li
                                            class="card"
                                            data-inst-id="<?php echo (int)$row['id_instituicao']; ?>"
                                            data-inst-name="<?php echo htmlspecialchars(mb_strtolower($row['instituicao'] ?? 'sem instituição','UTF-8')); ?>"
                                            data-ano="<?php echo (int)$row['anoProva']; ?>"
                                            data-nome="<?php echo htmlspecialchars(mb_strtolower($row['nome'],'UTF-8')); ?>">
                                            <div class="icon"><i class="fa-regular fa-file-lines"></i></div>
                                            <div class="content">
                                                <a href="mostraQuest.php?id=<?php echo (int)$row['id']; ?>" class="prova-link">
                                                    <?php echo htmlspecialchars($row['nome']); ?>
                                                </a>
                                                <div class="badges">
                                                    <span class="badge"><i class="fa-regular fa-building"></i>
                                                        <?php echo htmlspecialchars($row['instituicao'] ?? 'Sem instituição'); ?>
                                                    </span>
                                                    <span class="badge"><i class="fa-regular fa-calendar"></i>
                                                        <?php echo (int)$row['anoProva']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </details>
                    <?php endforeach; ?>
                    </div>
                    <hr style="margin:22px 0;border:0;height:1px;background:#eee">
                <?php else: ?>
                    <div class="empty">
                        <strong>Nenhuma prova encontrada para "<?php echo htmlspecialchars($termoBusca); ?>".</strong>
                        <div class="actions"><button type="button" id="limparTudo1">Limpar filtros</button></div>
                    </div>
                    <hr style="margin:22px 0;border:0;height:1px;background:#eee">
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section aria-labelledby="titulo-ano">
            <h2 id="titulo-ano" class="sr-only">Provas por ano</h2>
            <div id="provasList">
                <?php if (count($agrupado) > 0): // Listagem principal ?>
                    <?php foreach ($agrupado as $ano => $lista): ?>
                        <details class="year" data-year="<?php echo (int)$ano; ?>" open>
                            <summary>
                                <span><?php echo (int)$ano; ?> <span class="meta">— clique para expandir/retrair</span></span>
                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                            </summary>
                            <div class="year-body">
                                <ul class="grid">
                                    <?php foreach ($lista as $row): ?>
                                        <li
                                            class="card"
                                            data-inst-id="<?php echo (int)$row['id_instituicao']; ?>"
                                            data-inst-name="<?php echo htmlspecialchars(mb_strtolower($row['instituicao'] ?? 'sem instituição','UTF-8')); ?>"
                                            data-ano="<?php echo (int)$row['anoProva']; ?>"
                                            data-nome="<?php echo htmlspecialchars(mb_strtolower($row['nome'],'UTF-8')); ?>">
                                            <div class="icon"><i class="fa-regular fa-file-lines"></i></div>
                                            <div class="content">
                                                <a href="mostraQuest.php?id=<?php echo (int)$row['id']; ?>" class="prova-link">
                                                    <?php echo htmlspecialchars($row['nome']); ?>
                                                </a>
                                                <div class="badges">
                                                    <span class="badge"><i class="fa-regular fa-building"></i>
                                                        <?php echo htmlspecialchars($row['instituicao'] ?? 'Sem instituição'); ?>
                                                    </span>
                                                    <span class="badge"><i class="fa-regular fa-calendar"></i>
                                                        <?php echo (int)$row['anoProva']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </details>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty"><strong>Nenhuma prova disponível no momento.</strong></div>
                <?php endif; ?>
            </div>

            <div id="emptyState" class="empty hidden">
                <strong>Nenhuma prova corresponde aos filtros aplicados.</strong>
                <div class="actions"><button type="button" id="limparTudo2">Limpar filtros</button></div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados. <a href="POLITICA.php">Política de privacidade</a></p>
    </footer>

    <script>
        // ---------- Helpers ----------
        const $ = (sel,root=document)=>root.querySelector(sel);
        const $$ = (sel,root=document)=>Array.from(root.querySelectorAll(sel));

        // ---------- Compat: dropdown do menu (caso o menu.php não injete JS) ----------
        (function(){
            const btn = document.getElementById('userToggle');
            const drop = document.getElementById('userDropdown');
            if (btn && drop) {
                btn.addEventListener('click', function(e){
                    e.stopPropagation();
                    drop.classList.toggle('show');
                });
                document.addEventListener('click', function(){
                    if (drop.classList.contains('show')) drop.classList.remove('show');
                });
            }
        })();

        // ---------- Estado de busca vs lista ----------
        const urlParams = new URLSearchParams(location.search);
        const hasSearch = !!(urlParams.get('nome') && urlParams.get('nome').trim() !== '');

        // Containers
        const buscaGrid  = $('#buscaGrid');
        const provasList  = $('#provasList');
        const emptyState  = $('#emptyState');
        const totalCount  = $('#totalCount');

        // Controles
        const segButtons  = $$('.seg');
        const ordenarSel  = $('#ordenar');
        const clearSearch = $('#clearSearch');
        const inputSearch = $('#nome');

        // Filtro por instituição (querystring ou localStorage)
        let currentInst = urlParams.get('inst') || 'all';
        const savedInst = localStorage.getItem('provas.inst');
        if (!urlParams.has('inst') && savedInst) currentInst = savedInst;

        function showOnlyRelevantContainer() {
            if (hasSearch) {
                buscaGrid?.classList.remove('hidden');
                provasList?.classList.add('hidden');
            } else {
                provasList?.classList.remove('hidden');
                buscaGrid?.classList.add('hidden');
            }
        }

        function setInstFilter(inst){
            currentInst = inst;
            localStorage.setItem('provas.inst', inst);
            segButtons.forEach(b=>b.setAttribute('aria-pressed', b.dataset.inst===inst ? 'true':'false'));

            const url = new URL(location.href);
            if (inst==='all') url.searchParams.delete('inst'); else url.searchParams.set('inst', inst);
            history.replaceState({},'',url);

            applyFilters();
        }

        segButtons.forEach(b=>{
            b.addEventListener('click', ()=>setInstFilter(b.dataset.inst));
            b.addEventListener('keydown', (e)=>{
                if (e.key==='Enter' || e.key===' ') { e.preventDefault(); setInstFilter(b.dataset.inst); }
            });
        });

        // Ordenação
        function sortInside(container){
            if (!container) return;
            const val = ordenarSel.value;
            const isAsc = v => (v==='anoAsc' || v==='nomeAsc');

            if (val.startsWith('ano')) {
                const groups = $$('.year', container);
                // Ordena os blocos de detalhes (anos)
                groups.sort((a,b)=> (isAsc(val) ? (+a.dataset.year - +b.dataset.year) : (+b.dataset.year - +a.dataset.year)));
                groups.forEach(g=>container.appendChild(g));
            }
            $$('details.year', container).forEach(group=>{
                const grid = $('.grid', group);
                if (!grid) return;
                const cards = $$('.card', grid);
                if (val.startsWith('nome')) {
                    // Ordena os cards dentro do grid
                    cards.sort((a,b)=> {
                        const na = a.dataset.nome, nb = b.dataset.nome;
                        return isAsc(val) ? na.localeCompare(nb) : nb.localeCompare(na);
                    });
                    cards.forEach(c=>grid.appendChild(c));
                }
            });
        }
        ordenarSel.addEventListener('change', ()=>{
            if (hasSearch) sortInside(buscaGrid);
            else           sortInside(provasList);
        });

        // Busca: limpar
        clearSearch?.addEventListener('click', ()=>{
            inputSearch.value='';
            const url = new URL(location.href);
            url.searchParams.delete('nome');
            // Redireciona limpando o parâmetro 'nome' da URL
            location.href = url.toString();
        });
        
        // Limpar tudo para os botões do empty state
        document.getElementById('limparTudo1')?.addEventListener('click', clearAllFilters);
        document.getElementById('limparTudo2')?.addEventListener('click', clearAllFilters);

        function clearAllFilters() {
            // Remove inst do localStorage
            localStorage.removeItem('provas.inst');

            const url = new URL(location.href);
            // Remove todos os parâmetros de filtro/busca da URL
            url.searchParams.delete('nome');
            url.searchParams.delete('inst');
            
            // Redireciona para URL limpa, forçando o reload
            location.href = url.toString().split('#')[0];
        }


        function applyFilters(){
            showOnlyRelevantContainer();
            const activeContainer = hasSearch ? buscaGrid : provasList;
            if (!activeContainer) {
                totalCount.textContent = '0';
                emptyState?.classList.remove('hidden');
                return;
            }
            let totalVisiveis = 0;
            
            // 1. Filtra os cards por instituição
            $$('.card', activeContainer).forEach(card=>{
                const instId = card.dataset.instId || '';
                const show = (currentInst==='all') || (String(instId)===String(currentInst));
                card.classList.toggle('hidden', !show);
                if (show) totalVisiveis++;
            });
            
            // 2. Esconde/mostra o bloco de ano
            $$('details.year', activeContainer).forEach(group=>{
                const hasVisible = $$('.card', group).some(c=>!c.classList.contains('hidden'));
                group.classList.toggle('hidden', !hasVisible);
            });
            
            // 3. Atualiza contagem e empty state
            totalCount.textContent = totalVisiveis;
            emptyState?.classList.toggle('hidden', totalVisiveis>0);
        }

        // Persistência: colapso por ano
        $$('details.year').forEach(d=>{
            const y = d.dataset.year;
            const key = `provas.year.${y}`;
            const savedOpen = localStorage.getItem(key);
            if (savedOpen!==null) d.open = savedOpen==='1';
            d.addEventListener('toggle', ()=>localStorage.setItem(key, d.open?'1':'0'));
        });

        // Init
        // Aplica o filtro de instituição na inicialização
        setInstFilter(currentInst); 
        
        // Aplica a ordenação na inicialização
        ordenarSel.value = ordenarSel.value || 'anoDesc'; // Garante um valor padrão
        if (hasSearch) sortInside(buscaGrid); else sortInside(provasList);

        // Foca na seção de resultados se houver um hash (ex: após a busca)
        if (location.hash==="#resultados") { $('#resultados')?.focus?.(); }
    </script>
</body>
</html>