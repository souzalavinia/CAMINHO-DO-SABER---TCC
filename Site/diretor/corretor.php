<?php
session_start();
if (!isset($_SESSION['id'])) {
	header("Location: ../login.php");
	exit();
}

$idUsuario = (int)$_SESSION['id'];
require_once '../conexao/conecta.php';

/* ============================
   HELPERS
============================ */
function arr_get($arr, $key, $default = null) {
	return (is_array($arr) && array_key_exists($key, $arr)) ? $arr[$key] : $default;
}

/**
 * Decoder resiliente para payloads JSON armazenados em tb_redacao.errosRedacao.
 */
function decode_payload($raw) {
	$s = is_string($raw) ? trim($raw) : '';
	if ($s === '') return [];

	// Remove BOM (UTF-8)
	$s = preg_replace('/^\xEF\xBB\xBF/', '', $s);

	// Garante UTF-8
	if (!mb_check_encoding($s, 'UTF-8')) {
		$s = mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
	}

	// 1) direta
	$det = json_decode($s, true);
	if (is_array($det)) return $det;

	// 2) sem barras extras
	$s2 = stripslashes($s);
	$det = json_decode($s2, true);
	if (is_array($det)) return $det;

	// 3) sem aspas englobando tudo
	if (preg_match('/^\s*"(.*)"\s*$/s', $s, $m)) {
		$det = json_decode($m[1], true);
		if (is_array($det)) return $det;
	}

	// 4) salvamento de emergência se o JSON foi truncado dentro de "texto"
	$trim = rtrim($s);
	if ($trim !== '' && !preg_match('/[}\]]$/', $trim) && preg_match('/"texto"\s*:\s*"/', $s)) {
		$fix = preg_replace('/,\s*"texto"\s*:\s*".*$/s', '', $s);
		$fix = rtrim($fix);
		if (!preg_match('/[}\]]$/', $fix)) $fix .= '}';
		$det = json_decode($fix, true);
		if (is_array($det)) return $det;
	}

	// 5) limpeza leve
	$s3 = preg_replace('/,(\s*[}\]])/', '$1', $s2);
	$s3 = preg_replace('/[[:cntrl:]]+/u', ' ', $s3);
	$det = json_decode($s3, true);

	return is_array($det) ? $det : [];
}

/**
 * Retorna "d/m/Y H:i" para exibir no CARD.
 */
function dataHoraCard($dataRedacaoStr, $det) {
	$iso = arr_get($det, 'gerado_em', null);
	if ($iso) {
		try {
			$dt = new DateTime($iso);
			$dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
			return $dt->format('d/m/Y H:i');
		} catch (Exception $e) { /* fallback */ }
	}
	// Y-m-d H:i:s
	$dt = DateTime::createFromFormat('Y-m-d H:i:s', $dataRedacaoStr, new DateTimeZone('America/Sao_Paulo'));
	if ($dt) return $dt->format('d/m/Y H:i');

	// d/m/Y H:i
	if (preg_match('/^\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}$/', $dataRedacaoStr)) {
		return $dataRedacaoStr;
	}
	// d/m/Y
	if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataRedacaoStr)) {
		return $dataRedacaoStr . ' 00:00';
	}
	$ts = strtotime($dataRedacaoStr);
	return $ts ? date('d/m/Y H:i', $ts) : htmlspecialchars($dataRedacaoStr);
}

/* Badge de nota */
function classNotaBadge($nota) {
	if ($nota >= 900) return 'nota-ouro';
	if ($nota >= 700) return 'nota-alta';
	if ($nota >= 500) return 'nota-media';
	return 'nota-baixa';
}

/* Barra visual 0..200 */
function barra_html($valor_0_200) {
	$percent = max(0, min(100, round(($valor_0_200 / 200) * 100)));
	return '<div class="prog"><div class="prog-fill" style="width: '.$percent.'%"></div><span class="prog-label">'.$valor_0_200.'/200</span></div>';
}

/**
 * Fallback de notas por competência a partir de colunas do banco, se o JSON não trouxer.
 */
