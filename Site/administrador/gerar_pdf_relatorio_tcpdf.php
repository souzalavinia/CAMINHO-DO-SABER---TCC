<?php
session_start();

// Garante que o usuário está logado
if (!isset($_SESSION['id'])) {
    die("Acesso negado. Por favor, faça login.");
}

// =========================================================================
// INCLUSÃO DO TCPDF (INSTALAÇÃO MANUAL)
// *************************************************************************
require_once 'tcpdf/tcpdf.php'; 
// *************************************************************************


// =========================================================================
// 1. CONFIGURAÇÃO DE PERMISSÃO E CONEXÃO
// =========================================================================

$tipoUsuarioSessao = strtolower($_SESSION['tipoUsuario'] ?? '');

// Verifica se é administrador (a única permissão para baixar relatórios completos)
if ($tipoUsuarioSessao !== 'administrador') {
    die("Acesso negado: Você não tem permissão para baixar este relatório.");
}

// Inclui a conexão (mantendo o caminho original)
require_once '../conexao/conecta.php'; 


// =========================================================================
// 1.1 BUSCA E MAPEAMENTO DOS NOMES DAS ESCOLAS
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
    return $map[$codigo] ?? 'Nome não encontrado (Cód: ' . $codigo . ')';
}

// =========================================================================
// 1.2 APLICAÇÃO DOS FILTROS
// =========================================================================

// Admin pode filtrar por uma escola específica ou ver todas.
// O código da escola VAZIO significa "Todas as Escolas"
$codigoEscolaFiltrada = isset($_GET['codigoEscola']) ? trim($_GET['codigoEscola']) : '';

// Variável para exibir no PDF (AGORA MOSTRA O NOME COMPLETO OU A VISÃO GERAL)
$nomeEscolaFiltrada = empty($codigoEscolaFiltrada) 
    ? "Todas as Escolas (Visão Geral)" 
    : getNomeEscola($codigoEscolaFiltrada, $escolaMap);


// =========================================================================
// 2. CONFIGURAÇÃO DO FILTRO DE PROVAS E CLÁUSULA WHERE (MODIFICADO)
// =========================================================================

// Configuração do filtro para o relatório
$idProvaFiltrada = isset($_GET['idProva']) ? intval($_GET['idProva']) : 0;
$nomeProvaFiltrada = "Todas as Provas";

// Busca o nome da prova filtrada (se houver)
$sqlProvas = "SELECT id, nome FROM tb_prova WHERE id = ?";
$stmtProva = $conn->prepare($sqlProvas);
if ($idProvaFiltrada > 0 && $stmtProva) {
    $stmtProva->bind_param("i", $idProvaFiltrada);
    $stmtProva->execute();
    $resultProva = $stmtProva->get_result();
    if ($rowProva = $resultProva->fetch_assoc()) {
        $nomeProvaFiltrada = $rowProva['nome'];
    }
    $stmtProva->close();
}


// =========================================================================
// 2.1 Montagem Dinâmica da Cláusula WHERE e Parâmetros de Binding
// =========================================================================

$whereClauseBase = "1=1"; // Começa com uma condição verdadeira
$bindTypes = "";
$bindParams = [];

// 1. FILTRO DE ESCOLA
if (!empty($codigoEscolaFiltrada)) {
    $whereClauseBase .= " AND u.codigoEscola = ?";
    $bindTypes .= "s";
    // Nota: É necessário passar a referência para call_user_func_array
    $bindParams[] = &$codigoEscolaFiltrada; 
} 

// 2. FILTRO DE PROVA
if ($idProvaFiltrada > 0) {
    $whereClauseBase .= " AND t.idProva = ?";
    $bindTypes .= "i";
    // Nota: É necessário passar a referência para call_user_func_array
    $bindParams[] = &$idProvaFiltrada; 
}

// Cria a lista de referências para o bind_param.
$bindParamsRefs = [$bindTypes]; // Começa com a string de tipos
foreach ($bindParams as $key => $value) {
    $bindParamsRefs[] = &$bindParams[$key];
}


