<?php
/**
 * tentativas.php - Grava a tentativa e as respostas do usuário via AJAX
 * Retorna JSON: {status: 'success', idTentativa: N} ou {status: 'error', message: '...'}
 */

session_start();
require_once __DIR__ . '/conexao/conecta.php';

// =============================================
// FUNÇÃO PARA RETORNAR ERRO JSON
// =============================================
function returnJsonError($message, $httpCode = 500, $idProva = null) {
    error_log("ERRO DE EXECUÇÃO: " . $message);
    
    // Define o código de resposta HTTP (400, 401, 500 etc)
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit();
}

// =============================================
// VERIFICAÇÕES E LÓGICA DE PROCESSAMENTO
// (O restante da lógica de seu tentativas.php original deve ser mantida aqui)
// =============================================
if (!isset($_SESSION['id'])) {
	returnJsonError("Sua sessão expirou ou você não está logado.", 401);
}

$idUsuario = (int) $_SESSION['id'];

$idProva	= isset($_GET['prova']) ? (int) $_GET['prova'] : 0;
$respostas = (isset($_POST['respostas']) && is_array($_POST['respostas'])) ? $_POST['respostas'] : []; 

if ($idProva <= 0) {
	returnJsonError("O identificador da prova está faltando ou é inválido.", 400);
}

// ... (Restante da sua lógica de normalização, contagem de questões e cálculo de acertos/erros) ...
$norm = [];
foreach ($respostas as $idQuestao => $alt) {
	$idQ = (int) $idQuestao;
	if ($idQ <= 0) continue;

	$val = strtoupper(substr(trim((string)$alt), 0, 1)); 
	if (!in_array($val, ['A','B','C','D','E'], true)) continue;

	$norm[$idQ] = $val; 
}
$respostas = $norm;

$dataTentativa = date('d/m/Y'); 

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
		returnJsonError("A prova #{$idProva} não contém questões ou não foi encontrada no sistema.", 404, $idProva);
	}

	// 2) Preparar statements
	$sqlQuest = "SELECT alt_corre, prova FROM tb_quest WHERE id = ?";
	$stmtQ = $conn->prepare($sqlQuest);

	$sqlInsertResp = "INSERT INTO tb_respostas (idTentativa, idQuestao, alternativa_marcada, correta)
	                  VALUES (?, ?, ?, ?)";
	$stmtInsertResp = $conn->prepare($sqlInsertResp);

	// 3) Recalcular acertos/erros no servidor
	$acertosSrv = 0;
	$errosSrv	= 0;
	$respondidasValidas = 0; 	

	foreach ($respostas as $idQ => $altMarcada) {
		$stmtQ->bind_param("i", $idQ);
		$stmtQ->execute();
		$resQ = $stmtQ->get_result();

		if (!$resQ || !$resQ->num_rows) continue;
		$rowQ = $resQ->fetch_assoc();
		if ((int)$rowQ['prova'] !== $idProva) continue;

		$respondidasValidas++;
		$altCorreta = strtoupper(trim((string)$rowQ['alt_corre']));
		$isCorreta	= (int) ($altMarcada === $altCorreta);

		if ($isCorreta) {
			$acertosSrv++;
		} else {
			$errosSrv++;
		}
	}

	$naoRespondidas = max(0, $totalQuestoes - $respondidasValidas);
	$errosSrv += $naoRespondidas;

	// 4) Persistência com transação
	$conn->begin_transaction();

	// 4.1) Inserir tb_tentativas
	$sqlTentativa = "INSERT INTO tb_tentativas (acertos, erros, idProva, dataTentativa, idUsuario)
	                  VALUES (?, ?, ?, ?, ?)";
	$stmtTent = $conn->prepare($sqlTentativa);
	$stmtTent->bind_param("iiisi", $acertosSrv, $errosSrv, $idProva, $dataTentativa, $idUsuario);
	
	if (!$stmtTent->execute()) {
        throw new Exception("Falha ao registrar a tentativa principal.");
    }
	$idTentativa = (int)$conn->insert_id;

	// 4.2) Inserir tb_respostas
	foreach ($respostas as $idQ => $altMarcada) {
		$stmtQ->bind_param("i", $idQ);
		$stmtQ->execute();
		$resQ = $stmtQ->get_result();

		if (!$resQ || !$resQ->num_rows) continue;
		$rowQ = $resQ->fetch_assoc();
		if ((int)$rowQ['prova'] !== $idProva) continue;

		$altCorreta = strtoupper(trim((string)$rowQ['alt_corre']));
		$isCorreta	= (int) ($altMarcada === $altCorreta);

		$stmtInsertResp->bind_param("iisi", $idTentativa, $idQ, $altMarcada, $isCorreta);
		if (!$stmtInsertResp->execute()) {
            throw new Exception("Falha ao registrar respostas.");
        }
	}
	
	// 4.3) DELETAR O RASCUNHO APÓS O ENVIO
	$sqlDeleteRascunho = "DELETE FROM tb_rascunho WHERE id_usuario = ? AND id_prova = ?";
    $stmtDeleteRascunho = $conn->prepare($sqlDeleteRascunho);
    $stmtDeleteRascunho->bind_param("ii", $idUsuario, $idProva);
    $stmtDeleteRascunho->execute();
    $stmtDeleteRascunho->close();

	// 4.4) COMMIT
	$conn->commit();

	// 5) Retorna SUCESSO JSON (CRUCIAL PARA O FLUXO AJAX)
    $conn->close();
	http_response_code(200);
	header('Content-Type: application/json');
	echo json_encode([
        'status' => 'success',
        'message' => 'Prova enviada com sucesso!',
        'idTentativa' => $idTentativa // O ID é usado para o redirecionamento JS
    ]);
	exit();

} catch (mysqli_sql_exception $e) {
	if ($conn && $conn->thread_id) {
		@$conn->rollback();
	}
    returnJsonError("Ocorreu um erro crítico ao salvar: " . $e->getMessage(), 500, $idProva);

} catch (Exception $e) {
    returnJsonError("Houve um problema interno: " . $e->getMessage(), 500, $idProva);
}
?>
