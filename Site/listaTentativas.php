<?php
// listaTentativas.php — Lista todas as tentativas do usuário para uma prova específica

session_start();
if (!isset($_SESSION['id'])) {
    header('Location: login.html');
    exit();
}

// 1. Conecta-se ao banco de dados e obtém a variável $conn
require_once 'conexao/conecta.php';

$idUsuario = (int) $_SESSION['id'];
$idProva   = isset($_GET['prova']) ? (int) $_GET['prova'] : 0;

if ($idProva <= 0) {
    http_response_code(400);
    echo "Parâmetro 'prova' inválido.";
    
    // É uma boa prática fechar a conexão mesmo em caso de erro de parâmetro
    if (isset($conn)) {
        $conn->close();
    }
    exit();
}

// Segurança/charset
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// 2) Busca dados da prova (nome/ano)
$sqlProva = "SELECT id, nome, anoProva FROM tb_prova WHERE id = ?";
$stmt = $conn->prepare($sqlProva);
$stmt->bind_param("i", $idProva);
$stmt->execute();
$prova = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prova) {
    http_response_code(404);
    echo "Prova não encontrada.";
    if (isset($conn)) {
        $conn->close();
    }
    exit();
}

// 3) Busca todas as tentativas do usuário nessa prova
$sqlTent = "
    SELECT id, acertos, erros, dataTentativa
    FROM tb_tentativas
    WHERE idUsuario = ? AND idProva = ?
    ORDER BY id ASC
";
$stmt = $conn->prepare($sqlTent);
$stmt->bind_param("ii", $idUsuario, $idProva);
$stmt->execute();
$resTent = $stmt->get_result();

$tentativas = $resTent->fetch_all(MYSQLI_ASSOC);
$qtd = (int) $resTent->num_rows;

$stmt->close();

// IMPORTANTE: REMOVIDO $conn->close(); daqui
// Ele foi movido para o final do arquivo, após o HTML,
// para que o menu.php possa utilizar a conexão.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tentativas — <?php echo htmlspecialchars($prova['nome']); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
/* VARIÁVEIS do listaTentativas.php, mantidas para seus componentes */
:root{
    --primary:#0d4b9e; --primary-dark:#0a3a7a; --primary-light:#3a6cb5;
    --gold:#D4AF37; --page-bg:#f5f7fa; /* RENOMEADO de --bg para --page-bg */
    --white:#fff; --muted:#6c757d; --text:#212529;
    --gray:#e0e5ec; --success:#1b9e3e; --danger:#c0392b;
}

/* O 'container' principal da página listaTentativas.php foi renomeado para evitar conflito com o .container do menu */
.list-container{
    max-width:980px;
    margin:24px auto;
    padding:0 16px;
}