// =========================================================================
// 3. CONSULTA PARA O RELATÓRIO AGREGADO (AGORA COM NOME DA ESCOLA NO PHP)
// =========================================================================
$sqlAgregado = "
    SELECT
        " . (empty($codigoEscolaFiltrada) ? "u.codigoEscola AS codigoEscola, " : "") . "
        p.nome AS nomeProva,
        SUM(t.acertos) AS totalAcertos,
        COALESCE(SUM(t.erros), 0) AS totalErros,
        (SUM(t.acertos) + SUM(t.erros)) AS totalQuestoes,
        ROUND((SUM(t.acertos) * 100.0) / NULLIF((SUM(t.acertos) + SUM(t.erros)), 0), 2) AS percentualAcerto
    FROM 
        tb_tentativas t
    JOIN 
        tb_usuario u ON t.idUsuario = u.id
    JOIN 
        tb_prova p ON t.idProva = p.id
    WHERE
        " . $whereClauseBase . "
    GROUP BY
        " . (empty($codigoEscolaFiltrada) ? "u.codigoEscola, " : "") . "
        p.nome, p.id
    ORDER BY
        " . (empty($codigoEscolaFiltrada) ? "u.codigoEscola ASC, " : "") . "
        p.nome ASC;
";

$stmtAgregado = $conn->prepare($sqlAgregado);
if ($stmtAgregado === false) { die("Erro na preparação da consulta agregada: " . $conn->error); }

// Faz o bind dinamicamente
if (!empty($bindTypes)) {
    call_user_func_array([$stmtAgregado, 'bind_param'], $bindParamsRefs);
}
$stmtAgregado->execute();
$resultAgregado = $stmtAgregado->get_result();
$relatorioAgregado = $resultAgregado->fetch_all(MYSQLI_ASSOC);
$stmtAgregado->close();


// =========================================================================
// 4. CONSULTA PARA O RELATÓRIO DETALHADO (AGORA COM NOME DA ESCOLA NO PHP)
// =========================================================================
$sqlDetalhado = "
    SELECT
        u.nomeCompleto,
        u.codigoEscola, " . // Continua buscando o código para agrupamento
        "p.nome AS nomeProva,
        t.acertos,
        t.erros,
        (t.acertos + t.erros) AS totalQuestoes,
        ROUND((t.acertos * 100.0) / NULLIF((t.acertos + t.erros), 0), 2) AS percentualAcerto,
        t.dataTentativa 
    FROM 
        tb_tentativas t
    JOIN 
        tb_usuario u ON t.idUsuario = u.id
    JOIN 
        tb_prova p ON t.idProva = p.id
    WHERE
        " . $whereClauseBase . "
    ORDER BY
        u.codigoEscola ASC, " . // Ordena por escola
        "p.nome ASC,
        u.nomeCompleto ASC,
        t.dataTentativa DESC;
";

$stmtDetalhado = $conn->prepare($sqlDetalhado);
if ($stmtDetalhado === false) { die("Erro na preparação da consulta detalhada: " . $conn->error); }

// Faz o bind dinamicamente (reutiliza os mesmos parâmetros)
if (!empty($bindTypes)) {
    call_user_func_array([$stmtDetalhado, 'bind_param'], $bindParamsRefs);
}
$stmtDetalhado->execute();
$resultDetalhado = $stmtDetalhado->get_result();
$relatorioDetalhado = $resultDetalhado->fetch_all(MYSQLI_ASSOC);
$stmtDetalhado->close();

$conn->close();

// =========================================================================
// 5. GERAÇÃO DO PDF USANDO TCPDF (MODIFICADO)
// =========================================================================

// Cria um novo PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Informações do Documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema Caminho do Saber');
$pdf->SetTitle('Relatório de Desempenho Escolar');
$pdf->SetSubject('Relatório');

// Define cabeçalho e rodapé padrão
// O NOME DA ESCOLA AGORA ESTÁ FORMATADO CORRETAMENTE
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'RELATÓRIO DE DESEMPENHO ESCOLAR', 'Escola: ' . $nomeEscolaFiltrada);
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Define a fonte padrão (usando Dejavusans para suporte a UTF-8/acentos)
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetFont('dejavusans', '', 10);

// Define margens
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Quebra de página automática
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Adiciona a primeira página
$pdf->AddPage('P', 'A4'); // 'P' para Retrato (Portrait)

