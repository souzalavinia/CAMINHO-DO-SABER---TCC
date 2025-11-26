<?php
session_start();

// Garante que o usuário está logado
if (!isset($_SESSION['id'])) {
    // Redireciona de volta para o login ou mostra erro
    die("Acesso negado. Por favor, faça login.");
}

// =========================================================================
// INCLUSÃO DO DOMPDF (INSTALADO VIA COMPOSER)
// O caminho deve ser ajustado se o seu vendor não estiver um nível acima
// da pasta do relatorio.
// =========================================================================
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;


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

require_once '../conexao/conecta.php'; // Inclui a conexão


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
// 3. CONSULTA PARA O RELATÓRIO AGREGADO (REUTILIZADA)
// =========================================================================
$sqlAgregado = "
    SELECT
        p.nome AS nomeProva,
        SUM(t.acertos) AS totalAcertos,
        SUM(t.erros) AS totalErros,
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
// 4. CONSULTA PARA O RELATÓRIO DETALHADO (REUTILIZADA)
// =========================================================================
$sqlDetalhado = "
    SELECT
        u.nomeCompleto,
        p.nome AS nomeProva,
        t.acertos,
        t.erros,
        (t.acertos + t.erros) AS totalQuestoes,
        ROUND((t.acertos * 100.0) / NULLIF((t.acertos + t.erros), 0), 2) AS percentualAcerto,
        DATE_FORMAT(t.dataTentativa, '%d/%m/%Y %H:%i') AS dataTentativaFormatada
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
// 5. GERAÇÃO DO HTML PARA O PDF
// =========================================================================

$html = '
    <html>
    <head>
        <title>Relatório PDF</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; margin: 30px; }
            h1 { color: #007bff; font-size: 18pt; margin-bottom: 5px; }
            h2 { color: #333; font-size: 14pt; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
            p { margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: center; }
            th { background-color: #f0f0f0; font-weight: bold; }
            .agregado td:first-child { text-align: left; }
            .detalhado th:first-child, .detalhado td:first-child { text-align: left; width: 30%; }
            .detalhado tr.prova-header td { background-color: #e9e9e9; font-weight: bold; text-align: center; font-size: 11pt; padding: 8px; }
            .cor-verde { color: green; font-weight: bold; }
            .cor-vermelha { color: red; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>Relatório de Desempenho da Escola</h1>
        <p><strong>Escola:</strong> ' . htmlspecialchars($codigoEscolaDiretor) . '</p>
        <p><strong>Prova Filtrada:</strong> ' . htmlspecialchars($nomeProvaFiltrada) . '</p>
        <p><strong>Data de Geração:</strong> ' . date('d/m/Y H:i:s') . '</p>

        <h2>Desempenho Agregado por Prova</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 40%; text-align: left;">Nome da Prova</th>
                    <th style="width: 15%;">Total Questões</th>
                    <th style="width: 15%;">Total Acertos</th>
                    <th style="width: 15%;">Total Erros</th>
                    <th style="width: 15%;">% de Acerto</th>
                </tr>
            </thead>
            <tbody>';
            
            if (empty($relatorioAgregado)) {
                $html .= '<tr><td colspan="5">Nenhuma tentativa agregada encontrada.</td></tr>';
            } else {
                foreach ($relatorioAgregado as $linha) {
                    $html .= '<tr>';
                    $html .= '<td style="text-align: left;">' . htmlspecialchars($linha['nomeProva']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($linha['totalQuestoes']) . '</td>';
                    $html .= '<td class="cor-verde">' . htmlspecialchars($linha['totalAcertos']) . '</td>';
                    $html .= '<td class="cor-vermelha">' . htmlspecialchars($linha['totalErros']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($linha['percentualAcerto'] ?? 0) . '%</td>';
                    $html .= '</tr>';
                }
            }

        $html .= '</tbody></table>';

        $html .= '<h2>Resultados Detalhados por Aluno</h2>';
        
        if (empty($relatorioDetalhado)) {
             $html .= '<p>Nenhuma tentativa detalhada encontrada.</p>';
        } else {
            $html .= '<table class="detalhado">
                <thead>
                    <tr>
                        <th style="width: 30%; text-align: left;">Nome do Aluno</th>
                        <th style="width: 18%;">Data da Tentativa</th>
                        <th style="width: 13%;">Questões</th>
                        <th style="width: 13%;">Acertos</th>
                        <th style="width: 13%;">Erros</th>
                        <th style="width: 13%;">% Acerto</th>
                    </tr>
                </thead>
                <tbody>';

                $provaAtual = '';
                foreach ($relatorioDetalhado as $linha) {
                    if ($provaAtual !== $linha['nomeProva']) {
                        $provaAtual = $linha['nomeProva'];
                        $html .= '<tr><td colspan="6" class="prova-header">PROVA: ' . htmlspecialchars($provaAtual) . '</td></tr>';
                    }
                    
                    $html .= '<tr>';
                    $html .= '<td style="text-align: left;">' . htmlspecialchars($linha['nomeCompleto']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($linha['dataTentativaFormatada']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($linha['totalQuestoes']) . '</td>';
                    $html .= '<td class="cor-verde">' . htmlspecialchars($linha['acertos']) . '</td>';
                    $html .= '<td class="cor-vermelha">' . htmlspecialchars($linha['erros']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($linha['percentualAcerto'] ?? 0) . '%</td>';
                    $html .= '</tr>';
                }

            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';


// =========================================================================
// 6. GERAÇÃO DO PDF USANDO DOMPDF
// =========================================================================

$options = new Options();
// Habilitar esta opção para que o Dompdf consiga carregar a fonte Dejavu (necessário para acentos)
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans'); // Define a fonte para suporte a UTF-8

$dompdf = new Dompdf($options);

// Carrega o HTML
$dompdf->loadHtml($html);

// Configura o tamanho e orientação do papel
$dompdf->setPaper('A4', 'portrait');

// Renderiza o HTML para PDF
$dompdf->render();

// Envia o PDF gerado para o navegador
$filename = "Relatorio_Escola_" . $codigoEscolaDiretor . "_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]); // true = forçar download
exit(0);

// FIM do gerar_pdf_relatorio.php