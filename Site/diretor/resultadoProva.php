<?php
// resultadoProva.php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../conexao/conecta.php';

$idUsuario   = (int) $_SESSION['id'];
$idTentativa = isset($_GET['tentativa']) ? (int) $_GET['tentativa'] : 0;

if ($idTentativa <= 0) {
    http_response_code(400);
    echo "Tentativa inválida.";
    exit();
}

// Liga exceptions (facilita debug controlado)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// 1) Busca cabecalho: tentativa + prova (garantir que pertence ao usuario)
$sqlHead = "SELECT t.id, t.acertos, t.erros, t.dataTentativa, t.idProva,
                   p.nome AS nomeProva, p.anoProva
            FROM tb_tentativas t
            INNER JOIN tb_prova p ON p.id = t.idProva
            WHERE t.id = ? AND t.idUsuario = ?";
$stmtHead = $conn->prepare($sqlHead);
$stmtHead->bind_param("ii", $idTentativa, $idUsuario);
$stmtHead->execute();
$head = $stmtHead->get_result()->fetch_assoc();
$stmtHead->close();

if (!$head) {
    http_response_code(404);
    echo "Tentativa não encontrada para este usuário.";
    exit();
}

$idProva   = (int) $head['idProva'];
$acertos   = (int) $head['acertos'];
$erros     = (int) $head['erros'];
$dataTent  = $head['dataTentativa'];
$nomeProva = $head['nomeProva'];
$anoProva  = $head['anoProva'];

// 2) Carrega as respostas da tentativa unidas às questões
$sqlDet = "SELECT 
              r.idQuestao,
              r.alternativa_marcada,
              r.correta,
              q.numQuestao,
              q.quest,
              q.alt_a, q.alt_b, q.alt_c, q.alt_d, q.alt_e,
              q.alt_corre,
              q.foto,
              q.tipo
          FROM tb_respostas r
          INNER JOIN tb_quest q ON q.id = r.idQuestao
          WHERE r.idTentativa = ?
          ORDER BY q.numQuestao ASC";
$stmtDet = $conn->prepare($sqlDet);
$stmtDet->bind_param("i", $idTentativa);
$stmtDet->execute();
$det = $stmtDet->get_result();
$questoes = $det->fetch_all(MYSQLI_ASSOC);
$stmtDet->close();

