<?php
// =========================================================================
// !!! CONFIGURAÇÕES INICIAIS E DEBUG !!!
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Garante que o usuário está logado
if (!isset($_SESSION['id'])) {
    die("Acesso negado. Sessão expirada.");
}

// Inclui o arquivo de conexão.
// !!! ATENÇÃO: VERIFIQUE SE ESTE CAMINHO ESTÁ CORRETO !!!
require_once '../conexao/conecta.php';

// =========================================================================
// 1. INCLUSÃO E CONFIGURAÇÃO DO TCPDF
// =========================================================================

// !!! ATENÇÃO: AJUSTE ESTE CAMINHO PARA A LOCALIZAÇÃO REAL DA SUA INSTALAÇÃO DO TCPDF !!!
require_once('../tcpdf/tcpdf.php'); 

// =========================================================================
// 2. LÓGICA DE FILTROS E BUSCA DE DADOS
// =========================================================================

// Mapeamento de Prova e Área de Conhecimento (ID86) - 1º ANO
// Mapeamento mantido igual ao ID85, com alteração do nome da variável
$mapaQuestoesID86 = [
    1 => 'MATEMÁTICA', 2 => 'MATEMÁTICA', 3 => 'MATEMÁTICA', 4 => 'MATEMÁTICA', 5 => 'MATEMÁTICA',
    6 => 'PORTUGUÊS', 7 => 'PORTUGUÊS', 8 => 'PORTUGUÊS', 9 => 'PORTUGUÊS', 10 => 'PORTUGUÊS',
    11 => 'INGLÊS', 12 => 'INGLÊS', 13 => 'INGLÊS', 14 => 'INGLÊS', 15 => 'INGLÊS',
    16 => 'FÍSICA', 17 => 'FÍSICA', 18 => 'FÍSICA', 19 => 'FÍSICA', 20 => 'FÍSICA',
    21 => 'QUÍMICA', 22 => 'QUÍMICA', 23 => 'QUÍMICA', 24 => 'QUÍMICA', 25 => 'QUÍMICA',
    26 => 'BIOLOGIA', 27 => 'BIOLOGIA', 28 => 'BIOLOGIA', 29 => 'BIOLOGIA', 30 => 'BIOLOGIA',
];

$idProvaAlvo = 86; // ID da nova prova
$nomeProvaAlvo = "ID 86 - Simulado 1º Ano"; // Título ajustado 

// Verifica permissão
$tipoUsuarioSessao = strtolower($_SESSION['tipoUsuario'] ?? '');
if ($tipoUsuarioSessao !== 'administrador' && $tipoUsuarioSessao !== 'diretor') {
    die("Acesso negado. Permissão insuficiente.");
}

$codigoEscolaUsuario = $_SESSION['codigoEscola'] ?? '';
$codigoEscolaFiltrada = ''; 

if ($tipoUsuarioSessao === 'administrador') {
    $codigoEscolaFiltrada = isset($_GET['codigoEscola']) ? trim($_GET['codigoEscola']) : '';
} else {
    $codigoEscolaFiltrada = $codigoEscolaUsuario;
    if (empty($codigoEscolaUsuario)) {
        die("Acesso negado: Código de escola não encontrado.");
    }
}

// Mapeamento de Escolas
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

// Subtítulo da exibição com o nome da escola
$nomeEscolaExibicao = empty($codigoEscolaFiltrada) ? "Todas as Escolas" : getNomeEscola($codigoEscolaFiltrada, $escolaMap);
$subtituloExibicao = "Escola: $nomeEscolaExibicao | Prova: $nomeProvaAlvo";


// Montagem Dinâmica da Cláusula WHERE e Parâmetros de Binding
$whereClauseBase = "t.idProva = ?";
$bindTypes = "i";
$bindParams = [$idProvaAlvo]; 

if (!empty($codigoEscolaFiltrada)) {
    $whereClauseBase .= " AND u.codigoEscola = ?";
    $bindTypes .= "s";
    $bindParams[] = $codigoEscolaFiltrada; 
} 

$bindParamsRefs = [$bindTypes];
foreach ($bindParams as $key => $value) {
    $bindParamsRefs[] = &$bindParams[$key];
}


