<?php
// gerar_pdf_prova.php
session_start();

// Configurações para evitar erros com imagens grandes
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');

// Proteção básica: o usuário deve estar logado
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// 1. Conexão com o banco e ID da prova
require_once __DIR__ . '/conexao/conecta.php';

$idProva = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idProva <= 0) {
    die("ID da prova inválido.");
}

// 2. Inclui a biblioteca TCPDF
require_once(__DIR__ . '/tcpdf/tcpdf.php');

// 3. Consulta ao banco de dados
$sqlNome = "SELECT nome FROM tb_prova WHERE id = ?";
$stmtNome = $conn->prepare($sqlNome);
$stmtNome->bind_param("i", $idProva);
$stmtNome->execute();
$resultNome = $stmtNome->get_result();

if ($resultNome && $resultNome->num_rows > 0) {
    $rowNome = $resultNome->fetch_assoc();
    $nomeProva = htmlspecialchars($rowNome['nome']);
} else {
    $conn->close();
    die("Prova não encontrada.");
}
$stmtNome->close();

// Busca as questões
$sql = "SELECT id, quest, alt_a, alt_b, alt_c, alt_d, alt_e,
               foto, tipo, numQuestao
        FROM tb_quest
        WHERE prova = ?
        ORDER BY numQuestao";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idProva);
$stmt->execute();
$result = $stmt->get_result();

$questoes = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $questoes[] = $row;
    }
}
$stmt->close();
$conn->close();

if (empty($questoes)) {
    die("Nenhuma questão encontrada para esta prova.");
}

// =========================================================================
// 4. GERAÇÃO DO PDF USANDO TCPDF
// =========================================================================
class PDF extends TCPDF {
    public $title;

    public function Header() {
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 15, 'Prova: ' . $this->title, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(5);

        if ($this->getPage() === 1) {
            $this->SetFont('helvetica', 'I', 10);
            $this->Cell(0, 10, 'Nome do Aluno(a): ____________________________________________________________________', 0, 1, 'L', 0, '', 0, false, 'M', 'M');
            $this->Ln(3);
            $this->SetLineWidth(0.1);
            $this->Line(10, 35, 200, 35);
        } else {
            $this->SetLineWidth(0.1);
            $this->Line(10, 25, 200, 25);
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . ' - Caminho do Saber', 0, 0, 'C');
    }
}

// Criação do PDF
$pdf = new PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Caminho do Saber');
$pdf->SetTitle($nomeProva);
$pdf->title = $nomeProva;

$pdf->SetMargins(PDF_MARGIN_LEFT, 40, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

// =========================================================================
// 5. PERCORRE AS QUESTÕES
// =========================================================================
foreach ($questoes as $questao) {
    $numQ = htmlspecialchars($questao['numQuestao']);
    $enunciado = nl2br(htmlspecialchars($questao['quest']));

    // Título da Questão
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->writeHTML('<b>Questão ' . $numQ . ':</b>', true, false, true, false, '');

    // Enunciado
    $pdf->SetFont('helvetica', '', 11);
    $htmlEnunciado = '<p>' . str_replace("\n", "<br />", $enunciado) . '</p>';
    $pdf->writeHTML($htmlEnunciado, true, false, true, false, '');

    // =========================
    // Exibe imagem, se existir
    // =========================
    $dados_foto = $questao['foto'] ?? null;
    $tipo_foto = $questao['tipo'] ?? null;

    if (!empty($dados_foto)) {
        $tipoImg = trim($tipo_foto);
        if (empty($tipoImg) || strpos($tipoImg, '/') === false) {
            $tipoImg = 'image/jpeg';
        }

        $extensao = explode('/', $tipoImg)[1] ?? 'jpg';
        if ($extensao === 'jpeg') $extensao = 'jpg';

        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, 'img_') . '.' . $extensao;
        file_put_contents($tempFile, $dados_foto);

        list($largura, $altura) = @getimagesize($tempFile);

        if ($largura && $altura) {
            // Conversão px -> mm
            $larguraMM = $largura * 0.264583;
            $alturaMM = $altura * 0.264583;

            // Limites máximos
            $maxWidth = 120;
            $maxHeight = 85;

            $escala = min($maxWidth / $larguraMM, $maxHeight / $alturaMM, 1);
            $finalWidth = $larguraMM * $escala;
            $finalHeight = $alturaMM * $escala;

            // Centraliza
            $pageWidth = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
            $xPos = PDF_MARGIN_LEFT + ($pageWidth - $finalWidth) / 2;

            $pdf->Ln(3);
            $pdf->Image(
                $tempFile,
                $xPos,
                '',
                $finalWidth,
                $finalHeight,
                '',
                '',
                '',
                true,
                300,
                '',
                false,
                false,
                0,
                false,
                false,
                false
            );
            $pdf->Ln($finalHeight + 5);
        }

        @unlink($tempFile);
    }

    // Alternativas
    $alts = [
        'A' => $questao['alt_a'],
        'B' => $questao['alt_b'],
        'C' => $questao['alt_c'],
        'D' => $questao['alt_d'],
        'E' => $questao['alt_e'],
    ];

    $htmlAlternativas = '<table border="0" cellpadding="2">';
    foreach (['A','B','C','D','E'] as $letra) {
        $texto = $alts[$letra];
        if ($texto === null || $texto === '') continue;
        $htmlAlternativas .= '<tr><td width="20"><b>' . $letra . ')</b></td><td>' . htmlspecialchars($texto) . '</td></tr>';
    }
    $htmlAlternativas .= '</table><br />';

    $pdf->writeHTML($htmlAlternativas, true, false, true, false, '');
}

// =========================================================================
// 6. SAÍDA DO PDF
// =========================================================================
$nomeArquivo = "Prova_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $nomeProva) . ".pdf";
$pdf->Output($nomeArquivo, 'I');
exit;
?>