// Título principal
$pdf->SetFontSize(16);
$pdf->SetFillColor(240, 240, 255);
$pdf->Cell(0, 10, 'Relatório de Desempenho Escolar', 0, 1, 'C', 1);
$pdf->Ln(5);

// Informações de filtro
$pdf->SetFontSize(10);
// O NOME DA ESCOLA AQUI TAMBÉM ESTÁ FORMATADO CORRETAMENTE
$pdf->MultiCell(0, 6, "Escola: " . htmlspecialchars($nomeEscolaFiltrada) . "\nProva Filtrada: " . htmlspecialchars($nomeProvaFiltrada) . "\nData de Geração: " . date('d/m/Y H:i:s'), 0, 'L', 0, 1, '', '', true, 0, false, true, 0);
$pdf->Ln(5);

// =========================================================================
// TABELA AGREGADA (AGORA MOSTRA O NOME DA ESCOLA)
// =========================================================================

$pdf->SetFontSize(12);
$pdf->Write(0, 'Desempenho Agregado por Prova', '', 0, 'L', true, 0, false, false, 0);
$pdf->Ln(2);

$pdf->SetFontSize(9);
// Define larguras
$wEscola = empty($codigoEscolaFiltrada) ? '20%' : '0%';
$wProva = empty($codigoEscolaFiltrada) ? '30%' : '40%';
$wQtd = '15%';

// Estrutura do cabeçalho
$html_agregado_header = '<table border="1" cellpadding="4">
    <tr style="background-color: #f0f0f0;">' .
        (empty($codigoEscolaFiltrada) ? '<th width="' . $wEscola . '" align="left">Nome da Escola</th>' : '') . '
        <th width="' . $wProva . '" align="left">Nome da Prova</th>
        <th width="' . $wQtd . '">Total Questões</th>
        <th width="' . $wQtd . '">Total Acertos</th>
        <th width="' . $wQtd . '">Total Erros</th>
        <th width="' . $wQtd . '">% de Acerto</th>
    </tr>
';
$html_agregado = $html_agregado_header;

if (empty($relatorioAgregado)) {
    $colSpan = empty($codigoEscolaFiltrada) ? 6 : 5;
    $html_agregado .= '<tr><td colspan="' . $colSpan . '" align="center">Nenhuma tentativa agregada encontrada.</td></tr>';
} else {
    foreach ($relatorioAgregado as $linha) {
        $acerto = htmlspecialchars($linha['totalAcertos'] ?? 0);
        $erro = htmlspecialchars($linha['totalErros'] ?? 0); 
        
        $html_agregado .= '<tr nobr="true">'; 
        if (empty($codigoEscolaFiltrada)) {
            // MOSTRANDO O NOME DA ESCOLA
            $nome = getNomeEscola($linha['codigoEscola'], $escolaMap);
            $html_agregado .= '<td align="left" style="font-weight:bold;">' . $nome . '</td>';
        }
        $html_agregado .= '<td align="left">' . htmlspecialchars($linha['nomeProva']) . '</td>';
        $html_agregado .= '<td>' . htmlspecialchars($linha['totalQuestoes'] ?? 0) . '</td>';
        $html_agregado .= '<td><span style="color:#008000; font-weight:bold;">' . $acerto . '</span></td>'; 
        $html_agregado .= '<td><span style="color:#FF0000; font-weight:bold;">' . $erro . '</span></td>'; 
        $html_agregado .= '<td>' . htmlspecialchars($linha['percentualAcerto'] ?? 0) . '%</td>';
        $html_agregado .= '</tr>';
    }
}
$html_agregado .= '</table>';

$pdf->writeHTML($html_agregado, true, false, true, false, '');
$pdf->Ln(5);

// =========================================================================
// TABELA DETALHADA (AGORA MOSTRA NOME DA ESCOLA NO AGRUPAMENTO)
// =========================================================================

$pdf->SetFontSize(12);
$pdf->Write(0, ' Resultados Detalhados por Aluno', '', 0, 'L', true, 0, false, false, 0);
$pdf->Ln(2);

$pdf->SetFontSize(9);