/* Estilos gerais da lista */
.card{background:var(--white);border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);padding:24px;margin-bottom:24px}
.header{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.h1{margin:0;color:var(--primary-dark);font-size:1.35rem}
.meta{color:var(--muted);font-size:.95rem}
.back a{display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#fff;text-decoration:none;padding:8px 14px;border-radius:10px}
.table{width:100%;border-collapse:collapse;margin-top:8px}
.table th,.table td{padding:12px 14px;border:1px solid var(--gray);text-align:center}
.table th{background:var(--primary);color:#fff;text-transform:uppercase;font-size:.85rem}
.table tr:nth-child(even){background:#fafbff}
.kpi{display:flex;gap:16px;flex-wrap:wrap;margin-top:8px}
.kpi .box{border:1px solid var(--gray);border-radius:12px;padding:12px 16px;min-width:180px;background:#fff}
.kpi .big{font-weight:800;font-size:1.5rem}
.link{color:var(--primary);text-decoration:none}
.link:hover{text-decoration:underline}

/* --- CORREÇÃO DO CONFLITO DO BADGE --- */

/* Definindo o badge base apenas para este conteúdo para aumentar a especificidade */
.list-container .badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:4px 10px;
    border-radius:999px;
    font-size:.78rem;
    border:1px solid;
    background: transparent; /* Garante que não use o fundo de outros badges */
    font-weight: 600; /* Pode ser herdado, mas é bom garantir */
}

/* Acertos: Força a cor e borda */
.list-container .ok{
    color:var(--success) !important; 
    border-color:var(--success) !important;
}

/* Erros: Força a cor e borda */
.list-container .no{
    color:var(--danger) !important;
    border-color:var(--danger) !important;
}


/* --- ESTILOS RESPONSIVOS --- */
/* Em telas menores, a tabela se transforma em um layout de lista/cards */
@media (max-width: 768px) {
    .table thead {
        display: none; /* Esconde o cabeçalho da tabela */
    }
    .table, .table tbody, .table tr, .table td {
        display: block; /* Transforma todos os elementos em blocos */
        width: 100%;
    }
    .table tr {
        border-bottom: 2px solid var(--primary-light);
        margin-bottom: 1rem;
        padding-bottom: 1rem;
    }
    .table td {
        text-align: right;
        padding-left: 50%; /* Espaço para o "cabeçalho" da célula */
        position: relative;
    }
    .table td::before {
        content: attr(data-label); /* Usa o atributo data-label para criar o cabeçalho */
        position: absolute;
        left: 10px;
        width: 45%;
        padding-right: 10px;
        text-align: left;
        font-weight: bold;
        color: var(--muted);
    }
    /* Estilos específicos para a tabela móvel */
    .table tr td:first-child {
        font-size: 1.1rem;
        font-weight: bold;
        color: var(--primary-dark);
        border-top: none;
    }
}
</style>
</head>
<body>
    <?php 
    // Garante que o menu.php está no mesmo diretório do arquivo atual.
    // Se o menu.php estiver em outro lugar (ex: na raiz, use '../menu.php' ou $_SERVER['DOCUMENT_ROOT'] . '/menu.php'),
    // mas __DIR__ é o mais robusto se o caminho for o mesmo.
    include __DIR__ . '/menu.php'; // Inclui o menu de navegação - Agora a conexão está aberta.
    ?>
<div class="list-container">

    <div class="card header">
        <div>
            <h1 class="h1">Tentativas — <?php echo htmlspecialchars($prova['nome']); ?> (<?php echo htmlspecialchars($prova['anoProva']); ?>)</h1>
            <div class="meta">Prova <strong>#<?php echo (int)$prova['id']; ?></strong></div>
        </div>
        <div class="back">
            <a href="progresso.php"><i class="fa fa-arrow-left"></i> Voltar</a>
        </div>
    </div>

    <div class="card">
        <div class="kpi">
            <div class="box">
                <div class="meta">Quantidade de tentativas</div>
                <div class="big"><?php echo $qtd; ?></div>
            </div>
            <?php
            // KPI simples: melhor desempenho (maior acerto) nessa prova
            $best = 0; $bestErros = 0; $bestData = '';
            foreach ($tentativas as $t) {
                if ((int)$t['acertos'] > $best) {
                    $best = (int)$t['acertos'];
                    $bestErros = (int)$t['erros'];
                    $bestData = $t['dataTentativa'];
                }
            }
            ?>
            <div class="box">
                <div class="meta">Melhor desempenho</div>
                <div class="big"><?php echo $best; ?> acertos</div>
                <div class="meta"><?php echo $bestData ? "em {$bestData} (erros: {$bestErros})" : "—"; ?></div>
            </div>
        </div>

        <?php if ($qtd === 0): ?>
            <p class="meta" style="margin-top:12px">Nenhuma tentativa encontrada para esta prova.</p>
        <?php else: ?>
            <div class="responsive-table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th># Tentativa</th>
                            <th>Acertos</th>
                            <th>Erros</th>
                            <th>Data</th>
                            <th>Detalhe</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                        $contador = 1;
                        $tentativasEnumeradas = [];
                        foreach ($tentativas as $t) {
                            $tentativasEnumeradas[] = [
                                'numero' => $contador++,
                                'id' => $t['id'],
                                'acertos' => $t['acertos'],
                                'erros' => $t['erros'],
                                'data' => $t['dataTentativa']
                            ];
                        }

                        // Agora inverte só para exibir (última no topo)
                        $tentativasEnumeradas = array_reverse($tentativasEnumeradas);

                        foreach ($tentativasEnumeradas as $t): ?>
                            <tr>
                                <td data-label="# Tentativa"><?php echo $t['numero']; ?></td>
                                <td data-label="Acertos"><span class="badge ok"><i class="fa fa-check"></i> <?php echo (int)$t['acertos']; ?></span></td>
                                <td data-label="Erros"><span class="badge no"><i class="fa fa-xmark"></i> <?php echo (int)$t['erros']; ?></span></td>
                                <td data-label="Data"><?php echo htmlspecialchars($t['data']); ?></td>
                                <td data-label="Detalhe">
                                    <a class="link" href="resultadoProva.php?tentativa=<?php echo (int)$t['id']; ?>">
                                        Ver resultado
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<?php
// CORREÇÃO: FECHA A CONEXÃO AQUI, DEPOIS QUE TODO O CÓDIGO (INCLUINDO O MENU) FOI EXECUTADO.
if (isset($conn)) {
    $conn->close();
}
?>