function notas_from_row_if_missing($notas, $row) {
	if (!empty($notas)) return $notas;
	$sum = (int)arr_get($row,'nota_c1',0) + (int)arr_get($row,'nota_c2',0) + (int)arr_get($row,'nota_c3',0) + (int)arr_get($row,'nota_c4',0) + (int)arr_get($row,'nota_c5',0);
	if ($sum > 0) {
		return [
			'competencia_1' => (int)arr_get($row,'nota_c1',0),
			'competencia_2' => (int)arr_get($row,'nota_c2',0),
			'competencia_3' => (int)arr_get($row,'nota_c3',0),
			'competencia_4' => (int)arr_get($row,'nota_c4',0),
			'competencia_5' => (int)arr_get($row,'nota_c5',0),
		];
	}
	return [];
}

/* ============================
   EXCLUSÃO
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
	$idExcluir = (int)$_POST['excluir_id'];
	$stmt = $conn->prepare("DELETE FROM tb_redacao WHERE id = ? AND idUsuario = ?");
	$stmt->bind_param("ii", $idExcluir, $idUsuario);
	$stmt->execute();
	$stmt->close();
	header("Location: " . basename(__FILE__));
	exit();
}

/* ============================
   BUSCA DAS REDAÇÕES
============================ */
$sql = "SELECT * FROM tb_redacao WHERE idUsuario = ? ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$rs = $stmt->get_result();