if (empty($relatorioDetalhado)) {
    $pdf->Write(0, 'Nenhuma tentativa detalhada encontrada.', '', 0, 'L', true, 0, false, false, 0);
} else {
    // Cabeçalho da tabela detalhada (colunas e larguras)
    $html_detalhado_header = '<table border="1" cellpadding="4">
        <tr style="background-color: #f0f0f0;">
            <th width="30%" align="left">Nome do Aluno</th>
            <th width="18%">Data da Tentativa</th>
            <th width="13%">Questões</th>
            <th width="13%">Acertos</th>
            <th width="13%">Erros</th>
            <th width="13%">% Acerto</th>
        </tr>
    ';
    $html_detalhado = $html_detalhado_header;

    $escolaAtual = ''; // Novo marcador de escola
    $provaAtual = '';

    foreach ($relatorioDetalhado as $linha) {
        
        // 1. Mudança de ESCOLA
        if ($escolaAtual !== $linha['codigoEscola']) {
            $escolaAtual = $linha['codigoEscola'];
            $provaAtual = ''; // Reseta a prova ao mudar a escola
            
            // MOSTRANDO O NOME DA ESCOLA
            $nomeExibicao = getNomeEscola($escolaAtual, $escolaMap);
            $html_detalhado .= '<tr nobr="true"><td colspan="6" style="background-color: #d0e0ff; font-weight:bold;" align="center">ESCOLA: ' . $nomeExibicao . '</td></tr>';
        }

        // 2. Mudança de PROVA
        if ($provaAtual !== $linha['nomeProva']) {
            $provaAtual = $linha['nomeProva'];
            
            // Linha de cabeçalho da prova: Usamos nobr="true"
            $html_detalhado .= '<tr nobr="true"><td colspan="6" style="background-color: #e9e9e9; font-weight:bold;" align="center">PROVA: ' . htmlspecialchars($provaAtual) . '</td></tr>';
        }

        $acerto = htmlspecialchars($linha['acertos'] ?? 0);
        $erro = htmlspecialchars($linha['erros'] ?? 0);
        
        // ** FORMATANDO A DATA NO PHP (dd/mm/aaaa) **
        $dataCrua = trim($linha['dataTentativa'] ?? ''); 
        $dataFormatada = $dataCrua; // Mantém a string original como fallback

        if (!empty($dataCrua)) {
            // Assume que a data no DB está no formato d/m/Y
            $dataObj = DateTime::createFromFormat('d/m/Y', $dataCrua);
            
            // Tenta formatar se for um objeto válido
            if ($dataObj !== false && $dataObj->format('d/m/Y') === $dataCrua) {
                $dataFormatada = $dataObj->format('d/m/Y'); 
            }
        }
        $dataFormatada = htmlspecialchars($dataFormatada);
        // ** FIM: FORMATANDO NO PHP **
        
        
        // Linha de dados do aluno: Usa nobr="true"
        $html_detalhado .= '<tr nobr="true">';
        $html_detalhado .= '<td align="left">' . htmlspecialchars($linha['nomeCompleto']) . '</td>';
        $html_detalhado .= '<td>' . $dataFormatada . '</td>'; 
        $html_detalhado .= '<td>' . htmlspecialchars($linha['totalQuestoes'] ?? 0) . '</td>';
        $html_detalhado .= '<td><span style="color:#008000; font-weight:bold;">' . $acerto . '</span></td>'; 
        $html_detalhado .= '<td><span style="color:#FF0000; font-weight:bold;">' . $erro . '</span></td>'; 
        $html_detalhado .= '<td>' . htmlspecialchars($linha['percentualAcerto'] ?? 0) . '%</td>';
        $html_detalhado .= '</tr>';
    }
    $html_detalhado .= '</table>';
    
    // Adiciona o HTML ao PDF
    $pdf->writeHTML($html_detalhado, true, false, true, false, '');
}

// Finaliza e força o download
$filename = "Relatorio_Escola_" . (empty($codigoEscolaFiltrada) ? 'Geral' : $codigoEscolaFiltrada) . "_" . date('Ymd_His') . ".pdf";
$pdf->Output($filename, 'D'); // 'D' força o download
exit;
