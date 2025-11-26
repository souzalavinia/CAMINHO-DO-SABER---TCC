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
// 1. CONFIGURAÇÃO DE PERMISSÃO E BUSCA DO CÓDIGO DA ESCOLA (REUTILIZADA)
// =========================================================================

$tipoUsuarioSessao = strtolower($_SESSION['tipoUsuario'] ?? '');
if ( $tipoUsuarioSessao !== 'diretor' && $tipoUsuarioSessao !== 'administrador') {
    die("Acesso negado: Você não tem permissão para baixar este relatório.");
}

$codigoEscolaDiretor = $_SESSION['codigoEscola'] ?? '';

if (empty($codigoEscolaDiretor)) {
    die("Acesso negado: Código de escola não encontrado.");
}

// Inclui a conexão (mantendo o caminho original)
require_once '../conexao/conecta.php'; 


// =========================================================================
// 2. CONFIGURAÇÃO DO FILTRO DE PROVAS (REUTILIZADA)
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


// Define a cláusula WHERE base e o array de binding para o Prepared Statement
$whereClauseBase = "u.codigoEscola = ?";
$bindTypes = "s";
$bindParams = [&$codigoEscolaDiretor];

if ($idProvaFiltrada > 0) {
    $whereClauseBase .= " AND t.idProva = ?";
    $bindTypes .= "i";
    $bindParams[] = &$idProvaFiltrada;
}

// =========================================================================
// 3. CONSULTA PARA O RELATÓRIO AGREGADO (CORREÇÃO DE ERROS)
// =========================================================================
$sqlAgregado = "
    SELECT
        p.nome AS nomeProva,
        SUM(t.acertos) AS totalAcertos,
        COALESCE(SUM(t.erros), 0) AS totalErros, -- <== COALESCE GARANTE 0 AO INVÉS DE NULL
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
        p.nome, p.id
    ORDER BY
        p.nome ASC;
";

$stmtAgregado = $conn->prepare($sqlAgregado);
if ($stmtAgregado === false) { die("Erro na preparação da consulta agregada: " . $conn->error); }
call_user_func_array([$stmtAgregado, 'bind_param'], array_merge([$bindTypes], $bindParams));
$stmtAgregado->execute();
$resultAgregado = $stmtAgregado->get_result();
$relatorioAgregado = $resultAgregado->fetch_all(MYSQLI_ASSOC);
$stmtAgregado->close();


// =========================================================================
// 4. CONSULTA PARA O RELATÓRIO DETALHADO (PUXANDO A STRING CRUA - d/m/Y)
// =========================================================================
$sqlDetalhado = "
    SELECT
        u.nomeCompleto,
        p.nome AS nomeProva,
        t.acertos,
        t.erros,
        (t.acertos + t.erros) AS totalQuestoes,
        ROUND((t.acertos * 100.0) / NULLIF((t.acertos + t.erros), 0), 2) AS percentualAcerto,
        t.dataTentativa  -- PEGANDO A STRING CRUA (dd/mm/aaaa)
    FROM 
        tb_tentativas t
    JOIN 
        tb_usuario u ON t.idUsuario = u.id
    JOIN 
        tb_prova p ON t.idProva = p.id
    WHERE
        " . $whereClauseBase . "
    ORDER BY
        p.nome ASC,
        u.nomeCompleto ASC,
        t.dataTentativa DESC;
";

$stmtDetalhado = $conn->prepare($sqlDetalhado);
if ($stmtDetalhado === false) { die("Erro na preparação da consulta detalhada: " . $conn->error); }
call_user_func_array([$stmtDetalhado, 'bind_param'], array_merge([$bindTypes], $bindParams));
$stmtDetalhado->execute();
$resultDetalhado = $stmtDetalhado->get_result();
$relatorioDetalhado = $resultDetalhado->fetch_all(MYSQLI_ASSOC);
$stmtDetalhado->close();

$conn->close();

// =========================================================================
// 5. GERAÇÃO DO PDF USANDO TCPDF
// =========================================================================

// Cria um novo PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Informações do Documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema Caminho do Saber');
$pdf->SetTitle('Relatório de Desempenho Escolar');
$pdf->SetSubject('Relatório');

// Define cabeçalho e rodapé padrão (opcional)
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'RELATÓRIO DE DESEMPENHO ESCOLAR', 'Escola: ' . $codigoEscolaDiretor);
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
$pdf->Cell(0, 10, 'Relatório de Desempenho da Escola', 0, 1, 'C', 1);
$pdf->Ln(5);

// Informações de filtro
$pdf->SetFontSize(10);
$pdf->MultiCell(0, 6, "Escola: " . htmlspecialchars($codigoEscolaDiretor) . "\nProva Filtrada: " . htmlspecialchars($nomeProvaFiltrada) . "\nData de Geração: " . date('d/m/Y H:i:s'), 0, 'L', 0, 1, '', '', true, 0, false, true, 0);
$pdf->Ln(5);

// =========================================================================
// TABELA AGREGADA (CORREÇÃO APLICADA AQUI)
// =========================================================================

$pdf->SetFontSize(12);
$pdf->Write(0, 'Desempenho Agregado por Prova', '', 0, 'L', true, 0, false, false, 0);
$pdf->Ln(2);

