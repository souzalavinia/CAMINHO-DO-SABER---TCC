<?php
/**
 * tentativas.php - Grava a tentativa e as respostas do usuário
 * Fluxo:
 *  - GET:    ?prova=ID_DA_PROVA
 *  - POST:   respostas[ID_QUESTAO] = 'A'|'B'|'C'|'D'|'E'
 * Recalcula acertos/erros no servidor e persiste em tb_tentativas e tb_respostas.
 */

session_start();
require_once '../conexao/conecta.php';

if (!isset($_SESSION['id'])) {
	http_response_code(401);
	echo "Usuário não autenticado.";
	exit();
}

$idUsuario = (int) $_SESSION['id'];

// ---------- Entrada ----------
$idProva   = isset($_GET['prova']) ? (int) $_GET['prova'] : 0;
$respostas = (isset($_POST['respostas']) && is_array($_POST['respostas'])) ? $_POST['respostas'] : [];

if ($idProva <= 0) {
	http_response_code(400);
	echo "Parâmetro 'prova' inválido.";
	exit();
}

// ---------- Normalização robusta de respostas ----------
$norm = [];
foreach ($respostas as $idQuestao => $alt) {
	$idQ = (int) $idQuestao;
	if ($idQ <= 0) continue;

	$val = strtoupper(substr(trim((string)$alt), 0, 1)); // 'A'..'E'
	if (!in_array($val, ['A','B','C','D','E'], true)) continue;

	$norm[$idQ] = $val; // sobrescreve duplicatas
}
$respostas = $norm;

// ---------- Regras ----------
$dataTentativa = date('d/m/Y'); // coluna é VARCHAR(10)

// Ativa exceções no mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
	$conn->set_charset('utf8mb4');

	// 1) Contar total de questões da prova
	$sqlCount = "SELECT COUNT(*) AS total FROM tb_quest WHERE prova = ?";
	$stmtCount = $conn->prepare($sqlCount);
	$stmtCount->bind_param("i", $idProva);
	$stmtCount->execute();
	$resCount = $stmtCount->get_result()->fetch_assoc();
	$totalQuestoes = (int)($resCount['total'] ?? 0);
	$stmtCount->close();

	if ($totalQuestoes === 0) {
		http_response_code(404);
		echo "Prova sem questões ou não encontrada.";
		exit();
	}

	// 2) Preparar statements
	// 2.1) Buscar gabarito e prova da questão por ID
	$sqlQuest = "SELECT alt_corre, prova FROM tb_quest WHERE id = ?";
	$stmtQ = $conn->prepare($sqlQuest);

	// 2.2) Inserir resposta
	$sqlInsertResp = "INSERT INTO tb_respostas (idTentativa, idQuestao, alternativa_marcada, correta)
	                  VALUES (?, ?, ?, ?)";
	$stmtInsertResp = $conn->prepare($sqlInsertResp);

	// 3) Recalcular acertos/erros no servidor
	$acertosSrv = 0;
	$errosSrv   = 0;

	$respondidasValidas = 0;     // respostas que pertencem à prova
	$ignoradasProva     = 0;     // IDs que não pertencem a esta prova
	$naoEncontradas     = 0;     // IDs inexistentes em tb_quest

	// Valida e calcula corretude apenas para questões que pertencem à prova
	foreach ($respostas as $idQ => $altMarcada) {
		$stmtQ->bind_param("i", $idQ);
		$stmtQ->execute();
		$resQ = $stmtQ->get_result();

		if (!$resQ || !$resQ->num_rows) {
			$naoEncontradas++;
			continue; // id de questão inexistente
		}

		$rowQ = $resQ->fetch_assoc();
		$provaDaQuestao = (int)$rowQ['prova'];

		if ($provaDaQuestao !== $idProva) {
			$ignoradasProva++;
			continue; // questão não pertence a esta prova
		}

		$respondidasValidas++;

		$altCorreta = strtoupper(trim((string)$rowQ['alt_corre']));
		$isCorreta  = (int) ($altMarcada === $altCorreta);

		if ($isCorreta) {
			$acertosSrv++;
		} else {
			$errosSrv++;
		}
	}

	// Questões não respondidas contam como erro
	$naoRespondidas = max(0, $totalQuestoes - $respondidasValidas);
	$errosSrv += $naoRespondidas;

	// 4) Persistência com transação
	$conn->begin_transaction();

	// 4.1) Inserir tb_tentativas
	$sqlTentativa = "INSERT INTO tb_tentativas (acertos, erros, idProva, dataTentativa, idUsuario)
	                 VALUES (?, ?, ?, ?, ?)";
	$stmtTent = $conn->prepare($sqlTentativa);
	$stmtTent->bind_param("iiisi", $acertosSrv, $errosSrv, $idProva, $dataTentativa, $idUsuario);
	$stmtTent->execute();
	$idTentativa = (int)$conn->insert_id;

	// 4.2) Inserir tb_respostas apenas das questões válidas desta prova
	// Reitera para inserir (poderíamos ter cacheado os gabaritos; custo é baixo)
	$inseridos = 0;
	foreach ($respostas as $idQ => $altMarcada) {
		$stmtQ->bind_param("i", $idQ);
		$stmtQ->execute();
		$resQ = $stmtQ->get_result();

		if (!$resQ || !$resQ->num_rows) {
			continue; // inexistente
		}
		$rowQ = $resQ->fetch_assoc();
		if ((int)$rowQ['prova'] !== $idProva) {
			continue; // não pertence à prova
		}

		$altCorreta = strtoupper(trim((string)$rowQ['alt_corre']));
		$isCorreta  = (int) ($altMarcada === $altCorreta);

		$stmtInsertResp->bind_param("iisi", $idTentativa, $idQ, $altMarcada, $isCorreta);
		$stmtInsertResp->execute();
		$inseridos++;
	}

	$conn->commit();

	// --- Diagnóstico temporário (remova em produção) ---
	// file_put_contents('tentativas_debug.log', sprintf(
	// 	"[prova=%d usuario=%d] totalQuestoes=%d respondidasValidas=%d naoRespondidas=%d acertos=%d erros=%d inseridos=%d ignoradasProva=%d naoEncontradas=%d POST=%s\n",
	// 	$idProva, $idUsuario, $totalQuestoes, $respondidasValidas, $naoRespondidas, $acertosSrv, $errosSrv, $inseridos, $ignoradasProva, $naoEncontradas,
	// 	json_encode($respostas, JSON_UNESCAPED_UNICODE)
	// ), FILE_APPEND);

	// 5) Redireciona para progresso
	header("Location: progresso.php");
	exit();

} catch (mysqli_sql_exception $e) {
	// rollback se algo falhar após begin_transaction
	if ($conn && $conn->errno === 0) {
		// nada
	}
	if ($conn && $conn->thread_id) {
		// Se a transação estiver aberta, tenta reverter
		@$conn->rollback();
	}

	http_response_code(500);
	echo "Erro ao registrar tentativa: " . htmlspecialchars($e->getMessage());
	exit();
}
