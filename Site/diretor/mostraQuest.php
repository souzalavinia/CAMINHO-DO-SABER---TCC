<?php
// mostraQuest.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>PROVAS</title>
	<style>
	:root{
		--primary-color:#0d4b9e;--primary-dark:#0a3a7a;--primary-light:#3a6cb5;
		--gold-color:#D4AF37;--gold-light:#E6C200;--gold-dark:#996515;
		--black:#212529;--dark-black:#121212;--white:#ffffff;
		--light-gray:#f5f7fa;--medium-gray:#e0e5ec;--dark-gray:#6c757d
	}
	.ocultar{display:none}
	body{font-family:'Montserrat',Arial,sans-serif;margin:0;padding:20px;background-color:var(--light-gray)}
	.container{max-width:800px;margin:auto;background:var(--white);padding:20px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.1)}
	.btn-voltar{background:var(--primary-color);color:var(--white);padding:10px 20px;text-decoration:none;font-size:1rem;font-weight:600;border-radius:50px;box-shadow:0 4px 12px rgba(0,0,0,.1);transition:all .3s ease;border:none;cursor:pointer;margin-bottom:20px;display:inline-flex;align-items:center;gap:8px}
	.btn-voltar:hover{background:var(--primary-dark);transform:translateY(-2px)}
	.btn-voltar:active{transform:translateY(1px)}
	h1{text-align:center;color:var(--primary-dark);margin-bottom:30px}
	.questao{margin-bottom:30px;border-bottom:1px solid var(--medium-gray);padding-bottom:20px}
	.questao h2{color:var(--primary-color);font-size:1.4rem}
	img{display:block;margin:10px auto;border-radius:5px;max-width:100%;height:auto}
	label{display:block;margin:10px 0;background:var(--light-gray);padding:12px;border-radius:5px;cursor:pointer;transition:all .2s ease}
	label:hover{background:var(--medium-gray)}
	input[type="radio"]{margin-right:10px;accent-color:var(--primary-color)}
	button{display:block;width:100%;padding:15px;background-color:var(--gold-color);color:var(--black);border:none;border-radius:5px;cursor:pointer;font-size:16px;margin-top:20px;font-weight:600;transition:all .3s ease}
	button:hover{background-color:var(--gold-light)}
	</style>
</head>
<body>
<div class="container">
	<a href="exibirProvas.php" class="btn-voltar"><span>←</span> Voltar</a>

	<?php
	include '../conexao/conecta.php';

	$idProva = isset($_GET['id']) ? (int)$_GET['id'] : 0;

	// Nome da prova
	$sqlNome = "SELECT nome FROM tb_prova WHERE id = ?";
	$stmtNome = $conn->prepare($sqlNome);
	$stmtNome->bind_param("i", $idProva);
	$stmtNome->execute();
	$resultNome = $stmtNome->get_result();

	if ($resultNome && $resultNome->num_rows > 0) {
		$rowNome = $resultNome->fetch_assoc();
		echo "<h1>" . htmlspecialchars($rowNome['nome']) . "</h1>";
	} else {
		echo "<h1>Prova não encontrada</h1>";
	}
	$stmtNome->close();
	?>

	<form id="formRespostas" method="POST" action="<?php echo 'tentativas.php?prova=' . urlencode($idProva); ?>">
		<input type="hidden" name="prova" value="<?php echo htmlspecialchars($idProva); ?>">
	<?php
	// Questões da prova
	$sql = "SELECT id, quest, alt_a, alt_b, alt_c, alt_d, alt_e, alt_corre, foto, tipo, numQuestao
			FROM tb_quest
			WHERE prova = ?
			ORDER BY numQuestao";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("i", $idProva);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$idQuestao = (int)$row['id']; // ID REAL DA QUESTÃO
			$numQ      = htmlspecialchars($row['numQuestao']);
			$gabarito  = strtoupper(trim((string)($row['alt_corre'] ?? ''))); // "A".."E"

			echo "<div class='questao' data-id='{$idQuestao}' data-correta='" . htmlspecialchars($gabarito) . "'>";
			echo "<h2>Questão {$numQ}</h2>";

			// Imagem, se houver
			if (!empty($row['foto'])) {
				$tipoImg = htmlspecialchars($row['tipo'] ?: 'image/png');
				$base64  = base64_encode($row['foto']);
				echo "<img src='data:{$tipoImg};base64,{$base64}' alt='Imagem da questão' />";
			}

			// Enunciado
			echo "<p>" . nl2br(htmlspecialchars($row['quest'])) . "</p>";

			// Alternativas
			$alts = [
				'A' => $row['alt_a'],
				'B' => $row['alt_b'],
				'C' => $row['alt_c'],
				'D' => $row['alt_d'],
				'E' => $row['alt_e'],
			];

			foreach (['A','B','C','D','E'] as $letra) {
				$texto = $alts[$letra];
				if ($texto === null || $texto === '') continue;

				$inputName = "respostas[{$idQuestao}]";   // <-- CONTRACTO COM tentativas.php
				$inputId   = "q{$idQuestao}_{$letra}";
				echo "<label for='{$inputId}'>";
				echo "<input type='radio' id='{$inputId}' name='{$inputName}' value='{$letra}' required>";
				echo htmlspecialchars($texto);
				echo "</label>";
			}

			echo "</div>";
		}
	} else {
		echo "<p>Nenhuma questão encontrada.</p>";
	}

	$stmt->close();
	$conn->close();
	?>
		<button id="btnEnviar" type="submit">Enviar Respostas</button>
	</form>
</div>

<!--
JS opcional para mostrar um preview local de acertos/erros antes de enviar.
Não é necessário para o servidor funcionar, que já recalcula tudo.
<script>
(function(){
	const form     = document.getElementById('formRespostas');
	const questoes = document.querySelectorAll('.questao');
	form.addEventListener('submit', function(e){
		let acertos = 0, erros = 0;
		questoes.forEach(q => {
			const id  = q.getAttribute('data-id');
			const cor = (q.getAttribute('data-correta') || '').toUpperCase();
			const sel = document.querySelector(`input[name="respostas[${id}]"]:checked`);
			if (sel) { (sel.value.toUpperCase() === cor) ? acertos++ : erros++; }
			else { erros++; }
		});
		// if (!confirm(`Você marcou ${acertos} acertos e ${erros} erros. Deseja enviar?`)) {
		//   e.preventDefault();
		// }
	});
})();
</script>
-->
</body>
</html>