// =========================================================================
// 3. CONSULTA DOS DADOS (O SQL PERMANECE O MESMO DA VERSÃO 14.0)
// =========================================================================

// Converte o mapa de questões PHP para uma estrutura SQL CASE WHEN
$sqlCaseMateria = "CASE ";
// ATENÇÃO: Usando $mapaQuestoesID86
foreach ($mapaQuestoesID86 as $questao => $materia) {
    $sqlCaseMateria .= "WHEN q.numQuestao = {$questao} THEN '{$materia}' ";
}
$sqlCaseMateria .= "ELSE 'Outros' END";

// --- Consulta Agregada por Matéria ---
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
if ($stmtAgregadoMateria === false) { die("Erro SQL Agregado: " . $conn->error); }
if (!empty($bindTypes)) { call_user_func_array([$stmtAgregadoMateria, 'bind_param'], $bindParamsRefs); }
$stmtAgregadoMateria->execute();
$resultAgregadoMateria = $stmtAgregadoMateria->get_result();

$relatorioAgregadoMateria = [];
if ($resultAgregadoMateria->num_rows > 0) {
    while ($row = $resultAgregadoMateria->fetch_assoc()) {
        $row['percentualAcerto'] = ($row['totalQuestoes'] > 0) ? round(($row['totalAcertos'] * 100.0) / $row['totalQuestoes'], 2) : 0;
        $relatorioAgregadoMateria[] = $row;
    }
}
$stmtAgregadoMateria->close();


// --- Consulta Detalhada por Aluno e Matéria ---
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
        t.id, /* ID DA TENTATIVA */
        t.dataTentativa,
        materia
    ORDER BY
        u.codigoEscola ASC, 
        u.nomeCompleto ASC,
        t.dataTentativa DESC,
        materia ASC;
";

$stmtDetalhadoMateria = $conn->prepare($sqlDetalhadoMateria);
if ($stmtDetalhadoMateria === false) { die("Erro SQL Detalhado: " . $conn->error); }
if (!empty($bindTypes)) { call_user_func_array([$stmtDetalhadoMateria, 'bind_param'], $bindParamsRefs); }
$stmtDetalhadoMateria->execute();
$resultDetalhadoMateria = $stmtDetalhadoMateria->get_result();

$relatorioDetalhadoMateria = [];
if ($resultDetalhadoMateria->num_rows > 0) {
    while ($row = $resultDetalhadoMateria->fetch_assoc()) {
        $row['percentualAcerto'] = ($row['totalQuestoes'] > 0) ? round(($row['acertos'] * 100.0) / $row['totalQuestoes'], 2) : 0;
        $relatorioDetalhadoMateria[] = $row;
    }
}
$stmtDetalhadoMateria->close();
$conn->close();

// =========================================================================
// 4. CLASSE DE EXTENSÃO DO TCPDF (Configuração do PDF)
// =========================================================================

class PDF extends TCPDF {
    // Cabeçalho personalizado
    public function Header() {
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 64, 133); // Azul Marinho
        // Título principal - ATUALIZADO PARA ID86 e 1º Ano
        $this->Cell(0, 15, 'Relatório de Desempenho por Matéria - 1º Ano (ID86)', 0, false, 'C', 0, '', 0, false, 'M', 'M'); 
        
        $this->SetFont('helvetica', 'I', 10);
        $this->SetTextColor(80, 80, 80); // Cinza Escuro
        global $subtituloExibicao; // Usando a variável global para o subtítulo
        
        // CORREÇÃO AQUI: Aumentar o Y da célula para garantir que não haja sobreposição com o título principal
        $this->SetY(20); 
        // USANDO Cell(0, ...) garante que o texto possa ocupar toda a largura e o corte não ocorra.
        $this->Cell(0, 10, $subtituloExibicao, 0, false, 'C', 0, '', 0, false, 'M', 'M');