// KPIs
$estat = ['qtd'=>0,'media'=>0,'max'=>0,'min'=>1000];
$somaNotas = 0;
$rows = [];
while ($row = $rs->fetch_assoc()) {
	$rows[] = $row;
	$estat['qtd']++;
	$notaInt = (int)$row['notaRedacao'];
	$somaNotas += $notaInt;
	if ($notaInt > $estat['max']) $estat['max'] = $notaInt;
	if ($notaInt < $estat['min']) $estat['min'] = $notaInt;
}
$stmt->close();
$estat['media'] = $estat['qtd'] > 0 ? (int)round($somaNotas / $estat['qtd']) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Correções de Redação | Caminho do Saber</title>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<style>
		:root{
			--pri:#0d4b9e;--pri-d:#0a3a7a;--pri-l:#3a6cb5;
			--gold:#D4AF37;--gold-d:#996515;
			--ok:#16a34a;--warn:#f59e0b;--bad:#ef4444;
			--txt:#212529;--mut:#6b7280;--bg:#f5f7fa;--white:#fff;
			--rad:14px;--sh:0 10px 30px rgba(0,0,0,.08);
			--header-h: 120px;
		}
		*{box-sizing:border-box;margin:0;padding:0}
		body{font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif;background:var(--bg);color:var(--txt)}
		.site-header-spacer{height:var(--header-h)}
		.container{max-width:1200px;margin:0 auto;padding:24px}

		/* Banner */
		.banner{
			background:linear-gradient(135deg,var(--pri),#152238);
			color:#fff;border-radius:var(--rad);padding:22px 22px 18px;box-shadow:var(--sh);
			display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap
		}
		.banner i{font-size:1.4rem;color:var(--gold)}
		.banner h2{font-weight:600;margin-bottom:6px;font-size:1.25rem}
		.banner p{opacity:.95;line-height:1.6}

		/* Cards genéricos */
		.card{background:var(--white);border-radius:var(--rad);box-shadow:var(--sh);padding:22px}

		/* Form */
		.grid{display:grid;gap:24px;margin-top:24px}
		@media (min-width: 992px){.grid{grid-template-columns:2fr 1fr}}
		.card h3{font-size:1.15rem;color:var(--pri);margin-bottom:14px}
		.input,.textarea,select{
			width:100%;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;font-size:0.98rem;transition:.2s;background:#fff
		}
		.input:focus,.textarea:focus,select:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 4px rgba(212,175,55,.15)}
		.textarea{min-height:260px;resize:vertical}
		.counter{font-size:.9rem;color:#6b7280;text-align:right;margin-top:6px}
		.btn{display:inline-flex;align-items:center;gap:8px;border:none;border-radius:12px;padding:12px 16px;font-weight:600;cursor:pointer;transition:.2s}
		.btn-primary{background:linear-gradient(90deg,var(--pri),var(--pri-d));color:#fff}
		.btn-primary:hover{filter:brightness(.95);transform:translateY(-1px)}
		.btn-ghost{background:#f3f4f6}
		.btn-danger{background:linear-gradient(90deg,#ef4444,#dc2626);color:#fff}
		.small{font-size:.85rem;color:#6b7280}

		/* KPIs */
		.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:24px}
		.kpi{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:12px 14px}
		.kpi b{font-size:.8rem;color:#6b7280;display:block}
		.kpi span{font-weight:800;font-size:1.2rem;color:#111827}

		/* Lista */
		.section-title{margin:34px 6px 12px;display:flex;align-items:center;gap:10px;color:#111827}
		.section-title i{color:var(--gold)}
		.cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px}
		.r-card{
			background:var(--white);border-radius:16px;box-shadow:var(--sh);padding:18px;border-left:4px solid transparent;transition:.2s;cursor:pointer
		}
		.r-card:hover{transform:translateY(-3px);border-left-color:var(--gold)}
		.r-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
		.r-title{font-size:1rem;color:#111827;font-weight:700;line-height:1.3}
		.r-sub{font-size:.88rem;color:#374151;margin-top:2px}
		.r-meta{display:flex;gap:10px;flex-wrap:wrap;color:#6b7280;font-size:.88rem;margin-top:8px}
		.badge{padding:4px 10px;border-radius:999px;font-size:.78rem;font-weight:600;color:#fff}
		.nota-ouro{background:linear-gradient(90deg,#D4AF37,#f3c969);color:#111827}
		.nota-alta{background:linear-gradient(90deg,#10b981,#059669)}
		.nota-media{background:linear-gradient(90deg,#60a5fa,#2563eb)}
		.nota-baixa{background:linear-gradient(90deg,#f87171,#ef4444)}
		.aderencia-chip{border:1px solid #e5e7eb;background:#fff;padding:3px 8px;border-radius:999px;font-size:.78rem}
		.chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
		.mini-competencias{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin-top:10px}
		.mini-bar{height:8px;background:#f1f5f9;border-radius:999px;overflow:hidden;position:relative}
		.mini-fill{position:absolute;left:0;top:0;bottom:0;background:linear-gradient(90deg,#38bdf8,#0ea5e9)}
		.mini-labels{display:flex;justify-content:space-between;color:#6b7280;font-size:.72rem;margin-top:4px}

		/* Modal */
		.modal{
			display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:50;
			justify-content:center;align-items:flex-start;
			padding: calc(var(--header-h) + 20px) 20px 20px;
		}
		.m-content{background:#fff;max-width:980px;width:100%;max-height:85vh;overflow:auto;border-radius:18px;box-shadow:var(--sh);position:relative}
		.m-head{padding:18px 20px 10px;border-bottom:1px solid #f0f0f0}
		.m-body{padding:18px 20px 22px}
		.m-title{font-size:1.25rem;color:#111827;font-weight:800;display:flex;gap:10px;align-items:center}
		.close{position:absolute;right:14px;top:10px;border:none;background:transparent;font-size:1.4rem;color:#6b7280;cursor:pointer}
		.close:hover{color:#111827}
		.meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:10px 0 14px}
		.meta-item{background:#f9fafb;border:1px solid #eef2f7;border-radius:12px;padding:10px 12px}
		.meta-item b{display:block;font-size:.8rem;color:#6b7280;margin-bottom:4px}
		.meta-item span{font-weight:600}
		.redacao{background:#fbfbfd;border:1px solid #eef2f7;border-radius:12px;padding:14px;margin-top:6px;line-height:1.8;white-space:pre-wrap}

		.grid-competencias{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-top:14px}
		.comp{border:1px solid #eef2f7;border-radius:12px;padding:12px;background:#fff}
		.comp h4{font-size:.95rem;color:#0f172a;margin-bottom:8px}
		.prog{position:relative;height:10px;background:#f1f5f9;border-radius:999px;overflow:hidden;margin-top:8px}
		.prog-fill{position:absolute;left:0;top:0;bottom:0;background:linear-gradient(90deg,#38bdf8,#0ea5e9)}
		.prog-label{font-size:.8rem;color:#0f172a;margin-top:6px;display:inline-block}
		.com-item{border-left:3px solid var(--gold);background:linear-gradient(180deg,#fff, #fcfcfe);border-radius:0 12px 12px 0;padding:10px 12px;margin-top:10px}

		@media (max-width:768px){
			.banner{padding:16px}
			.cards{grid-template-columns:1fr}
			.kpis{grid-template-columns:repeat(2,1fr)}
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/menu.php'; ?>
	<div class="site-header-spacer" aria-hidden="true"></div>

	<div class="container">
		<!-- Banner -->
		<div class="banner" role="region" aria-label="Apresentação">
			<i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
			<div>
				<h2>Correção automática estilo ENEM</h2>
				<p>Receba nota estimada (0–1000), notas por competência (C1–C5) e comentários gerados por IA. Use para prática guiada — a orientação do seu professor continua sendo essencial.</p>
			</div>
		</div>

		<!-- Form de envio -->
		<form action="redacao.php" method="POST" style="margin-top:22px" onsubmit="return validarEnvio()" aria-labelledby="formTitulo">
			<div class="grid">
				<div class="card">
					<h3 id="formTitulo">Insira seu TEMA, TÍTULO e REDAÇÃO</h3>

					<!-- Tema: select + tema livre -->
					<div style="display:grid; gap:10px; grid-template-columns:1fr 1fr">
						<div>
							<label for="temaSelect" class="small">Tema (selecione)</label>
							<select class="input" id="temaSelect" onchange="onTemaChange()">
								<option value="">Selecione</option>
								<option>Desafios para a valorização da herança africana no Brasil</option>
								<option>Desafios para o enfrentamento da invisibilidade do trabalho de cuidado realizado pela mulher no Brasil</option>
								<option>Desafios para a valorização de comunidades e povos tradicionais no Brasil</option>
								<option>Invisibilidade e registro civil: garantia de acesso à cidadania no Brasil</option>
								<option>O estigma associado às doenças mentais na sociedade brasileira</option>
								<option>Democratização do acesso ao cinema no Brasil</option>
								<option>Manipulação do comportamento do usuário pelo controle de dados na Internet</option>
								<option value="__OUTRO__">Outro (tema livre)</option>
							</select>
						</div>
						<div id="temaLivreWrap" style="display:none">
							<label for="temaLivre" class="small">Tema (livre)</label>
							<input class="input" type="text" id="temaLivre" placeholder="Digite o tema exato…">
						</div>
					</div>

					<!-- Campo oculto que vai pro backend como 'temaRedacao' -->
					<input type="hidden" name="temaRedacao" id="temaRedacao" required>
					<!-- NOVO: aderência estimada pelo front -->
					<input type="hidden" name="aderenciaCliente" id="aderenciaCliente">

					<!-- Título opcional -->
					<label for="tituloRedacao" class="small" style="margin-top:12px;">Título (opcional)</label>
					<input class="input" type="text" id="tituloRedacao" name="tituloRedacao" placeholder="Digite um título (recomendado)">

					<!-- Texto -->
					<label for="redacao" class="small" style="margin-top:12px;">Redação</label>
					<textarea class="textarea" name="redacao" id="redacao" placeholder="Digite sua redação aqui..." oninput="contar();analisarAderencia()"></textarea>
					<div class="counter">
						<span id="countChars">0</span> caracteres ·
						<span id="aderenciaBadge" class="badge nota-media" title="Estimativa local de aderência ao tema">Aderência: —</span>
					</div>

					<div id="hintAderencia" class="small" style="margin-top:8px;color:#6b7280"></div>

					<button class="btn btn-primary" type="submit" style="margin-top:10px">
						<i class="fa-solid fa-paper-plane"></i> Enviar Redação
					</button>
				</div>

				<!-- Dicas -->
				<div class="card">
					<h3>Como tirar nota alta</h3>
					<ul style="list-style:none;display:grid;gap:12px">
						<li style="display:flex;gap:10px"><i class="fa-solid fa-star" style="color:var(--gold)"></i> <div><b>Domínio da norma culta</b> — gramática e ortografia.</div></li>
						<li style="display:flex;gap:10px"><i class="fa-solid fa-star" style="color:var(--gold)"></i> <div><b>Compreensão do tema</b> — responda exatamente ao proposto.</div></li>
						<li style="display:flex;gap:10px"><i class="fa-solid fa-star" style="color:var(--gold)"></i> <div><b>Coesão</b> — conectivos e progressão lógica.</div></li>
						<li style="display:flex;gap:10px"><i class="fa-solid fa-star" style="color:var(--gold)"></i> <div><b>Argumentação</b> — consistência e repertório.</div></li>
						<li style="display:flex;gap:10px"><i class="fa-solid fa-star" style="color:var(--gold)"></i> <div><b>Intervenção</b> — agente, ação, meio, finalidade e monitoramento.</div></li>
					</ul>
					<div class="small" style="margin-top:10px;background:rgba(212,175,55,.08);border-left:3px solid var(--gold);padding:10px 12px;border-radius:10px">
						<b>Dica:</b> Use título e revisite o enunciado: a aderência sobe e sua C2 agradece.
					</div>
				</div>
			</div>
		</form>

		<!-- KPIs -->
		<div class="kpis" role="region" aria-label="Resumo geral">
			<div class="kpi"><b>Total</b><span><?= (int)$estat['qtd'] ?></span></div>
			<div class="kpi"><b>Média</b><span><?= (int)$estat['media'] ?></span></div>
			<div class="kpi"><b>Maior</b><span><?= (int)$estat['max'] ?></span></div>
			<div class="kpi"><b>Menor</b><span><?= (int)$estat['min'] ?></span></div>
		</div>

		<!-- Lista de Redações -->
		<h3 class="section-title" style="margin-top:22px"><i class="fa-solid fa-book-open"></i> Suas Redações</h3>

		<?php if (count($rows) === 0): ?>
			<div class="card" style="text-align:center">
				<i class="fa-regular fa-file-lines" style="font-size:2rem;color:#6b7280"></i>
				<p class="small" style="margin-top:8px">Nenhuma redação encontrada. Envie a primeira acima.</p>
			</div>
		<?php else: ?>
			<div class="cards">
				<?php foreach ($rows as $row): ?>
					<?php
						$det         = decode_payload($row['errosRedacao']);
						$comentarios = arr_get($det, 'comentarios', []);

						$notasJson   = arr_get($det, 'notas', []);
						$notas       = notas_from_row_if_missing($notasJson, $row);

						$temaInf     = arr_get($det, 'tema_informado', ($row['temaRedacao'] ?: arr_get($det, 'tema', '')));
						$dataHora    = dataHoraCard($row['dataRedacao'], $det);

						// Título
						$titulo      = isset($row['tituloRedacao']) ? trim((string)$row['tituloRedacao']) : '';
						if ($titulo === '') { $titulo = trim((string)arr_get($det, 'titulo', '')); }
						$tituloCard  = $titulo !== '' ? $titulo : $row['temaRedacao'];

						$notaInt     = (int)$row['notaRedacao'];
						$notaBadge   = classNotaBadge($notaInt);

						// Aderência: preferir heurística local do front; fallback IA
						$scoreLocal  = arr_get($det, 'aderencia_cliente', null);
						$scoreIa     = arr_get($det, 'score_tema', null);
						$scoreUsado  = is_numeric($scoreLocal) ? $scoreLocal : $scoreIa;
						$aderenciaPct= is_numeric($scoreUsado) ? max(0, min(100, round($scoreUsado * 100))) : null;

						// Preview
						$preview = mb_substr(trim((string)$row['redacao']), 0, 160);
						if (mb_strlen((string)$row['redacao']) > 160) $preview .= '…';

						// Valores C1..C5
						$c1 = isset($notas['competencia_1']) ? (int)$notas['competencia_1'] : 0;
						$c2 = isset($notas['competencia_2']) ? (int)$notas['competencia_2'] : 0;
						$c3 = isset($notas['competencia_3']) ? (int)$notas['competencia_3'] : 0;
						$c4 = isset($notas['competencia_4']) ? (int)$notas['competencia_4'] : 0;
						$c5 = isset($notas['competencia_5']) ? (int)$notas['competencia_5'] : 0;
						$mini = [$c1,$c2,$c3,$c4,$c5];
					?>
					<!-- Card -->
					<article class="r-card" role="article" aria-labelledby="t-<?= (int)$row['id'] ?>" onclick="openModal(<?= (int)$row['id'] ?>)">
						<div class="r-top">
							<div>
								<div id="t-<?= (int)$row['id'] ?>" class="r-title"><?= htmlspecialchars($tituloCard) ?></div>
								<?php if ($titulo !== ''): ?>
									<div class="r-sub"><?= htmlspecialchars($row['temaRedacao']) ?></div>
								<?php endif; ?>
							</div>
							<div class="badge <?= $notaBadge ?>" title="Nota final (0–1000)">Nota <?= number_format($notaInt, 0, ',', '.') ?></div>
						</div>

						<div class="r-meta">
							<span title="Data e hora da prova"><i class="fa-regular fa-calendar"></i> <?= $dataHora ?></span>
						</div>

						<p class="small" style="margin-top:6px;color:#374151"><?= htmlspecialchars($preview) ?></p>

						<div class="chips">
							<span class="aderencia-chip">Tema: <b><?= htmlspecialchars($temaInf) ?></b></span>
							<?php if ($aderenciaPct !== null): ?>
								<span class="aderencia-chip <?= $aderenciaPct >= 65 ? 'chip--ok' : ($aderenciaPct >= 35 ? '' : 'chip--warn') ?>">
									Aderência: <b><?= $aderenciaPct ?>%</b>
								</span>
							<?php endif; ?>
						</div>

						<!-- Mini barras C1..C5 -->
						<div class="mini-competencias" aria-label="Resumo por competências">
							<?php foreach ($mini as $v): $p = max(0,min(100, round(($v/200)*100))); ?>
								<div class="mini-bar"><div class="mini-fill" style="width: <?= $p ?>%"></div></div>
							<?php endforeach; ?>
						</div>
						<div class="mini-labels"><span>C1</span><span>C2</span><span>C3</span><span>C4</span><span>C5</span></div>
					</article>

					<!-- Modal -->
					<div class="modal" id="m-<?= (int)$row['id'] ?>" role="dialog" aria-modal="true" aria-labelledby="mh-<?= (int)$row['id'] ?>">
						<div class="m-content">
							<button class="close" onclick="closeModal(<?= (int)$row['id'] ?>)" aria-label="Fechar">&times;</button>
							<div class="m-head">
								<div class="m-title" id="mh-<?= (int)$row['id'] ?>">
									<i class="fa-solid fa-pen-to-square" style="color:var(--gold)"></i>
									<?= htmlspecialchars($tituloCard) ?>
								</div>
								<div class="chips" style="margin:10px 0 4px">
									<span class="aderencia-chip">Tema: <b><?= htmlspecialchars($temaInf) ?></b></span>
									<?php if ($aderenciaPct !== null): ?>
										<span class="aderencia-chip <?= $aderenciaPct >= 65 ? 'chip--ok' : ($aderenciaPct >= 35 ? '' : 'chip--warn') ?>">
											Aderência (tema × título+texto): <b><?= $aderenciaPct ?>%</b>
										</span>
									<?php endif; ?>
								</div>
								<div class="meta-grid">
									<div class="meta-item"><b>Nota</b><span class="<?= $notaBadge ?> badge"><?= number_format($notaInt, 0, ',', '.') ?></span></div>
									<div class="meta-item"><b>Data e hora</b><span><?= $dataHora ?></span></div>
								</div>
							</div>
							<div class="m-body">
								<h4 style="color:#0f172a">Sua redação</h4>
								<div class="redacao"><?= nl2br(htmlspecialchars($row['redacao'])) ?></div>

								<!-- Competências -->
								<?php
									$cmap = [
										'competencia_1' => 'C1 — Norma padrão',
										'competencia_2' => 'C2 — Compreensão do tema',
										'competencia_3' => 'C3 — Coesão/organização',
										'competencia_4' => 'C4 — Argumentação/recursos',
										'competencia_5' => 'C5 — Proposta de intervenção',
									];
								?>
								<?php if (!empty($notas)): ?>
									<h4 style="margin-top:16px;color:#0f172a">Notas por competência</h4>
									<div class="grid-competencias">
										<?php foreach ($cmap as $ckey => $ctitle):
											$val = isset($notas[$ckey]) ? (int)$notas[$ckey] : 0;
											$coment = isset($comentarios[$ckey]) ? $comentarios[$ckey] : null;
										?>
											<div class="comp">
												<h4><?= htmlspecialchars($ctitle) ?></h4>
												<?= barra_html($val) ?>
												<?php if ($coment): ?>
													<div class="com-item"><b>Comentário:</b> <span><?= htmlspecialchars($coment) ?></span></div>
												<?php endif; ?>
											</div>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<?php if (isset($comentarios['geral'])): ?>
									<div class="com-item" style="margin-top:14px"><b>Observação geral:</b> <?= htmlspecialchars($comentarios['geral']) ?></div>
								<?php endif; ?>

								<div class="actions" style="margin-top:16px">
									<button class="btn btn-ghost" type="button" onclick="printModal(<?= (int)$row['id'] ?>)"><i class="fa-solid fa-print"></i> Imprimir</button>
									<button class="btn btn-ghost" type="button" onclick='copyJson(<?= json_encode($det, JSON_UNESCAPED_UNICODE) ?>)'><i class="fa-solid fa-code"></i> Copiar JSON</button>
									<form method="post" onsubmit='return confirm("Tem certeza que deseja excluir esta redação?");' style="display:inline-flex">
										<input type="hidden" name="excluir_id" value="<?= (int)$row['id'] ?>">
										<button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Excluir</button>
									</form>
								</div>

								<p class="small" style="margin-top:10px">Os critérios exibem descrições quando disponíveis em <code>criterios</code> (payload da API).</p>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>

	<script>
		/* ============================
		   Aderência local (front)
		============================ */
		const STOP = new Set([
			'a','o','e','de','da','do','das','dos','um','uma','uns','umas',
			'em','no','na','nos','nas','para','por','com','sem','sobre','entre',
			'como','que','se','ao','à','às','aos','ou','mas','os','as',
			'é','ser','foi','são','era','será','tem','há','ter'
		]);
		const MARCADORES = [
			"em primeiro lugar","além disso","por outro lado","no entanto",
			"portanto","dessa forma","assim sendo","logo","todavia","entretanto"
		];
		function norm(s){
			if(!s) return '';
			return s.toLowerCase()
				.normalize('NFD').replace(/[\u0300-\u036f]/g,'')
				.replace(/[^\w\s-]/g,' ')
				.replace(/\s+/g,' ')
				.trim();
		}
		function tokens(s){
			return norm(s).split(' ').filter(w => w && !STOP.has(w));
		}
		function onTemaChange(){
			const sel = document.getElementById('temaSelect');
			const wrap = document.getElementById('temaLivreWrap');
			wrap.style.display = (sel.value === '__OUTRO__') ? 'block' : 'none';
			atualizarTemaHidden();
			analisarAderencia();
		}
		function atualizarTemaHidden(){
			const sel = document.getElementById('temaSelect');
			const livre = document.getElementById('temaLivre');
			const hidden = document.getElementById('temaRedacao');
			hidden.value = (sel.value === '__OUTRO__') ? (livre.value || '') : (sel.value || '');
		}

		let _ultimoScoreAderencia = null;

		function analisarAderencia(){
			atualizarTemaHidden();
			const tema = document.getElementById('temaRedacao').value;
		 const titulo = document.getElementById('tituloRedacao').value;
			const redacao = document.getElementById('redacao').value;

			const tt = tokens(tema);
			if(tt.length === 0){
				setBadge('—','nota-media','');
				document.getElementById('hintAderencia').textContent = '';
				_ultimoScoreAderencia = null;
				document.getElementById('aderenciaCliente').value = '';
				return;
			}

			const cx = tokens((titulo ? titulo+' ' : '') + redacao);

			let hits = 0;
			const setCx = new Set(cx);
			tt.forEach(t => { if(setCx.has(t)) hits++; });

			const redNorm = norm(redacao);
			let marc = 0;
			MARCADORES.forEach(m => { if(redNorm.includes(m)) marc++; });

			const overlap = tt.length ? (hits / tt.length) : 0;
			const marcNorm = Math.min(1, marc / 5);
			const score = 0.7 * overlap + 0.3 * marcNorm;
			_ultimoScoreAderencia = Math.max(0, Math.min(1, score));
			document.getElementById('aderenciaCliente').value = _ultimoScoreAderencia.toFixed(4);

			let cls = 'nota-baixa', label='Baixa';
			let hint = 'Pouca relação com o tema. Reforce o vínculo no título e nos parágrafos.';
			if(score >= 0.65){ cls = 'nota-alta'; label='Alta'; hint = 'Boa aderência ao tema — mantenha a consistência.'; }
			else if(score >= 0.35){ cls = 'nota-media'; label='Ok'; hint = 'Aderência razoável. Dê exemplos e volte ao tema no fechamento.'; }

			setBadge(label, cls, `Aderência estimada ao tema: ${(score*100).toFixed(0)}%`);
			document.getElementById('hintAderencia').textContent = hint;
		}
		function setBadge(text, cls, title){
			const b = document.getElementById('aderenciaBadge');
			b.textContent = 'Aderência: ' + text;
			b.className = 'badge ' + cls;
			if(title) b.title = title;
		}
		function contar(){
			const el = document.getElementById('redacao');
			if(!el) return;
			document.getElementById('countChars').textContent = el.value.length.toString();
		}

		function validarEnvio(){
			analisarAderencia();
			const tema = document.getElementById('temaRedacao').value.trim();
			const redacao = document.getElementById('redacao').value.trim();
			if(!tema){
				alert('Escolha um tema (ou informe um tema livre).');
				return false;
			}
			if(redacao.length < 80){
				return confirm('Sua redação está muito curta. Deseja enviar mesmo assim?');
			}
			const badge = document.getElementById('aderenciaBadge').textContent || '';
			if(badge.includes('Baixa')){
				return confirm('A aderência ao tema parece baixa. Deseja enviar mesmo assim?');
			}
			return true;
		}

		/* ============================
		   Modal / Impressão / JSON
		============================ */
		function openModal(id){
			const m = document.getElementById('m-'+id);
			if(!m) return;
			m.style.display='flex';
			document.body.style.overflow='hidden';
		}
		function closeModal(id){
			const m = document.getElementById('m-'+id);
			if(!m) return;
			m.style.display='none';
			document.body.style.overflow='auto';
		}
		window.addEventListener('click', (e)=>{
			if(e.target.classList && e.target.classList.contains('modal')){
				e.target.style.display='none';
				document.body.style.overflow='auto';
			}
		});
		function printModal(id){
			const modal = document.getElementById('m-'+id);
			if(!modal) return;
			const content = modal.querySelector('.m-content').innerHTML;
			const w = window.open('', '_blank');
			w.document.write(`<html><head><title>Imprimir Redação</title>
				<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
				<style>
					body{font-family:Poppins,Arial,sans-serif;padding:18px}
					.badge{padding:4px 10px;border-radius:999px;font-size:.78rem;font-weight:600;color:#fff}
					.nota-ouro{background:linear-gradient(90deg,#D4AF37,#f3c969);color:#111827}
					.nota-alta{background:linear-gradient(90deg,#10b981,#059669)}
					.nota-media{background:linear-gradient(90deg,#60a5fa,#2563eb)}
					.nota-baixa{background:linear-gradient(90deg,#f87171,#ef4444)}
					.meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:10px 0 14px}
					.meta-item{background:#f9fafb;border:1px solid #eef2f7;border-radius:12px;padding:10px 12px}
					.redacao{background:#fbfbfd;border:1px solid #eef2f7;border-radius:12px;padding:14px;margin-top:6px;line-height:1.8;white-space:pre-wrap}
					.grid-competencias{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-top:14px}
					.comp{border:1px solid #eef2f7;border-radius:12px;padding:12px;background:#fff}
					.comp h4{font-size:.95rem;color:#0f172a;margin-bottom:8px}
					.prog{position:relative;height:10px;background:#f1f5f9;border-radius:999px;overflow:hidden;margin-top:8px}
					.prog-fill{position:absolute;left:0;top:0;bottom:0;background:linear-gradient(90deg,#38bdf8,#0ea5e9)}
					.prog-label{font-size:.8rem;color:#0f172a;margin-top:6px;display:inline-block}
					.com-item{border-left:3px solid #D4AF37;background:linear-gradient(180deg,#fff,#fcfcfe);border-radius:0 12px 12px 0;padding:10px 12px;margin-top:10px}
				</style></head><body>`);
			w.document.write(content);
			w.document.write('</body></html>');
			w.document.close();
			w.focus();
			w.print();
			w.close();
		}
		function copyJson(obj){
			try{
				const txt = JSON.stringify(obj, null, 2);
				navigator.clipboard.writeText(txt).then(()=>{
					alert('JSON copiado para a área de transferência.');
				});
			}catch(e){
				alert('Não foi possível copiar o JSON.');
			}
		}

		// Inicialização
		analisarAderencia();
		contar();
	</script>
</body>
</html>
