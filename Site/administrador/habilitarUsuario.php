<?php
// habilitarUsuario.php — atualizado com codigoEscola automático e único
session_start();
if (!isset($_SESSION['id'])) { header("Location: ../login.php"); exit(); }

require_once '../conexao/conecta.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// --- util: escape
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// --- planos permitidos
$PLANOS_VALIDOS = ['Essencial','Pro','Premiun'];

// --- entrada
$idUsuario = filter_input(INPUT_GET, 'idUsuario', FILTER_VALIDATE_INT);
$planoAssinado = filter_input(INPUT_GET, 'plano', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (!$idUsuario) { http_response_code(400); echo "Parâmetro 'idUsuario' inválido."; exit(); }
if (!in_array($planoAssinado, $PLANOS_VALIDOS, true)) { $planoAssinado = 'Essencial'; }

// --- busca usuário
$stmt = $conn->prepare("SELECT id, nomeCompleto, email, telefone, tipoUsuario, plano FROM tb_usuario WHERE id = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$usuario) { http_response_code(404); echo "Usuário não encontrado."; exit(); }

// --- garante existência da tabela tb_escola (opcional, caso ainda não tenha rodado o SQL)
$conn->query("
CREATE TABLE IF NOT EXISTS tb_escola (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nome VARCHAR(150) NOT NULL,
  codigoEscola VARCHAR(30) NOT NULL,
  plano ENUM('Essencial','Pro','Premiun') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_codigoEscola (codigoEscola)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// --- gerador de codigoEscola único (ex.: 8 chars A-Z0-9)
function gerarCodigoEscola(mysqli $conn, int $len = 8): string {
	$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sem 0,O,1,I para evitar confusão
	for ($attempt = 0; $attempt < 50; $attempt++) {
		$code = '';
		for ($i = 0; $i < $len; $i++) {
			$code .= $chars[random_int(0, strlen($chars)-1)];
		}
		// checa unicidade
		$stmt = $conn->prepare("SELECT 1 FROM tb_escola WHERE codigoEscola = ? LIMIT 1");
		$stmt->bind_param("s", $code);
		$stmt->execute();
		$exists = (bool)$stmt->get_result()->fetch_row();
		$stmt->close();
		if (!$exists) return $code;
	}
	// fallback raro
	return 'ESC'.time();
}

// --- código gerado para exibir (e enviar por hidden)
$codigoGerado = gerarCodigoEscola($conn);

// --- mensagens
$mensagemSucesso = null;
$mensagemErro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$tipoUsuario = $_POST['tipoUsuario'] ?? '';
	$tipoUsuario = in_array($tipoUsuario, ['Individual','Diretor'], true) ? $tipoUsuario : '';

	try {
		if ($tipoUsuario === 'Individual') {
			// Atualiza usuário: tipo + plano
			$stmt = $conn->prepare("UPDATE tb_usuario SET tipoUsuario = 'estudante', plano = 'Individual', statusPlano = 'habilitado' WHERE id = ?");
			$stmt->bind_param("i", $idUsuario);
			$stmt->execute();
			$stmt->close();

			// --- NOVO: Atualiza o status na tabela tb_assinaturas
			$stmt_assinatura = $conn->prepare("UPDATE tb_assinaturas SET status = 'habilitado' WHERE idUsuario = ?");
			$stmt_assinatura->bind_param("i", $idUsuario);
			$stmt_assinatura->execute();
			$stmt_assinatura->close();

			$mensagemSucesso = "Usuário habilitado como estudante com plano 'Individual'.";
		} elseif ($tipoUsuario === 'Diretor') {
			$nomeEscola   = trim($_POST['nomeEscola'] ?? '');
			$codigoEscola = trim($_POST['codigoEscola'] ?? '');  // vem do hidden
			$planoEscola  = $_POST['planoEscola'] ?? $planoAssinado;

			if ($nomeEscola === '' || $codigoEscola === '') {
				throw new RuntimeException("Informe nome e código da escola (o código é gerado automaticamente).");
			}
			if (!in_array($planoEscola, $PLANOS_VALIDOS, true)) {
				throw new RuntimeException("Plano inválido para a escola.");
			}

			// Insere escola (com retry se colidir o código)
			$maxRetries = 3;
			for ($i=0; $i<$maxRetries; $i++) {
				try {
					$stmt = $conn->prepare("INSERT INTO tb_escola (nome, codigoEscola, plano) VALUES (?, ?, ?)");
					$stmt->bind_param("sss", $nomeEscola, $codigoEscola, $planoEscola);
					$stmt->execute();
					$idEscola = $stmt->insert_id;
					$stmt->close();
					break;
				} catch (mysqli_sql_exception $ex) {
					if ($ex->getCode() == 1062 && $i < $maxRetries-1) {
						$codigoEscola = gerarCodigoEscola($conn);
						continue;
					}
					throw $ex;
				}
			}

			// Atualiza usuário: tipo + plano (AGORA TAMBÉM GRAVA O PLANO ESCOLHIDO)
		$stmt = $conn->prepare("UPDATE tb_usuario SET tipoUsuario = 'Diretor', plano = ?, statusPlano = 'habilitado', codigoEscola = ? WHERE id = ?");
		$stmt->bind_param("ssi", $planoEscola, $codigoEscola, $idUsuario); // Tipos e ordem corretos (string, string, integer)
			$stmt->execute();
			$stmt->close();

			// --- NOVO: Atualiza o status na tabela tb_assinaturas
			$stmt_assinatura = $conn->prepare("UPDATE tb_assinaturas SET status = 'habilitado', codigoEscola = ? WHERE idUsuario = ?");
			$stmt_assinatura->bind_param("si", $codigoEscola, $idUsuario);
			$stmt_assinatura->execute();
			$stmt_assinatura->close();

			$mensagemSucesso = "Usuário habilitado como Diretor. Escola #{$idEscola} criada com código '{$codigoEscola}' e plano '{$planoEscola}'.";
			// Dica futura: adicionar tb_usuario.idEscola para vincular o diretor à escola
		} else {
			throw new RuntimeException("Selecione um tipo de usuário válido.");
		}
	} catch (Throwable $e) {
		$mensagemErro = $e->getMessage();
	}
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Habilitar Usuário</title>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
	<style>
		:root{--pri:#0d4b9e;--pri-d:#0a3a7a;--bg:#f5f7fa;--txt:#222;--mut:#6b7280;--ok:#0e9f6e;--err:#e11d48;--br:#fff;--rad:12px;--sh:0 8px 20px rgba(0,0,0,.08)}
		*{box-sizing:border-box;margin:0;padding:0}
		body{font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--txt)}
		main{max-width:900px;margin:24px auto;padding:16px}
		h1{font-size:1.6rem;color:var(--pri);margin:4px 0 14px;font-weight:600;text-align:center}
		.card{background:var(--br);border-radius:var(--rad);box-shadow:var(--sh);padding:18px}
		.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
		@media(max-width:720px){.grid{grid-template-columns:1fr}}
		label{font-weight:600;font-size:.95rem;margin-bottom:6px;display:block}
		.input,.select{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;font-size:.95rem}
		.input[readonly], .input[disabled]{background:#f3f4f6}
		.row{display:flex;gap:10px;align-items:center}
		.badge{background:#eaf1ff;color:#0b2c5e;padding:4px 10px;border-radius:999px;font-weight:600;font-size:.8rem}
		.subtitle{color:var(--mut);font-size:.92rem;margin:-6px 0 10px}
		.actions{display:flex;gap:12px;justify-content:flex-end;margin-top:14px}
		.btn{border:0;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer}
		.btn-primary{background:var(--pri);color:#fff}
		.btn-primary:hover{background:var(--pri-d)}
		.btn-sec{background:#e5e7eb}
		.help{font-size:.85rem;color:var(--mut)}
		.alert{padding:10px 12px;border-radius:10px;margin-bottom:12px}
		.alert-ok{background:#ecfdf5;color:#064e3b;border:1px solid #a7f3d0}
		.alert-err{background:#fff1f2;color:#881337;border:1px solid #fecdd3}
		.section{margin-top:14px;padding-top:14px;border-top:1px dashed #e5e7eb}
	</style>
</head>
<body>
	<?php include 'menu.php'; ?>

	<main>
		<h1>Habilitar Usuário</h1>

		<div class="card">
			<?php if (!empty($mensagemSucesso)): ?>
				<div class="alert alert-ok"><i class="fa-solid fa-check-circle"></i> <?php echo e($mensagemSucesso); ?></div>
			<?php endif; ?>
			<?php if (!empty($mensagemErro)): ?>
				<div class="alert alert-err"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($mensagemErro); ?></div>
			<?php endif; ?>

			<div class="grid" style="margin-bottom:10px">
				<div>
					<label>Usuário</label>
					<input class="input" type="text" value="<?php echo e($usuario['nomeCompleto']); ?>" readonly>
				</div>
				<div>
					<label>E-mail</label>
					<input class="input" type="text" value="<?php echo e($usuario['email']); ?>" readonly>
				</div>
				<div>
					<label>Telefone</label>
					<input class="input" type="text" value="<?php echo e($usuario['telefone']); ?>" readonly>
				</div>
				<div>
					<label>Status atual</label>
					<input class="input" type="text" value="Tipo: <?php echo e($usuario['tipoUsuario'] ?: '—'); ?> | Plano: <?php echo e($usuario['plano'] ?: '—'); ?>" readonly>
				</div>
			</div>

			<form method="post" action="">
				<input type="hidden" name="idUsuario" value="<?php echo (int)$idUsuario; ?>">

				<div class="grid">
					<div>
						<label for="tipoUsuario">Tipo de Usuário</label>
						<select id="tipoUsuario" name="tipoUsuario" class="select" required>
							<option value="" disabled selected>Selecione...</option>
							<option value="Individual">Individual</option>
							<option value="Diretor">Diretor</option>
						</select>
						<p class="help">Escolha <b>Individual</b> para acesso pessoal, ou <b>Diretor</b> para cadastrar uma escola com plano.</p>
					</div>

					<div id="wrap-plano-individual" style="display:none">
						<label>Plano (Individual)</label>
						<input class="input" type="text" value="Individual" readonly>
						<p class="help">Ao confirmar, o plano do usuário será definido como <b>Individual</b>.</p>
					</div>
				</div>

				<div id="section-diretor" class="section" style="display:none">
					<p class="subtitle"><i class="fa-solid fa-school"></i> Dados da Escola</p>
					<div class="grid">
						<div>
							<label for="nomeEscola">Nome da Escola</label>
							<input id="nomeEscola" name="nomeEscola" class="input" type="text" placeholder="Ex.: ETEC Orlando Quagliato">
						</div>

						<div>
							<label for="codigoEscola_view">Código da Escola (gerado)</label>
							<input id="codigoEscola_view" class="input" type="text" value="<?php echo e($codigoGerado); ?>" disabled>
							<input type="hidden" id="codigoEscola" name="codigoEscola" value="<?php echo e($codigoGerado); ?>">
							<p class="help">Gerado automaticamente e garantido único no momento do cadastro.</p>
						</div>

						<div>
							<label for="planoEscola">Plano da Escola</label>
							<select id="planoEscola" name="planoEscola" class="select">
								<?php
								foreach ($PLANOS_VALIDOS as $p) {
									$sel = ($p === $planoAssinado) ? 'selected' : '';
									echo "<option value=\"".e($p)."\" $sel>".e($p)."</option>";
								}
								?>
							</select>
							<p class="help">Pré-selecionado pelo plano da assinatura (GET), mas pode ser alterado.</p>
						</div>

						<div>
							<label>Resumo</label>
							<div class="row"><span class="badge">Assinado: <?php echo e($planoAssinado); ?></span></div>
						</div>
					</div>
				</div>

				<div class="actions">
					<a class="btn btn-sec" href="javascript:history.back()"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
					<button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Habilitar</button>
				</div>
			</form>
		</div>
	</main>

	<footer style="text-align:center;color:#6b7280;font-size:.9rem;margin:18px 0">
		&copy; 2025 Caminho do Saber — <a href="POLITICA.php" style="color:#D4AF37;text-decoration:none">Política de privacidade</a>
	</footer>

	<script>
		const tipo = document.getElementById('tipoUsuario');
		const secDir = document.getElementById('section-diretor');
		const wrapInd = document.getElementById('wrap-plano-individual');
		function toggleSections(){
			const v = tipo.value;
			secDir.style.display = (v === 'Diretor') ? '' : 'none';
			wrapInd.style.display = (v === 'Individual') ? '' : 'none';
			document.getElementById('nomeEscola').required = (v === 'Diretor');
			document.getElementById('codigoEscola').required = (v === 'Diretor'); // hidden
			document.getElementById('planoEscola').required = (v === 'Diretor');
		}
		tipo.addEventListener('change', toggleSections);

		<?php if (isset($_GET['tipo']) && $_GET['tipo'] === 'Diretor'): ?>
		document.addEventListener('DOMContentLoaded', () => { tipo.value = 'Diretor'; toggleSections(); });
		<?php endif; ?>
	</script>
</body>
</html>