$pdf->SetFontSize(9);
// Estrutura do cabeçalho fora do corpo para melhor controle de quebra
$html_agregado_header = '<table border="1" cellpadding="4">
    <tr style="background-color: #f0f0f0;">
        <th width="40%" align="left">Nome da Prova</th>
        <th width="15%">Total Questões</th>
        <th width="15%">Total Acertos</th>
        <th width="15%">Total Erros</th>
        <th width="15%">% de Acerto</th>
    </tr>
';
$html_agregado = $html_agregado_header;

if (empty($relatorioAgregado)) {
    $html_agregado .= '<tr><td colspan="5" align="center">Nenhuma tentativa agregada encontrada.</td></tr>';
} else {
    foreach ($relatorioAgregado as $linha) {
        $acerto = htmlspecialchars($linha['totalAcertos'] ?? 0);
        $erro = htmlspecialchars($linha['totalErros'] ?? 0); // <== Proteção PHP mantida
        
        // Adicionando nobr="true" para garantir que a linha da tabela não seja quebrada
        $html_agregado .= '<tr nobr="true">'; 
        $html_agregado .= '<td align="left">' . htmlspecialchars($linha['nomeProva']) . '</td>';
        $html_agregado .= '<td>' . htmlspecialchars($linha['totalQuestoes'] ?? 0) . '</td>';
        $html_agregado .= '<td><span style="color:#008000; font-weight:bold;">' . $acerto . '</span></td>'; // Cor verde
        $html_agregado .= '<td><span style="color:#FF0000; font-weight:bold;">' . $erro . '</span></td>'; // Cor vermelha
        $html_agregado .= '<td>' . htmlspecialchars($linha['percentualAcerto'] ?? 0) . '%</td>';
        $html_agregado .= '</tr>';
    }
}
$html_agregado .= '</table>';

$pdf->writeHTML($html_agregado, true, false, true, false, '');
$pdf->Ln(5);

// =========================================================================
// TABELA DETALHADA
// =========================================================================

$pdf->SetFontSize(12);
$pdf->Write(0, ' Resultados Detalhados por Aluno', '', 0, 'L', true, 0, false, false, 0);
$pdf->Ln(2);

$pdf->SetFontSize(9);

if (empty($relatorioDetalhado)) {
    $pdf->Write(0, 'Nenhuma tentativa detalhada encontrada.', '', 0, 'L', true, 0, false, false, 0);
} else {
    // Cabeçalho da tabela detalhada
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

    $provaAtual = '';
    foreach ($relatorioDetalhado as $linha) {
        if ($provaAtual !== $linha['nomeProva']) {
            $provaAtual = $linha['nomeProva'];
            
            // Linha de cabeçalho da prova: Usamos nobr="true"
            $html_detalhado .= '<tr nobr="true"><td colspan="6" style="background-color: #e9e9e9; font-weight:bold;" align="center">PROVA: ' . htmlspecialchars($provaAtual) . '</td></tr>';
        }

        $acerto = htmlspecialchars($linha['acertos'] ?? 0);
        $erro = htmlspecialchars($linha['erros'] ?? 0);
        
        // ** FORMATANDO A DATA NO PHP (dd/mm/aaaa) **
        $dataCrua = trim($linha['dataTentativa'] ?? ''); 
        $dataFormatada = '';

        // Tenta criar um objeto DateTime ESPECIFICANDO o formato de entrada 'd/m/Y'
        if (!empty($dataCrua)) {
            // Formato de entrada é 'd/m/Y' (dd/mm/aaaa)
            $dataObj = DateTime::createFromFormat('d/m/Y', $dataCrua);
            
            // Verifica se a criação foi bem-sucedida e se o objeto não gerou warnings/erros
            if ($dataObj !== false && $dataObj->format('d/m/Y') === $dataCrua) {
                $dataFormatada = $dataObj->format('d/m/Y'); 
            }
        }
        $dataFormatada = htmlspecialchars($dataFormatada);
        // ** FIM: FORMATANDO NO PHP **
        
        
        // Linha de dados do aluno: Usa nobr="true"
        $html_detalhado .= '<tr nobr="true">';
        $html_detalhado .= '<td align="left">' . htmlspecialchars($linha['nomeCompleto']) . '</td>';
        $html_detalhado .= '<td>' . $dataFormatada . '</td>'; // Célula de data corrigida
        $html_detalhado .= '<td>' . htmlspecialchars($linha['totalQuestoes'] ?? 0) . '</td>';
        $html_detalhado .= '<td><span style="color:#008000; font-weight:bold;">' . $acerto . '</span></td>'; // Cor verde
        $html_detalhado .= '<td><span style="color:#FF0000; font-weight:bold;">' . $erro . '</span></td>'; // Cor vermelha
        $html_detalhado .= '<td>' . htmlspecialchars($linha['percentualAcerto'] ?? 0) . '%</td>';
        $html_detalhado .= '</tr>';
    }
    $html_detalhado .= '</table>';
    
    // Adiciona o HTML ao PDF
    $pdf->writeHTML($html_detalhado, true, false, true, false, '');
}

// Finaliza e força o download
$filename = "Relatorio_Escola_" . $codigoEscolaDiretor . "_" . date('Ymd_His') . ".pdf";
$pdf->Output($filename, 'D'); // 'D' força o download
exit;