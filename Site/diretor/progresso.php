<?php
// progresso.php (versão com Progress Card v2)

// Sempre inicie a sessão antes de QUALQUER saída
session_start();

// Autorização
if (!isset($_SESSION['id'])) {
	header("Location: ../login.php");
	exit();
}
$id = (int)$_SESSION['id'];

// Conexão
include __DIR__ . '/../conexao/conecta.php';

// Charset
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title>Progresso - Caminho do Saber</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>

	<style>
		:root{
			--primary-color:#0d4b9e;
			--primary-dark:#0a3a7a;
			--primary-light:#3a6cb5;
			--gold-color:#D4AF37;
			--gold-light:#E6C200;
			--gold-dark:#996515;
			--black:#212529;
			--dark-black:#121212;
			--white:#ffffff;
			--light-gray:#f5f7fa;
			--medium-gray:#e0e5ec;
			--dark-gray:#6c757d;
			--border-radius:12px;
			--box-shadow:0 10px 30px rgba(0,0,0,.1);
			--transition:all .3s cubic-bezier(.25,.8,.25,1);

			/* Altura do header fixo definido no menu.php */
			--header-h: 120px;
		}

		*{margin:0;padding:0;box-sizing:border-box}
		body{
			font-family:'Poppins', sans-serif;
			line-height:1.6;
			background:var(--light-gray);
			color:var(--black);
		}

		/* Spacer para não deixar o conteúdo atrás do header fixo do menu.php */
		.site-header-spacer{ height: var(--header-h); }

		.main-content{ max-width:1200px; margin:0 auto 50px; padding: 24px 20px; }

		/* ===============================
		   Progress Card v2 (glass + ring)
		   =============================== */
		.progress-card.v2{
			--card-bg: rgba(255,255,255,.75);
			--ring-size: 164px;
			--ring-thickness: 12px;
			--ring-gap: 10px;

			width:100%;
			max-width:720px;
			margin:0 auto 40px;
			padding:22px;
			border-radius:20px;
			background:
				linear-gradient(var(--card-bg), var(--card-bg)) padding-box,
				linear-gradient(135deg, var(--primary-color), var(--gold-color)) border-box;
			border:1px solid transparent;
			box-shadow: 0 20px 40px rgba(0,0,0,.08);
			backdrop-filter: blur(8px);
			-webkit-backdrop-filter: blur(8px);
		}
		.progress-card.v2 .pc-header{
			display:flex;
			align-items:center;
			justify-content:space-between;
			gap:14px;
			padding:6px 6px 14px 6px;
			border-bottom:1px dashed var(--medium-gray);
		}
		.progress-card.v2 .pc-title{
			display:flex;
			align-items:center;
			gap:10px;
			font-size:1.15rem;
			color:var(--primary-dark);
			font-weight:700;
			letter-spacing:.2px;
		}
		.progress-card.v2 .pc-title i{
			font-size:1.05rem;
			color:var(--gold-color);
		}
		.progress-card.v2 .pc-cta{
			display:inline-flex;
			align-items:center;
			gap:8px;
			padding:8px 12px;
			border-radius:999px;
			text-decoration:none;
			font-weight:600;
			font-size:.9rem;
			background:linear-gradient(135deg, var(--primary-color), var(--primary-light));
			color:#fff;
			box-shadow:0 6px 16px rgba(13,75,158,.25);
			transition: transform .15s ease, box-shadow .2s ease, opacity .2s ease;
			white-space:nowrap;
		}
		.progress-card.v2 .pc-cta:hover{
			transform:translateY(-1px);
			box-shadow:0 10px 24px rgba(13,75,158,.28);
			opacity:.95;
		}
		.progress-card.v2 .pc-body{
			display:grid;
			grid-template-columns: auto 1fr;
			gap:24px 28px;
			align-items:center;
			padding:18px 6px 6px;
		}
		/* Ring progress */
		.progress-card.v2 .pc-ring{
			position:relative;
			width:var(--ring-size);
			height:var(--ring-size);
			display:grid;
			place-items:center;
			animation: pc-appear .6s ease-out both;
		}
		@keyframes pc-appear{ from{ transform:scale(.98); opacity:0 } to{ transform:scale(1); opacity:1 } }

		.progress-card.v2 .pc-ring-track,
		.progress-card.v2 .pc-ring-fill{
			position:absolute;
			inset:0;
			border-radius:50%;
		}
		/* Trilho */
		.progress-card.v2 .pc-ring-track{
			background:
				radial-gradient(closest-side, transparent calc(50% - var(--ring-thickness) - var(--ring-gap)), var(--medium-gray) 0 calc(50% - var(--ring-gap)), transparent 0);
			opacity:.6;
		}
		/* Preenchimento */
		.progress-card.v2 .pc-ring-fill{
			--pdeg: calc((var(--p, 0) / 100) * 360deg);
			background:
				conic-gradient(var(--primary-color) 0deg, var(--primary-light) var(--pdeg), transparent var(--pdeg) 360deg);
			-webkit-mask:
				radial-gradient(closest-side, transparent calc(50% - var(--ring-thickness) - var(--ring-gap)), #000 calc(50% - var(--ring-gap)));
			mask:
				radial-gradient(closest-side, transparent calc(50% - var(--ring-thickness) - var(--ring-gap)), #000 calc(50% - var(--ring-gap)));
		}
		/* Centro do ring */
		.progress-card.v2 .pc-ring-center{
			position:relative;
			z-index:1;
			width:calc(var(--ring-size) - (var(--ring-thickness)*2 + var(--ring-gap)*2));
			height:calc(var(--ring-size) - (var(--ring-thickness)*2 + var(--ring-gap)*2));
			border-radius:50%;
			display:flex;
			flex-direction:column;
			align-items:center;
			justify-content:center;
			background: linear-gradient(180deg, #fff, #f9fbff);
			box-shadow: inset 0 1px 0 rgba(255,255,255,.8), inset 0 -1px 0 rgba(0,0,0,.03);
		}
		.progress-card.v2 .pc-ring-center strong{
			font-size:2rem;
			line-height:1;
			color:var(--primary-dark);
		}
		.progress-card.v2 .pc-ring-center small{
			margin-top:4px;
			font-size:.9rem;
			color:var(--dark-gray);
		}
		/* Mensagem */
		.progress-card.v2 .pc-message{
			font-size:1rem;
			color:var(--black);
			line-height:1.5;
		}
		.progress-card.v2 .pc-message .pc-ok{
			color:#1b9e3e;
			font-weight:600;
		}
		.progress-card.v2 .pc-message i{ margin-right:6px; }
		/* Métricas */
		.progress-card.v2 .pc-metrics{
			display:flex;
			flex-wrap:wrap;
			gap:10px;
			margin-top:10px;
		}
		.progress-card.v2 .pc-metrics li{
			list-style:none;
			flex:1 1 110px;
			min-width:110px;
			background:#fff;
			border:1px solid var(--medium-gray);
			border-radius:12px;
			padding:10px 12px;
			display:flex;
			align-items:center;
			justify-content:space-between;
			box-shadow:0 6px 12px rgba(0,0,0,.04);
		}
		.progress-card.v2 .pc-metrics li span{ color:var(--dark-gray); font-size:.9rem; }
		.progress-card.v2 .pc-metrics li b{ font-size:1.05rem; color:var(--primary-dark); }
		/* Barra linear */
		.progress-card.v2 .pc-linear{
			position:relative;
			margin-top:12px;
			height:14px;
			background:var(--medium-gray);
			border-radius:999px;
			overflow:hidden;
			box-shadow: inset 0 2px 4px rgba(0,0,0,.06);
		}
		.progress-card.v2 .pc-linear-bar{
			position:absolute;
			inset:0;
			width:var(--p, 0%);
			background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
			transform-origin:left center;
			animation: pc-grow .8s ease-out both;
		}
		@keyframes pc-grow { from{ width:0% } to{ width:var(--p, 0%) } }
		.progress-card.v2 .pc-linear-label{
			position:absolute;
			right:8px;
			top:50%;
			transform:translateY(-50%);
			font-size:.8rem;
			color:#fff;
			font-weight:700;
			text-shadow:0 1px 2px rgba(0,0,0,.25);
		}

		/* Histórico */
		.history-container{ margin-top: 50px; }
		.history-title{
			font-size:1.8rem; color:var(--primary-color); margin-bottom:20px; text-align:center; position:relative; padding-bottom:15px;
		}
		.history-title::after{
			content:''; position:absolute; bottom:0; left:50%; transform:translateX(-50%);
			width:100px; height:3px; background:var(--gold-color); border-radius:3px;
		}
		.history-table{
			width:100%; border-collapse:collapse; margin-top:30px; box-shadow:var(--box-shadow);
			overflow:hidden; border-radius:var(--border-radius); background: var(--white);
		}
		.history-table th, .history-table td{ padding:15px 20px; text-align:center; border:1px solid var(--medium-gray); }
		.history-table th{
			background:var(--primary-color); color:#fff; font-weight:600; text-transform:uppercase; font-size:.9rem;
		}
		.history-table tr:nth-child(even){ background:var(--light-gray); }
		.history-table tr:hover{ background:rgba(13,75,158,.05); }

		/* Chip Tentativas */
		.tentativas-chip{
			display:inline-flex; align-items:center; gap:8px; padding:8px 12px;
			border:1px solid var(--medium-gray); border-radius:999px; text-decoration:none;
			background:#fff; color:var(--primary-color); font-weight:600;
			transition:transform .15s ease, box-shadow .2s ease, border-color .2s ease;
			box-shadow:0 2px 6px rgba(0,0,0,.05);
		}
		.tentativas-chip:hover{ border-color:var(--primary-color); box-shadow:0 4px 10px rgba(13,75,158,.15); transform:translateY(-1px); }
		.tentativas-chip i{ font-size:.95rem; }
		.tentativas-chip .badge{
			min-width:24px; padding:2px 8px; background:var(--primary-color); color:#fff;
			border-radius:999px; font-size:.85rem; line-height:1.4; text-align:center;
		}
		.tentativas-chip .txt{ font-size:.95rem; }

		/* Footer */
		footer{
			background: linear-gradient(135deg, var(--primary-color), var(--dark-black));
			color:#fff; text-align:center; padding:20px 0; border-top:3px solid var(--gold-color);
		}
		footer p{ font-size:.9rem; margin-bottom:10px; }
		footer a{ color:var(--gold-color); text-decoration:none; font-weight:500; transition: var(--transition); }
		footer a:hover{ color: var(--gold-light); text-decoration: underline; }

		/* Responsividade */
		@media (max-width: 768px){
			.history-table{ display:block; overflow-x:auto; white-space:nowrap; }
			.progress-card.v2 .pc-body{ grid-template-columns: 1fr; justify-items:center; text-align:center; }
			.progress-card.v2 .pc-metrics{ justify-content:center; }
		}
	</style>
</head>
<body>

<?php include __DIR__ . '/menu.php'; ?>
<div class="site-header-spacer" aria-hidden="true"></div>

<div class="main-content">
	<?php
		// Quantas provas distintas o usuário já tentou
		$sqlQtd = "SELECT COUNT(DISTINCT idProva) AS qtd FROM tb_tentativas WHERE idUsuario = ?";
		$stmt = $conn->prepare($sqlQtd);
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$resQtd = $stmt->get_result();
		$qtdProvasDistintas = ($row = $resQtd->fetch_assoc()) ? (int)$row['qtd'] : 0;
		$stmt->close();

		// Meta de provas do usuário
		$sqlMeta = "SELECT metaProvas FROM tb_usuario WHERE id = ?";
		$stmt = $conn->prepare($sqlMeta);
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$resMeta = $stmt->get_result();
		$meta = ($resMeta->num_rows > 0) ? (int)$resMeta->fetch_assoc()['metaProvas'] : 0;
		$stmt->close();

		$porc = ($meta > 0) ? min(100, ($qtdProvasDistintas / $meta) * 100) : 0;
		$porcInt = (int)round($porc);
		$resta   = max(0, (int)$meta - (int)$qtdProvasDistintas);
	?>

	<!-- ===== Progress Card v2 ===== -->
	<div class="progress-card v2" aria-labelledby="progressTitle">
		<header class="pc-header">
			<h2 id="progressTitle" class="pc-title">
				<i class="fa fa-bolt"></i>
				<span>Seu Progresso</span>
			</h2>
			<?php if ($meta > 0): ?>
				<a class="pc-cta" href="configuracao/configuracoes.php" title="Ajustar meta">
					<i class="fa fa-flag-checkered"></i> Ajustar meta
				</a>
			<?php endif; ?>
		</header>

		<div class="pc-body">
			<!-- Ring progress -->
			<div
				class="pc-ring"
				style="--p: <?= $porcInt ?>;"
				role="progressbar"
				aria-valuenow="<?= $porcInt ?>"
				aria-valuemin="0"
				aria-valuemax="100"
				aria-label="Progresso em <?= $porcInt ?> por cento"
			>
				<div class="pc-ring-track"></div>
				<div class="pc-ring-fill"></div>
				<div class="pc-ring-center">
					<strong><?= (int)$qtdProvasDistintas ?></strong>
					<small>de <?= (int)$meta ?></small>
				</div>
			</div>

			<!-- Lado direito: mensagem + métricas + barra -->
			<div>
				<p class="pc-message">
					<?php
						if ($meta <= 0) {
							echo "Defina uma meta de provas no seu perfil!";
						} elseif ($qtdProvasDistintas >= $meta) {
							echo "<span class='pc-ok'><i class=\"fa fa-check-circle\"></i> Meta concluída com sucesso! 🎉</span>";
						} else {
							echo "Faltam <strong>{$resta}</strong> ".($resta==1?'prova':'provas')." para concluir sua meta.";
						}
					?>
				</p>

				<ul class="pc-metrics" aria-label="Resumo de métricas">
					<li>
						<span>Provas</span>
						<b><?= (int)$qtdProvasDistintas ?></b>
					</li>
					<li>
						<span>Meta</span>
						<b><?= (int)$meta ?></b>
					</li>
					<li>
						<span>Restante</span>
						<b><?= (int)$resta ?></b>
					</li>
				</ul>

				<div class="pc-linear" aria-hidden="true">
					<span class="pc-linear-bar" style="--p: <?= $porcInt ?>%;"></span>
					<span class="pc-linear-label"><?= $porcInt ?>%</span>
				</div>
			</div>
		</div>
	</div>
	<!-- ===== /Progress Card v2 ===== -->

	<div class="history-container">
		<h2 class="history-title">Histórico de Provas</h2>

		<table class="history-table">
			<thead>
				<tr>
					<th>Prova</th>
					<th>Tentativas</th>
				</tr>
			</thead>
			<tbody>
				<?php
				// Uma linha por prova com total de tentativas; link para lista de tentativas
				$sql = "
					SELECT
						p.id   AS idProva,
						p.nome AS nomeProva,
						(SELECT COUNT(*) FROM tb_tentativas t2
						 WHERE t2.idUsuario = ? AND t2.idProva = p.id) AS tentativas
					FROM tb_prova p
					WHERE EXISTS (
						SELECT 1 FROM tb_tentativas t3
						WHERE t3.idUsuario = ? AND t3.idProva = p.id
					)
					ORDER BY p.nome ASC
				";
				$stmt_history = $conn->prepare($sql);
				$stmt_history->bind_param("ii", $id, $id);
				$stmt_history->execute();
				$consulta = $stmt_history->get_result();

				if ($consulta && $consulta->num_rows > 0) {
					while ($dados = $consulta->fetch_assoc()) {
						echo "<tr>";
						echo "	<td><a href='mostraQuest.php?id=".(int)$dados['idProva']."' class='prova-link'>".htmlspecialchars($dados['nomeProva'])."</a></td>";
						echo "	<td>
									<a class='tentativas-chip' 
									   href='listaTentativas.php?prova=".(int)$dados['idProva']."'
									   title='Ver todas as tentativas desta prova'
									   aria-label='Ver todas as tentativas da prova ".htmlspecialchars($dados['nomeProva'])."'>
										<i class='fa fa-list-check'></i>
										<span class='txt'>Tentativas</span>
										<span class='badge'>".(int)$dados['tentativas']."</span>
									</a>
								</td>";
						echo "</tr>";
					}
				} else {
					echo "<tr><td colspan='2'>Nenhuma prova realizada ainda</td></tr>";
				}

				$stmt_history->close();
				$conn->close();
				?>
			</tbody>
		</table>
	</div>
</div>

<footer>
	<p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
	<a href="../POLITICA.php">Política de privacidade</a>
</footer>

<script>
/* Fallback simples do dropdown do menu (caso o menu.php não injete JS) */
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
</script>

</body>
</html>
