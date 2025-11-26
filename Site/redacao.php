<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$idUsuario = (int)$_SESSION['id'];
$planoUsuario = isset($_SESSION['planoUsuario']) ? $_SESSION['planoUsuario'] : 'Basico'; // padrão: Basico

require_once 'conexao/conecta.php';

/* ============================
    LIMITES POR PLANO (REDAÇÕES)
============================ */
// Define o limite de redações por semana para cada plano.
// null = ilimitado
$limitesRedacao = [
    'Basico' => 1,
    'Individual' => 3,
    'Essencial' => 3,
    'Pro' => 5,
    'Premium' => null,
    'EscolaPublica' => null
];

$limiteSemanalRedacoes = $limitesRedacao[$planoUsuario] ?? null;

// A verificação é feita apenas para planos com limite definido (não nulo)
if (!is_null($limiteSemanalRedacoes)) {
    // contar tentativas na semana atual
    $sqlLimite = "
        SELECT COUNT(*) AS total
        FROM tb_redacao
        WHERE idUsuario = ?
        AND YEARWEEK(dataRedacao, 1) = YEARWEEK(CURDATE(), 1)
    ";
    $stmt = $conn->prepare($sqlLimite);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $qtdRedacoesSemana = (int)$res['total'];

    if ($qtdRedacoesSemana >= $limiteSemanalRedacoes) {
        echo "<div style='max-width:600px;margin:40px auto;
                    background:#fff3f3;border:1px solid #f5c2c7;
                    color:#842029;padding:18px;border-radius:12px;
                    font-family:sans-serif;text-align:center'>
                <h2 style='margin-bottom:10px'>Limite de Redações Atingido 🚫</h2>
                <p>O seu plano <b>" . ucfirst($planoUsuario) . "</b> permite enviar até
                    <b>{$limiteSemanalRedacoes} redações por semana</b>.</p>
                <p>Você já atingiu esse limite. Para continuar, aguarde a próxima semana
                    ou <a href='configuracao/configuracoes.php?tab=plans' style='color:#0d6efd;font-weight:600'>faça upgrade de plano</a>.</p>
              </div>";
        exit();
    }
}

if (!isset($_SESSION['id'])) {
	header("Location: login.html");
	exit();
}

// O código abaixo é o restante do seu arquivo redacao.php que foi movido para cá
// Não foi alterado, apenas movido para a posição correta após a verificação do limite

$idUsuario    = (int)$_SESSION['id'];
$temaRedacao  = isset($_POST['temaRedacao'])   ? trim($_POST['temaRedacao'])   : '';
$redacao      = isset($_POST['redacao'])       ? trim($_POST['redacao'])       : '';
$tituloRed    = isset($_POST['tituloRedacao']) ? trim($_POST['tituloRedacao']) : ''; // opcional
$aderenciaCli = isset($_POST['aderenciaCliente']) ? trim($_POST['aderenciaCliente']) : ''; // opcional (0..1)

// ===================
// Validações mínimas
// ===================
if ($temaRedacao === '' || $redacao === '') {
	die('Tema e redação são obrigatórios.');
}

// Normaliza aderência do cliente caso venha como string/percentual
if ($aderenciaCli !== '') {
	if (substr($aderenciaCli, -1) === '%') {
		$aderenciaCli = rtrim($aderenciaCli, '%');
		$aderenciaCli = is_numeric($aderenciaCli) ? (float)$aderenciaCli / 100.0 : null;
	} else {
		$aderenciaCli = is_numeric($aderenciaCli) ? (float)$aderenciaCli : null;
		if ($aderenciaCli !== null && $aderenciaCli > 1.0) {
			$aderenciaCli = $aderenciaCli / 100.0;
		}
	}
	if ($aderenciaCli !== null) {
		$aderenciaCli = max(0.0, min(1.0, $aderenciaCli));
	}
} else {
	$aderenciaCli = null;
}

// ===================
// Chamada à API (cURL)
// ===================
$api_url = 'https://caminhodosaber.pythonanywhere.com/corrigir-redacao'; // ajuste se necessário

$payload = [
	'texto' => $redacao,
	'tema'  => $temaRedacao,
];

if ($tituloRed !== '') {
	$payload['titulo'] = $tituloRed;
}
if ($aderenciaCli !== null) {
	$payload['aderencia_cliente'] = $aderenciaCli;
}

$json_payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init($api_url);
curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_TIMEOUT        => 15,
	CURLOPT_POST           => true,
	CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
	CURLOPT_POSTFIELDS     => $json_payload,
]);

$result   = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($result === false) {
	die('Erro ao chamar a API de correção: ' . $curlErr);
}
if ($httpCode < 200 || $httpCode >= 300) {
	$maybe = json_decode($result, true);
	$msg   = is_array($maybe) && isset($maybe['error']) ? $maybe['error'] : $result;
	die('A API retornou erro HTTP ' . $httpCode . ': ' . $msg);
}

// ===================
// Extrair dados
// ===================
$response = json_decode($result, true);
if (!is_array($response)) {
	die('Resposta da API inválida.');
}

// Garante que a resposta tenha aderencia_cliente (preferida no front)
if ($aderenciaCli !== null && !isset($response['aderencia_cliente'])) {
	$response['aderencia_cliente'] = $aderenciaCli;
}

