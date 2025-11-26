<?php
// =========================================================================
// !!! CÓDIGOS DE DEBUG (REMOVER EM PRODUÇÃO APÓS O CONSERTO) !!!
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =========================================================================

session_start();

// Garante que o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

// Inclui o arquivo de conexão centralizado
// VERIFIQUE SE O CAMINHO ESTÁ CORRETO: '../conexao/conecta.php'
require_once '../conexao/conecta.php';


// =========================================================================
// 1. CONFIGURAÇÃO DE PERMISSÃO E BUSCA DO CÓDIGO DA ESCOLA
// =========================================================================

// Mapeamento de Prova e Área de Conhecimento (ID84)
$mapaQuestoesID84 = [
    1 => 'BIOLOGIA', 2 => 'BIOLOGIA', 3 => 'BIOLOGIA', 4 => 'BIOLOGIA', 5 => 'BIOLOGIA',
    6 => 'INGLÊS', 7 => 'INGLÊS', 13 => 'INGLÊS', 14 => 'INGLÊS', 15 => 'INGLÊS',
    8 => 'PORTUGUÊS', 9 => 'PORTUGUÊS', 10 => 'PORTUGUÊS', 11 => 'PORTUGUÊS', 12 => 'PORTUGUÊS',
    16 => 'QUÍMICA', 17 => 'QUÍMICA', 18 => 'QUÍMICA', 19 => 'QUÍMICA', 20 => 'QUÍMICA',
    21 => 'FÍSICA', 22 => 'FÍSICA', 23 => 'FÍSICA', 24 => 'FÍSICA', 25 => 'FÍSICA',
    26 => 'MATEMÁTICA', 27 => 'MATEMÁTICA', 28 => 'MATEMÁTICA', 29 => 'MATEMÁTICA', 30 => 'MATEMÁTICA',
];
$idProvaAlvo = 84;
$nomeProvaAlvo = 'ID84 - Desempenho por Matéria';

// Verifica permissão (mantido)
$tipoUsuarioSessao = strtolower($_SESSION['tipoUsuario'] ?? '');

if ($tipoUsuarioSessao !== 'administrador' && $tipoUsuarioSessao !== 'diretor') {
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
}

$codigoEscolaUsuario = $_SESSION['codigoEscola'] ?? '';
$codigoEscolaFiltrada = ''; 

if ($tipoUsuarioSessao === 'administrador') {
    $codigoEscolaFiltrada = isset($_GET['codigoEscola']) ? trim($_GET['codigoEscola']) : '';
} else {
    $codigoEscolaFiltrada = $codigoEscolaUsuario;
    if (empty($codigoEscolaUsuario)) {
        die("Acesso negado: Código de escola não encontrado para o seu perfil.");
    }
}

$tituloExibicao = empty($codigoEscolaFiltrada) ? "TODAS AS ESCOLAS (Admin)" : "ESCOLA: " . htmlspecialchars($codigoEscolaFiltrada);


// =========================================================================
// 2. CONFIGURAÇÃO DOS FILTROS (ESCOLAS)
// =========================================================================

$escolaMap = [];
$sqlEscolaMap = "SELECT codigoEscola, nome FROM tb_escola WHERE codigoEscola IS NOT NULL AND codigoEscola != ''"; 
$resultEscolaMap = $conn->query($sqlEscolaMap);
if ($resultEscolaMap) {
    while ($row = $resultEscolaMap->fetch_assoc()) {
        $escolaMap[$row['codigoEscola']] = htmlspecialchars($row['nome']);
    }
}

function getNomeEscola($codigo, $map) {
    return $map[$codigo] ?? 'Nome não encontrado';
}

$escolas = [];
if ($tipoUsuarioSessao === 'administrador') {
    foreach ($escolaMap as $codigo => $nome) {
        $escolas[] = ['codigoEscola' => $codigo, 'nomeEscola' => $nome];
    }
}

$nomeEscolaFiltrada = empty($codigoEscolaFiltrada) 
    ? "Todas as Escolas" 
    : getNomeEscola($codigoEscolaFiltrada, $escolaMap) . " (" . $codigoEscolaFiltrada . ")";


