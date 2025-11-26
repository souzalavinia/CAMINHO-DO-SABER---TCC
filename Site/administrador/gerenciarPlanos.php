<?php
// Inicie a sessão ANTES de qualquer saída
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verifique se o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

// 2. Converta o tipo de usuário para minúsculas e remova espaços
$tipoUsuarioSessao = strtolower(trim($_SESSION['tipoUsuario'] ?? ''));

// 3. Verifique se o tipo de usuário tem permissão para acessar a página
// Neste exemplo, a página é restrita a 'diretor' e 'administrador'.
// Adapte a lógica conforme a necessidade de cada página.
if ($tipoUsuarioSessao !== 'administrador') {
    // Se o usuário não tiver a permissão necessária,
    // a sessão é destruída e ele é redirecionado para o login com uma mensagem de negação.
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
}

// A partir daqui, o código só será executado se o usuário estiver logado
// e tiver o tipo de permissão correto (diretor ou administrador).
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title>Gerenciar Planos - Caminho do Saber</title>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
	<style>
		:root{
			--pri:#0d4b9e;--pri-d:#0a3a7a;--gold:#D4AF37;--txt:#212529;--bg:#f5f7fa;--mut:#6c757d;
			--br:#fff;--rad:12px;--sh:0 6px 18px rgba(0,0,0,.08);--tr:.2s ease;
		}
		*{box-sizing:border-box;margin:0;padding:0}
		body{font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--txt);line-height:1.5}
		main{padding:24px 16px;max-width:1200px;margin:0 auto}
		/* evita sobreposição do menu/header reutilizável */
		@media (min-width:0){ main{margin-top:16px} }

		h1{font-size:1.75rem;color:var(--pri);font-weight:600;text-align:center;margin:8px 0 20px}

		/* Caixa da lista */
		.list-wrap{background:var(--br);border-radius:var(--rad);box-shadow:var(--sh);overflow:hidden}
		.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #e9ecef}
		.toolbar .hint{font-size:.9rem;color:var(--mut)}
		.table{width:100%;border-collapse:collapse}
		.table thead{background:#eef4fb}
		.table th,.table td{padding:12px 14px;text-align:left;border-bottom:1px solid #edf0f3;vertical-align:middle}
		.table th{font-weight:600;font-size:.92rem;color:#0b2c5e}
		.table td{font-size:.95rem}
		.badge{display:inline-block;padding:4px 10px;border-radius:999px;background:#eaf1ff;color:#0b2c5e;font-size:.8rem;font-weight:600}
		.small{color:var(--mut);font-size:.88rem}
		.actions a{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:10px;text-decoration:none;background:var(--pri);color:#fff;font-weight:600;transition:transform var(--tr),opacity var(--tr)}
		.actions a:hover{transform:translateY(-1px);opacity:.9}
		.actions a i{font-size:.95rem}
		/* Responsivo: empilha colunas no mobile */
		@media (max-width:820px){
			.table thead{display:none}
			.table tr{display:grid;grid-template-columns:1fr;gap:8px;padding:12px 14px;border-bottom:1px solid #edf0f3}
			.table td{border:none;padding:0}
			.row-head{display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between}
			.row-meta{display:grid;grid-template-columns:1fr;gap:6px;margin-top:6px}
			.actions{margin-top:10px}
		}
		/* Links de política/rodapé simples */
		footer{margin:28px auto 8px;text-align:center;color:var(--mut);font-size:.9rem}
		footer a{color:var(--gold);text-decoration:none}
		footer a:hover{text-decoration:underline}
	</style>
</head>
<body>
	<?php include 'menu.php'; ?>

	<main>
		<h1>Gerenciamento de Planos</h1>

		<div class="list-wrap">
			<div class="toolbar">
				<strong>Assinaturas</strong>
				<span class="hint">Clique em “Habilitar Assinatura” para enviar os dados ao cadastro de usuário.</span>
			</div>

			<table class="table" role="table" aria-label="Lista de assinaturas">
				<thead>
					<tr>
						<th style="min-width:180px">Plano</th>
						<th style="min-width:220px">Assinante</th>
						<th style="min-width:220px">Contato</th>
						<th>Descrição</th>
						<th style="width:150px">Criado em</th>
						<th style="width:1%">Ação</th>
					</tr>
				</thead>
				<tbody>
					<?php
					require_once '../conexao/conecta.php';
					if ($conn->connect_error) { die("Conexão falhou: " . $conn->connect_error); }

					$sql = "SELECT a.id, a.nomePlano, a.nomeUsuario, a.emailUsuario, a.telefoneUsuario, 
								a.descricaoPlano, a.data_criacao, a.idUsuario,
								a.status
							FROM tb_assinaturas a
							ORDER BY a.data_criacao DESC";
					$result = $conn->query($sql);

					if ($result && $result->num_rows > 0) {
						while ($row = $result->fetch_assoc()) {
							$plano   = $row['nomePlano'] ?? '';
							$nome    = $row['nomeUsuario'] ?? '';
							$email   = $row['emailUsuario'] ?? '';
							$tel     = $row['telefoneUsuario'] ?? '';
							$desc    = $row['descricaoPlano'] ?? '';
							$data    = $row['data_criacao'] ?? '';
							$idUsuario   = $row['idUsuario'] ?? '';
							$status = $row['status'] ?? '';

							// Monta URL para habilitar
							$params = http_build_query([
								'nome'     => $nome,
								'telefone' => $tel,
								'email'    => $email,
								'plano'    => $plano,
								'idUsuario'=> $idUsuario,
							]);
							$href = "habilitarUsuario.php?".$params;

							// Escapes
							$plano_e = htmlspecialchars($plano);
							$nome_e  = htmlspecialchars($nome);
							$email_e = htmlspecialchars($email);
							$tel_e   = htmlspecialchars($tel);
							$desc_e  = htmlspecialchars($desc);
							$data_e  = $data ? date('d/m/Y H:i', strtotime($data)) : '';

							echo '<tr>';
							echo '  <td><span class="badge">'.$plano_e.'</span></td>';
							echo '  <td>';
							echo '      <div>'.$nome_e.'</div>';
							echo '      <div class="small">ID #'.(int)$row['id'].'</div>';
							echo '  </td>';
							echo '  <td>';
							echo '      <div>'.$email_e.'</div>';
							echo '      <div class="small">'.$tel_e.'</div>';
							echo '  </td>';
							echo '  <td>'.$desc_e.'</td>';
							echo '  <td>'.$data_e.'</td>';
							echo '  <td class="actions">';
							
							if (strtolower($status) === 'habilitado') {
								// Mostra botão desativado (mesma aparência, mas sem link)
								echo '<a style="pointer-events:none;opacity:.5;cursor:not-allowed">';
								echo '  <i class="fa-solid fa-circle-check"></i> Já Habilitado';
								echo '</a>';
							} else {
								// Mostra botão normal
								echo '<a href="'.$href.'" title="Habilitar Assinatura">';
								echo '  <i class="fa-solid fa-user-plus"></i> Habilitar Assinatura';
								echo '</a>';
							}

							echo '  </td>';
							echo '</tr>';
						}
					} else {
						echo '<tr><td colspan="6" class="small" style="text-align:center;padding:18px">Nenhuma assinatura encontrada.</td></tr>';
					}
					$conn->close();
					?>

				</tbody>
			</table>
		</div>
	</main>

	<footer>
		<p>&copy; 2025 Caminho do Saber. Todos os direitos reservados. <a href="POLITICA.php">Política de privacidade</a></p>
	</footer>

	<script>
		// Toggle do menu do usuário (se existir no menu.php)
		const t = document.getElementById('userToggle');
		const d = document.getElementById('userDropdown');
		if(t && d){
			t.addEventListener('click', e => { e.stopPropagation(); d.classList.toggle('show'); });
			window.addEventListener('click', () => d.classList.remove('show'));
		}
	</script>
</body>
</html>