// Nota final
$notaFinalFloat = isset($response['nota_final']) ? (float)$response['nota_final'] : 0.0;
$notaRedacao    = (int) round($notaFinalFloat);

// ===================
// Compactar JSON antes de salvar
// ===================
// - Remove campos pesados/desnecessários para o front (evita truncamento no BD).
// - Mantém 'criterios' porque a interface exibe descrições quando disponíveis.
$respCompacto = $response;

// Remove campos grandes/diagnósticos que não são usados no front
unset(
	$respCompacto['texto'],
	$respCompacto['debug_tema_aux']
);

// (Opcional) Caso queira reduzir ainda mais em algum momento, você pode:
// unset($respCompacto['criterios']); // se parar de exibir descrições no front

$detalhesJson = json_encode($respCompacto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($detalhesJson === false) {
	// Fallback de segurança
	$detalhesJson = '{}';
}

// ===================
// Preparar dados auxiliares para colunas opcionais
// ===================
$notas = (isset($response['notas']) && is_array($response['notas'])) ? $response['notas'] : [];
$nota_c1 = isset($notas['competencia_1']) ? (int)$notas['competencia_1'] : 0;
$nota_c2 = isset($notas['competencia_2']) ? (int)$notas['competencia_2'] : 0;
$nota_c3 = isset($notas['competencia_3']) ? (int)$notas['competencia_3'] : 0;
$nota_c4 = isset($notas['competencia_4']) ? (int)$notas['competencia_4'] : 0;
$nota_c5 = isset($notas['competencia_5']) ? (int)$notas['competencia_5'] : 0;

$comentariosMin = isset($response['comentarios']) && is_array($response['comentarios'])
	? json_encode($response['comentarios'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	: null;

// ===================
// Inserir no banco (dinâmico)
// ===================

// Data/hora em DATETIME para melhor ordenação
$dataRedacao = date('Y-m-d H:i:s');

/**
 * Verifica se a coluna existe na tabela (para insert dinâmico sem quebrar).
 */
function colunaExiste(mysqli $conn, string $tabela, string $coluna): bool {
	$db = $conn->real_escape_string($conn->query("SELECT DATABASE()")->fetch_row()[0]);
	$tab = $conn->real_escape_string($tabela);
	$col = $conn->real_escape_string($coluna);

	$sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
	        WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='{$tab}' AND COLUMN_NAME='{$col}' LIMIT 1";
	$rs  = $conn->query($sql);
	return $rs && $rs->num_rows > 0;
}

$temColTitulo      = colunaExiste($conn, 'tb_redacao', 'tituloRedacao');
$temColNotaC1      = colunaExiste($conn, 'tb_redacao', 'nota_c1');
$temColNotaC2      = colunaExiste($conn, 'tb_redacao', 'nota_c2');
$temColNotaC3      = colunaExiste($conn, 'tb_redacao', 'nota_c3');
$temColNotaC4      = colunaExiste($conn, 'tb_redacao', 'nota_c4');
$temColNotaC5      = colunaExiste($conn, 'tb_redacao', 'nota_c5');
$temColComentarios = colunaExiste($conn, 'tb_redacao', 'comentarios_json');

// Monta INSERT dinâmico
$cols = [];
$ph   = [];
$types= '';
$params = [];

// Helpers para montar bind dinâmico
$add = function($col, $type, $val) use (&$cols, &$ph, &$types, &$params) {
	$cols[] = $col;
	$ph[]   = '?';
	$types .= $type;
	$params[] = $val;
};

// Colunas padrão
if ($temColTitulo) {
	$add('tituloRedacao', 's', $tituloRed);
}
$add('temaRedacao',  's', $temaRedacao);
$add('redacao',      's', $redacao);
$add('notaRedacao',  'i', $notaRedacao);
$add('errosRedacao', 's', $detalhesJson);
$add('dataRedacao',  's', $dataRedacao);
$add('idUsuario',    'i', $idUsuario);

// Colunas opcionais de notas por competência
if ($temColNotaC1) $add('nota_c1', 'i', $nota_c1);
if ($temColNotaC2) $add('nota_c2', 'i', $nota_c2);
if ($temColNotaC3) $add('nota_c3', 'i', $nota_c3);
if ($temColNotaC4) $add('nota_c4', 'i', $nota_c4);
if ($temColNotaC5) $add('nota_c5', 'i', $nota_c5);

// Coluna opcional de comentários compactos
if ($temColComentarios) {
	$add('comentarios_json', 's', $comentariosMin ?? null);
}

// Monta SQL final
$sql = sprintf(
	"INSERT INTO tb_redacao (%s) VALUES (%s)",
	implode(', ', $cols),
	implode(', ', $ph)
);

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
	die('Erro no prepare: ' . mysqli_error($conn));
}

// bind_param dinâmico
mysqli_stmt_bind_param($stmt, $types, ...$params);

if (mysqli_stmt_execute($stmt)) {
	$_SESSION['correcao'] = $respCompacto; // opcional: usar na próxima tela
	header("Location: corretor.php");
	exit();
} else {
	echo "Erro ao cadastrar: " . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);