// 2.3 Montagem Dinâmica da Cláusula WHERE e Parâmetros de Binding
$whereClauseBase = "t.idProva = ?";
$bindTypes = "i";
$bindParams = [$idProvaAlvo]; 

if (!empty($codigoEscolaFiltrada)) {
    $whereClauseBase .= " AND u.codigoEscola = ?";
    $bindTypes .= "s";
    $bindParams[] = $codigoEscolaFiltrada; 
} 

// Cria a lista de referências para o bind_param.
$bindParamsRefs = [$bindTypes];
foreach ($bindParams as $key => $value) {
    $bindParamsRefs[] = &$bindParams[$key]; // Passa a referência da variável
}


// =========================================================================
// 3. CONSULTA PARA O RELATÓRIO AGREGADO POR MATÉRIA (Geral da Escola)
// =========================================================================

// Converte o mapa de questões PHP para uma estrutura SQL CASE WHEN
$sqlCaseMateria = "CASE ";
foreach ($mapaQuestoesID84 as $questao => $materia) {
    // Usando 'q.numQuestao'
    $sqlCaseMateria .= "WHEN q.numQuestao = {$questao} THEN '{$materia}' ";
}
$sqlCaseMateria .= "ELSE 'Outros' END";

$sqlAgregadoMateria = "
    SELECT
        " . (empty($codigoEscolaFiltrada) ? "u.codigoEscola AS codigoEscola, " : "") . "
        ({$sqlCaseMateria}) AS materia,
        SUM(CASE WHEN r.correta = 1 THEN 1 ELSE 0 END) AS totalAcertos,
        SUM(CASE WHEN r.correta = 0 THEN 1 ELSE 0 END) AS totalErros,
        COUNT(r.id) AS totalQuestoes
    FROM 
        tb_respostas r
    JOIN 
        tb_tentativas t ON r.idTentativa = t.id
    JOIN 
        tb_usuario u ON t.idUsuario = u.id
    JOIN 
        tb_quest q ON r.idQuestao = q.id
    WHERE
        " . $whereClauseBase . "
    GROUP BY
        " . (empty($codigoEscolaFiltrada) ? "u.codigoEscola, " : "") . "
        materia
    ORDER BY
        " . (empty($codigoEscolaFiltrada) ? "u.codigoEscola ASC, " : "") . "
        materia ASC;
";

$stmtAgregadoMateria = $conn->prepare($sqlAgregadoMateria);
if ($stmtAgregadoMateria === false) {
    die("Erro na preparação da consulta agregada por matéria: " . $conn->error);
}

if (!empty($bindTypes)) {
    call_user_func_array([$stmtAgregadoMateria, 'bind_param'], $bindParamsRefs);
}

$stmtAgregadoMateria->execute();
$resultAgregadoMateria = $stmtAgregadoMateria->get_result();

$relatorioAgregadoMateria = [];
if ($resultAgregadoMateria->num_rows > 0) {
    while ($row = $resultAgregadoMateria->fetch_assoc()) {
        $row['percentualAcerto'] = ($row['totalQuestoes'] > 0) 
            ? round(($row['totalAcertos'] * 100.0) / $row['totalQuestoes'], 2) 
            : 0;
        $relatorioAgregadoMateria[] = $row;
    }
}
$stmtAgregadoMateria->close();


// =========================================================================
// 4. CONSULTA PARA O RELATÓRIO DETALHADO POR ALUNO E MATÉRIA
// =========================================================================
$sqlDetalhadoMateria = "
    SELECT
        u.nomeCompleto,
        u.codigoEscola, 
        t.dataTentativa,
        ({$sqlCaseMateria}) AS materia,
        SUM(CASE WHEN r.correta = 1 THEN 1 ELSE 0 END) AS acertos,
        SUM(CASE WHEN r.correta = 0 THEN 1 ELSE 0 END) AS erros,
        COUNT(r.id) AS totalQuestoes
    FROM 
        tb_respostas r
    JOIN 
        tb_tentativas t ON r.idTentativa = t.id
    JOIN 
        tb_usuario u ON t.idUsuario = u.id
    JOIN 
        tb_quest q ON r.idQuestao = q.id
    WHERE
        " . $whereClauseBase . "
    GROUP BY
        u.codigoEscola, 
        u.nomeCompleto,
        t.dataTentativa,
        materia
    ORDER BY
        u.codigoEscola ASC, 
        u.nomeCompleto ASC,
        materia ASC,
        t.dataTentativa DESC;