// 3) Conta total de questões da prova (para barra/percentual)
$sqlCount = "SELECT COUNT(*) AS total FROM tb_quest WHERE prova = ?";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param("i", $idProva);
$stmtCount->execute();
$totalQuestoes = (int) ($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtCount->close();

$percentual = $totalQuestoes > 0 ? round(($acertos / $totalQuestoes) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resultado da Prova - <?php echo htmlspecialchars($nomeProva); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root{
    --primary:#0d4b9e; --primary-dark:#0a3a7a; --primary-light:#3a6cb5;
    --gold:#D4AF37; --gold-light:#E6C200;
    --bg:#f5f7fa; --white:#fff; --gray:#e0e5ec; --text:#212529; --muted:#6c757d;
    --success:#1b9e3e; --danger:#c0392b;
}
*{box-sizing:border-box}
body{margin:0;font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;background:var(--bg);color:var(--text)}
.container{max-width:980px;margin:24px auto;padding:0 16px}
.card{background:var(--white);border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);padding:24px;margin-bottom:24px}
.header{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.h1{font-size:1.4rem;color:var(--primary-dark);margin:0}
.meta{color:var(--muted);font-size:.95rem}
.back a{display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#fff;text-decoration:none;padding:8px 14px;border-radius:10px}
.summary{display:grid;grid-template-columns:repeat(4,minmax(120px,1fr));gap:16px;margin-top:12px}
.kpi{background:#fff;border:1px solid var(--gray);border-radius:12px;padding:16px;text-align:center}
.kpi .big{font-weight:800;font-size:1.6rem}
.progress-wrap{margin-top:8px}
.progress{width:100%;height:12px;background:var(--gray);border-radius:999px;overflow:hidden}
.progress>span{display:block;height:100%;background:linear-gradient(90deg,var(--primary),var(--primary-light));width:<?php echo (int)$percentual; ?>%}
.list .questao{border:1px solid var(--gray);border-radius:12px;padding:16px;margin-bottom:16px}
.questao h3{margin:0 0 8px;color:var(--primary)}
.questao .enun{margin:8px 0 12px}
.questao img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 12px auto;    /* espaçamento e centralização */
    display: block;       /* garante centralização horizontal */
}
.alt{display:flex;gap:8px;flex-direction:column}
.alt label{padding:10px 12px;border:1px solid var(--gray);border-radius:10px}
.alt .marcada.certa{border-color:var(--success);background:rgba(27,158,62,.08)}
.alt .marcada.errada{border-color:var(--danger);background:rgba(192,57,43,.08)}
.badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:.78rem;border:1px solid}
.badge.ok{color:var(--success);border-color:var(--success)}
.badge.no{color:var(--danger);border-color:var(--danger)}
.small{color:var(--muted);font-size:.9rem}
.code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
</style>
</head>
<body>
<div class="container">
    <div class="card header">
        <div>
            <h1 class="h1">Resultado — <?php echo htmlspecialchars($nomeProva); ?> (<?php echo htmlspecialchars($anoProva); ?>)</h1>
            <div class="meta">Tentativa em <?php echo htmlspecialchars($dataTent); ?></div>
        </div>
        <div class="back">
            <a href="progresso.php"><i class="fa fa-arrow-left"></i> Voltar</a>
        </div>
    </div>

    <div class="card">
        <div class="summary">
            <div class="kpi">
                <div class="small">Acertos</div>
                <div class="big" style="color:var(--success)"><?php echo (int)$acertos; ?></div>
            </div>
            <div class="kpi">
                <div class="small">Erros</div>
                <div class="big" style="color:var(--danger)"><?php echo (int)$erros; ?></div>
            </div>
            <div class="kpi">
                <div class="small">Total</div>
                <div class="big"><?php echo (int)$totalQuestoes; ?></div>
            </div>
            <div class="kpi">
                <div class="small">Percentual</div>
                <div class="big"><?php echo (int)$percentual; ?>%</div>
            </div>
        </div>
        <div class="progress-wrap">
            <div class="progress"><span></span></div>
            <div class="small" style="margin-top:6px">Progresso: <?php echo (int)$percentual; ?>%</div>
        </div>
    </div>

    <div class="card list">
        <?php if (!$questoes): ?>
            <p class="small">Nenhuma resposta registrada nesta tentativa.</p>
        <?php else: ?>
            <?php foreach ($questoes as $q):
                $idQ        = (int)$q['idQuestao'];
                $num        = (int)$q['numQuestao'];
                $enun       = (string)$q['quest'];
                $altCorreta = strtoupper(trim((string)$q['alt_corre']));
                $marcada    = strtoupper(trim((string)$q['alternativa_marcada']));
                $isCorreta  = (int)$q['correta'] === 1;

                $alts = [
                    'A' => $q['alt_a'],
                    'B' => $q['alt_b'],
                    'C' => $q['alt_c'],
                    'D' => $q['alt_d'],
                    'E' => $q['alt_e'],
                ];
            ?>
            <div class="questao">
                <h3>Questão <?php echo htmlspecialchars((string)$num); ?>
                    <?php if ($marcada): ?>
                        <span class="badge <?php echo $isCorreta ? 'ok' : 'no'; ?>" title="Sua marcação">
                            <?php echo $isCorreta ? 'Acertou' : 'Errou'; ?>
                        </span>
                    <?php endif; ?>
                </h3>

                <?php if (!empty($q['foto'])): ?>
                    <img alt="Imagem da questão" src="data:<?php echo htmlspecialchars($q['tipo'] ?: 'image/jpeg'); ?>;base64,<?php echo base64_encode($q['foto']); ?>">
                <?php endif; ?>

                <div class="enun"><?php echo nl2br(htmlspecialchars($enun)); ?></div>

                <div class="alt">
                    <?php foreach ($alts as $letra => $texto): if ($texto === null || $texto === '') continue;
                        $classes = [];
                        if ($marcada === $letra) {
                            $classes[] = 'marcada';
                            $classes[] = ($letra === $altCorreta) ? 'certa' : 'errada';
                        }
                    ?>
                    <label class="<?php echo implode(' ', $classes); ?>">
                        <input type="radio" disabled <?php echo $marcada === $letra ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($texto); ?>
                        <?php if ($letra === $altCorreta): ?>
                            <span class="small" style="margin-left:8px">— correta</span>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="small" style="margin-top:6px">
                    Sua resposta: <strong><?php echo $marcada ?: '—'; ?></strong> |
                    Correta: <strong><?php echo $altCorreta; ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<footer>
		<p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
		<a href="../POLITICA.php" class="footer-link">Política de Privacidade</a>
	</footer>
    
</body>
</html>