        $this->Line(5, 30, 292, 30, ['width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [150, 150, 150]]);
        
        // Retorna o Y para a posição correta após o cabeçalho
        $this->SetY(35); 
    }

    // Rodapé personalizado
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(80, 80, 80); // Cinza Escuro
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . ' | Gerado em: ' . date('d/m/Y H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

// Cria novo PDF em formato paisagem (landscape)
$pdf = new PDF('L', 'mm', 'A4', true, 'UTF-8', false); 

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema Caminho do Saber');
// Título do arquivo ajustado
$pdf->SetTitle('Relatório de Desempenho por Matéria - ID86 - ' . $nomeEscolaExibicao); 
$pdf->SetSubject('Desempenho por Matéria');

// Define margens e remove o cabeçalho padrão
$pdf->SetMargins(5, 35, 5); 
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->SetFont('dejavusans', '', 10); // Fonte com bom suporte a acentos

// Adiciona a primeira página
$pdf->AddPage('L', 'A4'); 

// =========================================================================
// 5. GERAÇÃO DO CONTEÚDO HTML PARA O PDF (USANDO BORDER="1")
// =========================================================================

// --- Tabela 1: Desempenho Agregado ---

$pdf->SetFontSize(12);
$pdf->SetTextColor(0, 64, 133); 
$pdf->Write(0, 'Desempenho Agregado por Matéria (Prova ID86)', '', 0, 'L', true, 0, false, false, 0); // Título ajustado
$pdf->Ln(2);
$pdf->SetTextColor(0, 0, 0); 

$pdf->SetFontSize(9);

// Define larguras para Landscape A4 (total 287mm)
$wEscola = empty($codigoEscolaFiltrada) ? '15%' : '0%';
$wMateria = empty($codigoEscolaFiltrada) ? '25%' : '40%';
$wQtd = '15%';

$htmlTabela1 = '<table border="1" cellpadding="4">
    <tr style="background-color: #F0F8FF; font-weight: bold;">' .
        (empty($codigoEscolaFiltrada) ? '<th width="' . $wEscola . '" align="left">Cód. Escola</th>' : '') . '
        <th width="' . $wMateria . '" align="left">Matéria</th>
        <th width="' . $wQtd . '">Total Questões</th>
        <th width="' . $wQtd . '">Total Acertos</th>
        <th width="' . $wQtd . '">Total Erros</th>
        <th width="' . $wQtd . '">% de Acerto</th>
    </tr>
';

if (empty($relatorioAgregadoMateria)) {
    $colSpan = empty($codigoEscolaFiltrada) ? 6 : 5;
    $htmlTabela1 .= '<tr><td colspan="' . $colSpan . '" align="center">Nenhum dado agregado encontrado por matéria.</td></tr>';
} else {
    foreach ($relatorioAgregadoMateria as $linha) {
        $acerto = htmlspecialchars($linha['totalAcertos'] ?? 0);
        $erro = htmlspecialchars($linha['totalErros'] ?? 0);
        
        $htmlTabela1 .= '<tr nobr="true">'; 
        if (empty($codigoEscolaFiltrada)) {
            $htmlTabela1 .= '<td align="left" style="font-weight:bold; font-size: 0.8em;">' . htmlspecialchars($linha['codigoEscola']) . '</td>';
            $htmlTabela1 .= '<td align="left">' . htmlspecialchars($linha['materia']) . '</td>';
        } else {
            $htmlTabela1 .= '<td align="left">' . htmlspecialchars($linha['materia']) . '</td>';
        }
        
        $htmlTabela1 .= '<td>' . htmlspecialchars($linha['totalQuestoes'] ?? 0) . '</td>';
        $htmlTabela1 .= '<td><span style="color:#008000; font-weight:bold;">' . $acerto . '</span></td>'; 
        $htmlTabela1 .= '<td><span style="color:#FF0000; font-weight:bold;">' . $erro . '</span></td>'; 
        $htmlTabela1 .= '<td style="background-color: #E6F7FF; font-weight:bold;">' . htmlspecialchars($linha['percentualAcerto'] ?? 0) . '%</td>';
        $htmlTabela1 .= '</tr>';
    }
}
$htmlTabela1 .= '</table>';

$pdf->writeHTML($htmlTabela1, true, false, true, false, '');
$pdf->Ln(5);


// --- Tabela 2: Resultados Detalhados ---

if ($pdf->GetY() > 180) { 
    $pdf->AddPage('L', 'A4');
}

$pdf->SetFontSize(12);
$pdf->SetTextColor(0, 64, 133); 
$pdf->Write(0, 'Resultados Detalhados por Aluno e Matéria', '', 0, 'L', true, 0, false, false, 0);
$pdf->Ln(2);
$pdf->SetTextColor(0, 0, 0); 

$pdf->SetFontSize(9);

if (empty($relatorioDetalhadoMateria)) {
    $pdf->Write(0, 'Nenhum dado detalhado encontrado por aluno e matéria.', '', 0, 'L', true, 0, false, false, 0);
} else {
    
    $htmlTabela2 = '<table border="1" cellpadding="4" cellspacing="0">
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <th width="30%" align="left">Matéria</th>
            <th width="15%">Questões</th>
            <th width="15%">Acertos</th>
            <th width="15%">Erros</th>
            <th width="12.5%">% Acerto</th>
            <th width="12.5%">% Erro</th>
        </tr>
    ';

    $escolaAtual = '';
    $alunoTentativaAtual = '';

    foreach ($relatorioDetalhadoMateria as $linha) {
        
        // 1. Mudança de ESCOLA (Agrupamento maior)
        if ($escolaAtual !== $linha['codigoEscola']) {
            $escolaAtual = $linha['codigoEscola'];
            $alunoTentativaAtual = ''; 
            
            // Linha de cabeçalho da Escola
            $htmlTabela2 .= '<tr nobr="true"><td colspan="6" style="background-color: #004085; color: #FFFFFF; font-weight:bold; font-size: 1.1em;" align="left">ESCOLA: ' . htmlspecialchars($escolaAtual) . ' - (' . getNomeEscola($escolaAtual, $escolaMap) . ')</td></tr>';
        }

        // 2. Mudança de Aluno/Tentativa (Agrupamento intermediário)
        $alunoTentativaID = $linha['nomeCompleto'] . ' - ' . $linha['dataTentativa'];
        if ($alunoTentativaAtual !== $alunoTentativaID) {
            $alunoTentativaAtual = $alunoTentativaID;
            
            // Linha de cabeçalho do Aluno
            $htmlTabela2 .= '<tr nobr="true"><td colspan="6" style="background-color: #F7F9FB; font-weight:bold; color:#0056b3; font-size: 1em;" align="left">ALUNO: ' . htmlspecialchars($linha['nomeCompleto']) . ' <span style="font-weight:normal; color:#6c757d; font-size: 0.8em;">(Tentativa: ' . htmlspecialchars($linha['dataTentativa']) . ')</span></td></tr>';
        }

        $acerto = htmlspecialchars($linha['acertos'] ?? 0);
        $erro = htmlspecialchars($linha['erros'] ?? 0);
        $percentualErro = 100.0 - $linha['percentualAcerto'];
        
        // Linha de dados por Matéria
        $htmlTabela2 .= '<tr nobr="true">';
        $htmlTabela2 .= '<td align="left" style="font-weight:normal; padding-left: 10px;">' . htmlspecialchars($linha['materia']) . '</td>';
        $htmlTabela2 .= '<td>' . htmlspecialchars($linha['totalQuestoes'] ?? 0) . '</td>';
        $htmlTabela2 .= '<td><span style="color:#008000; font-weight:bold;">' . $acerto . '</span></td>'; 
        $htmlTabela2 .= '<td><span style="color:#FF0000; font-weight:bold;">' . $erro . '</span></td>'; 
        $htmlTabela2 .= '<td>' . htmlspecialchars($linha['percentualAcerto'] ?? 0) . '%</td>';
        $htmlTabela2 .= '<td>' . number_format($percentualErro, 2) . '%</td>';
        $htmlTabela2 .= '</tr>';
    }
    $htmlTabela2 .= '</table>';
    
    $pdf->writeHTML($htmlTabela2, true, false, true, false, '');
}

// Finaliza e força o download
$filename = "Relatorio_Desempenho_Materia_ID86_" . ($codigoEscolaFiltrada ?: "Geral") . "_" . date("Ymd") . ".pdf";
$pdf->Output($filename, 'D'); 

exit;