";

$stmtDetalhadoMateria = $conn->prepare($sqlDetalhadoMateria);
if ($stmtDetalhadoMateria === false) {
    die("Erro na preparação da consulta detalhada por matéria: " . $conn->error);
}

if (!empty($bindTypes)) {
    call_user_func_array([$stmtDetalhadoMateria, 'bind_param'], $bindParamsRefs);
}

$stmtDetalhadoMateria->execute();
$resultDetalhadoMateria = $stmtDetalhadoMateria->get_result();

$relatorioDetalhadoMateria = [];
if ($resultDetalhadoMateria->num_rows > 0) {
    while ($row = $resultDetalhadoMateria->fetch_assoc()) {
        $row['percentualAcerto'] = ($row['totalQuestoes'] > 0) 
            ? round(($row['acertos'] * 100.0) / $row['totalQuestoes'], 2) 
            : 0;
        $relatorioDetalhadoMateria[] = $row;
    }
}
$stmtDetalhadoMateria->close();

$conn->close();

// =========================================================================
// 5. PREPARAÇÃO DO LINK DO PDF (CORRIGIDO)
// =========================================================================
$pdf_url = 'relatorioID84PorMateria_pdf.php';
// Adiciona o filtro de escola na URL do PDF
if (!empty($codigoEscolaFiltrada)) {
    $pdf_url .= '?codigoEscola=' . urlencode($codigoEscolaFiltrada);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Desempenho <?= $tituloExibicao ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* CSS mantido para estilo e responsividade */
        :root { --primary-color: #007bff; --primary-color2: #ff0028; --dark-text: #333333; --light-bg: #f4f7f6; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); color: var(--dark-text); line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh; }
        main { padding-top: 146px; padding-left: 40px; padding-right: 40px; padding-bottom: 40px; flex-grow: 1; }
        @media (min-width: 992px) { main { padding-top: 200px; padding-left: 120px; padding-right: 120px; } }
        @media (max-width: 768px) { main { padding-top: 230px; padding-left: 20px; padding-right: 20px; } }
        h1 { color: var(--primary-color); margin-bottom: 20px; font-weight: 600; font-size: 2em; }
        h2 { color: var(--dark-text); margin-top: 20px; margin-bottom: 15px; font-weight: 500; font-size: 1.5em; }
        .btn-submit { background-color: var(--primary-color2); color: white; border: none; border-radius: 5px; padding: 10px 20px; cursor: pointer; font-weight: 500; transition: background-color 0.3s ease; text-decoration: none; display: inline-block; }
        .btn-submit:hover { background-color: #0056b3; }
        footer { text-align: center; padding: 15px; background-color: #343a40; color: white; font-size: 0.9em; margin-top: auto; }
        .form-container { max-width: 1200px; background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); margin: 0 auto; }
        .filter-form { background-color: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; }
        .filter-form .form-group { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .filter-form select { padding: 10px; border: 1px solid #ccc; border-radius: 5px; width: 100%; }
        .table-wrapper { overflow-x: auto; margin-bottom: 40px; }
        .table-relatorio { width: 100%; min-width: 600px; border-collapse: collapse; }
        .table-relatorio th, .table-relatorio td { text-align: center; padding: 8px; border: 1px solid #ddd; }
        .materia-header { background-color: #f7f7f7; color: var(--dark-text); font-weight: 600; text-align: left !important; font-size: 1em; padding-left: 20px !important; }
        .escola-header { background-color: #e0f7fa; font-weight: bold; text-align: left !important; font-size: 1.1em; }
        .escola-cell-content { display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .escola-cell-content .codigo { font-weight: bold; font-size: 1em; line-height: 1.2; }
        .escola-cell-content .nome { font-size: 0.75em; color: #666; line-height: 1.2; }
        .escola-header-agrupamento { display: flex; flex-direction: column; align-items: flex-start; padding: 5px 10px; }
        .escola-header-agrupamento .nome { font-weight: normal; font-size: 0.9em; color: #444; }
        .aluno-row td:first-child { text-align: left !important; padding-left: 30px; }
        @media (max-width: 768px) {
             /* Estilos responsivos omitidos por brevidade */
        }
    </style>
</head>
<body>
    
    <?php 
    // Certifique-se que o arquivo menu.php está no local correto e contém a navegação
    include 'menu.php'; 
    ?>

    <main>
        <div class="form-container">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1><i class="fas fa-book-reader"></i> Desempenho Simulado 3° Ano por Matéria: <?= $tituloExibicao ?></h1>
                
                <a href="<?= $pdf_url ?>" class="btn-submit" style="white-space: nowrap; margin-bottom: 0;" target="_blank">
                    <i class="fas fa-file-pdf"></i> Baixar PDF
                </a>
            </div>

            <form action="relatorioID84PorMateria.php" method="GET" class="filter-form">
                <div class="form-group" style="flex-direction: row; flex-wrap: wrap;"> 
                    
                    <?php if ($tipoUsuarioSessao === 'administrador'): ?>
                        <div style="flex-grow: 1; min-width: 250px;">
                            <label for="codigoEscola" style="display: block; margin-bottom: 5px; font-weight: bold;">Filtrar por Escola (Cod):</label>
                            <select id="codigoEscola" name="codigoEscola">
                                <option value="" <?= (empty($codigoEscolaFiltrada)) ? 'selected' : '' ?>>Todas as Escolas (<?= htmlspecialchars(count($escolas)) ?>)</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= htmlspecialchars($escola['codigoEscola']) ?>" 
                                            <?= ($codigoEscolaFiltrada === $escola['codigoEscola']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($escola['codigoEscola']) ?> - <?= htmlspecialchars($escola['nomeEscola']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="codigoEscola" value="<?= htmlspecialchars($codigoEscolaUsuario) ?>">
                    <?php endif; ?>

                    <div style="flex-grow: 1; min-width: 250px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Prova Fixa:</label>
                        <p style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; background-color: #eee; width: 100%;">ID84</p>
                    </div>

                    <button type="submit" class="btn-submit" style="white-space: nowrap; align-self: flex-end;">
                        <i class="fas fa-filter"></i> Aplicar Filtro
                    </button>
                </div>
                
                <?php if (!empty($codigoEscolaFiltrada) || $tipoUsuarioSessao === 'diretor'): ?>
                    <p style="margin-top: 10px; font-weight: bold;">
                        <i class="fas fa-check-circle" style="color: green;"></i> Filtros Ativos: 
                        Escola: <?= htmlspecialchars($nomeEscolaFiltrada) ?> | Prova: ID84 (Por Matéria)
                    </p>
                <?php endif; ?>
            </form>
            <hr style="margin-top: 20px; border-color: #ccc;">
            
            <h2 style="margin-top: 30px; color: var(--primary-color);">Desempenho Agregado por Matéria (Prova ID84)</h2>
            
            <?php if (empty($relatorioAgregadoMateria)): ?>
                <p style="text-align: center; margin-top: 20px;">Nenhuma tentativa da Prova ID84 encontrada com os filtros aplicados.</p>
            <?php else: ?>
                
            <div class="table-wrapper">
                <table class="table-relatorio table-relatorio-agregada">
                    <thead>
                        <tr>
                            <?php if (empty($codigoEscolaFiltrada)): ?>
                                <th style="width: 20%;">Cód. Escola</th>
                            <?php endif; ?>
                            <th style="width: <?= empty($codigoEscolaFiltrada) ? '30%' : '50%' ?>; text-align: left !important; padding-left: 20px;">Matéria</th>
                            <th>Total Questões</th>
                            <th>Total Acertos</th>
                            <th>Total Erros</th>
                            <th>% de Acerto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relatorioAgregadoMateria as $linha): ?>
                        <tr>
                            <?php if (empty($codigoEscolaFiltrada)): ?>
                                <td>
                                    <div class="escola-cell-content">
                                        <span class="codigo"><?= htmlspecialchars($linha['codigoEscola']) ?></span>
                                        <span class="nome"><?= getNomeEscola($linha['codigoEscola'], $escolaMap) ?></span>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td style="text-align: left; padding-left: 20px; font-weight: bold;"><?= htmlspecialchars($linha['materia']) ?></td>
                            <td><?= htmlspecialchars($linha['totalQuestoes']) ?></td>
                            <td style="color: green; font-weight: bold;"><?= htmlspecialchars($linha['totalAcertos']) ?></td>
                            <td style="color: red; font-weight: bold;"><?= htmlspecialchars($linha['totalErros']) ?></td>
                            <td>
                                <span style="font-weight: bold;">
                                    <?= htmlspecialchars($linha['percentualAcerto'] ?? 0) ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div> 
            <?php endif; ?>

            <h2 style="margin-top: 30px; color: var(--primary-color);">Resultados Detalhados por Aluno e Matéria</h2>

            <?php if (empty($relatorioDetalhadoMateria)): ?>
                <p style="text-align: center; margin-top: 20px;">Nenhuma tentativa de prova encontrada com os filtros aplicados.</p>
            <?php else: ?>
                
            <div class="table-wrapper">
                <table class="table-relatorio table-relatorio-detalhada">
                    <thead>
                        <tr>
                            <th>Matéria</th>
                            <th>Total Questões</th>
                            <th>Acertos</th>
                            <th>Erros</th>
                            <th>% Acerto</th>
                            <th>Data da Tentativa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $escolaAtual = '';
                        $alunoAtual = '';
                        
                        foreach ($relatorioDetalhadoMateria as $linha): 
                            
                            // 1. Destaca mudança de ESCOLA
                            if ($escolaAtual !== $linha['codigoEscola']):
                                $escolaAtual = $linha['codigoEscola'];
                                $alunoAtual = ''; 
                        ?>
                                <tr>
                                    <td colspan="7" class="escola-header">
                                        <div class="escola-header-agrupamento">
                                            CÓD. ESCOLA: <?= htmlspecialchars($escolaAtual) ?> 
                                            <span class="nome">(<?= getNomeEscola($escolaAtual, $escolaMap) ?>)</span>
                                        </div>
                                    </td>
                                </tr>
                        <?php 
                            endif;
                            
                            // 2. Destaca mudança de ALUNO (nome e data para o cabeçalho)
                            $alunoTentativaId = $linha['nomeCompleto'] . ' - ' . $linha['dataTentativa'];
                            if ($alunoAtual !== $alunoTentativaId):
                                $alunoAtual = $alunoTentativaId;
                        ?>
                                <tr>
                                    <td colspan="7" class="materia-header">
                                        ALUNO: <span style="font-weight: 600;"><?= htmlspecialchars($linha['nomeCompleto']) ?></span> (Data: <?= htmlspecialchars($linha['dataTentativa']) ?>)
                                    </td>
                                </tr>
                        <?php 
                            endif;
                        ?>
                        <tr class="aluno-row">
                            <td data-label="Matéria:"><?= htmlspecialchars($linha['materia']) ?></td>
                            <td data-label="Total Questões:"><?= htmlspecialchars($linha['totalQuestoes']) ?></td>
                            <td data-label="Acertos:" style="color: green;"><?= htmlspecialchars($linha['acertos']) ?></td>
                            <td data-label="Erros:" style="color: red;"><?= htmlspecialchars($linha['erros']) ?></td>
                            <td data-label="% Acerto:" style="font-weight: bold;"><?= htmlspecialchars($linha['percentualAcerto'] ?? 0) ?>%</td>
                            <td data-label="Data da Tentativa:" style="display:none;"><?= htmlspecialchars($linha['dataTentativa']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div> 
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados. (Relatório ID84)</p>
        <a href="../POLITICA.php">Política de privacidade</a>
    </footer>
</body>
</html>