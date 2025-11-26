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

// 2. Inclua o arquivo de conexão com o banco de dados
// Certifique-se de que o caminho está correto
require_once __DIR__ . '/../conexao/conecta.php'; 

// 3. Verifique se o tipo de usuário tem permissão para acessar a página
// Se esta página de configurações for para todos os usuários, a validação abaixo é desnecessária.
// Caso contrário, ajuste-a para os perfis permitidos.
// Exemplo: se apenas diretores e administradores podem editar configurações de usuários, mantenha a validação.
// Se cada usuário edita suas próprias configurações, remova este bloco.
$tipoUsuarioSessao = strtolower(trim($_SESSION['tipoUsuario'] ?? ''));
if ($tipoUsuarioSessao !== 'diretor' && $tipoUsuarioSessao !== 'administrador') {
     session_destroy();
     header("Location: /login.php?acessoNegado");
     exit();
}

$id = $_SESSION['id'];

// Regra simples de autenticação (ajuste se necessário)
$isLoggedIn = isset($_SESSION['id']) && (int)$_SESSION['id'] > 0;
// Opcional: exibir nome se existir
$userName = isset($_SESSION['nome']) ? trim($_SESSION['nome']) : '';
require_once __DIR__ . '/config.php';
?>
<!-- MENU/HEADER GLOBAL -->
<style>
	/* === Variáveis do tema (mantenha aqui se não tiver um CSS global) === */
	:root {
		--azul-primario: #0d4b9e;
		--azul-claro: rgba(13, 75, 158, 0.5);
		--azul-escuro: #0a3a7a;
		--gold-color: #D4AF37;
		--branco: #ffffff;
		--preto: #333333;
		--destaque: #1283c5;
		--sombra: 0 4px 12px rgba(0, 0, 0, 0.1);
		--transicao: all 0.3s ease;
		--borda-arredondada: 8px;
	}

	/* ====== Header ====== */
	header.cs-header {
		position: fixed;
		top: 0; left: 0; width: 100%;
		background: var(--azul-claro);
		padding: 15px 0;
		backdrop-filter: blur(8px);
		z-index: 1000;
		box-shadow: var(--sombra);
		border-bottom: 4px solid var(--gold-color);
	}

	.cs-container {
		width: 90%;
		max-width: 1200px;
		margin: 0 auto;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.cs-logo img { height: 60px; transition: var(--transicao); }
	.cs-logo img:hover { transform: scale(1.05); }

	.cs-title {
		font-family: 'Merriweather', serif;
		font-weight: 700;
		font-size: 1.8rem;
		color: var(--branco);
		text-align: center;
		flex-grow: 1;
		margin: 0 20px;
		text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
	}

	.cs-buttons { display: flex; gap: 15px; }

	.cs-btn {
		background-color: var(--branco);
		color: var(--azul-primario);
		padding: 10px 20px;
		border-radius: 30px;
		font-weight: 600;
		border: 2px solid transparent;
		transition: var(--transicao);
		cursor: pointer;
		text-decoration: none;
		display: inline-flex;
		align-items: center;
		gap: 8px;
	}
	.cs-btn:hover {
		background-color: transparent;
		border-color: var(--branco);
		color: var(--branco);
		transform: translateY(-2px);
	}

	/* Alinhamento à direita (botões + user menu) */
	.cs-header-right {
		display: flex;
		align-items: center;
		gap: 20px;
	}

	/* ====== Menu do usuário ====== */
	.cs-user-menu { position: relative; margin-left: 10px; }

	.cs-user-toggle {
		display: flex; align-items: center; justify-content: center;
		width: 44px; height: 44px; padding: 0;
		border-radius: 50%;
		background-color: var(--branco);
		color: var(--azul-primario);
		border: 2px solid transparent;
		transition: var(--transicao);
		cursor: pointer;
	}
	.cs-user-toggle:hover {
		background-color: transparent;
		border-color: var(--branco);
		color: var(--branco);
		transform: translateY(-2px);
	}
	.cs-user-toggle i { font-size: 1.2rem; }

	.cs-user-dropdown {
		display: none;
		position: absolute; right: 0; top: 50px;
		background-color: var(--branco);
		min-width: 220px;
		box-shadow: var(--sombra);
		border-radius: var(--borda-arredondada);
		z-index: 1000;
		overflow: hidden;
		border: 1px solid rgba(13,75,158,0.1);
		animation: csFadeIn .25s ease-out;
	}
	.cs-user-dropdown.cs-show { display: block; }

	.cs-user-dropdown a,
	.cs-user-dropdown button {
		display: flex; align-items: center; gap: 10px;
		padding: 12px 15px;
		color: var(--preto);
		text-decoration: none;
		transition: var(--transicao);
		font-size: 0.95rem;
		background: transparent;
		border: 0;
		width: 100%;
		text-align: left;
		cursor: pointer;
	}
	.cs-user-dropdown a:hover,
	.cs-user-dropdown button:hover {
		background-color: var(--azul-claro);
		color: var(--azul-primario);
	}
	.cs-user-dropdown i { width: 20px; text-align: center; color: var(--azul-primario); }

	/* Badge com nome (opcional) */
	.cs-user-name {
		color: var(--branco);
		font-size: 0.95rem;
		font-weight: 600;
		white-space: nowrap;
		max-width: 240px;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	@keyframes csFadeIn {
		from { opacity: 0; transform: translateY(6px); }
		to   { opacity: 1; transform: translateY(0); }
	}

	/* Responsividade */
	@media (max-width: 768px) {
		.cs-container { flex-direction: column; text-align: center; }
		.cs-logo img { margin-bottom: 12px; }
		.cs-title { margin: 10px 0; font-size: 1.5rem; }
		.cs-header-right { flex-direction: column; gap: 10px; }
		.cs-user-dropdown { right: auto; left: 0; }
	}
</style>

<!-- Fontes/Ícones necessários (apenas injeta se ainda não houver no DOM) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php
// Evita duplicar as folhas se já existirem em <head> da página
?>
<link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

<header class="cs-header">
	<div class="cs-container">
		<div class="cs-logo">
			<a href="index.php" aria-label="Ir para a página inicial">
				<img src="<?= BASE_URL ?>imagem/logonova.png" alt="Logo Caminho do Saber">
			</a>
		</div>

		<h1 class="cs-title">CAMINHO DO SABER</h1>

		<div class="cs-header-right">
			<?php if (!$isLoggedIn): ?>
				<div class="cs-buttons" role="navigation" aria-label="Acesso">
					<a href="<?= BASE_URL ?>login.php" class="cs-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
					<a href="<?= BASE_URL ?>cadastro.html" class="cs-btn"><i class="fas fa-user-plus"></i> Cadastrar-se</a>
				</div>
			<?php else: ?>
				<!-- Opcional: Nome do usuário -->
				<?php if ($userName !== ''): ?>
					<div class="cs-user-name" title="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>">
						<i class="fas fa-user-circle" style="margin-right:6px;"></i>
						<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
					</div>
				<?php endif; ?>

				<!-- Menu do usuário -->
				<div class="cs-user-menu">
					<button class="cs-user-toggle" id="csUserToggle" aria-haspopup="true" aria-expanded="false" aria-controls="csUserDropdown">
						<i class="fas fa-bars" aria-hidden="true"></i>
						<span class="sr-only">Abrir menu do usuário</span>
					</button>
					<div class="cs-user-dropdown" id="csUserDropdown" role="menu" aria-label="Menu do usuário">
						<a href="home.php" role="menuitem"><i class="fas fa-home"></i> Home</a>
						<a href="exibirProvas.php" role="menuitem"><i class="fas fa-clipboard-list"></i> Provas</a>
						<a href="corretor.php" role="menuitem"><i class="fas fa-pen-fancy"></i> Corretor</a>
						<a href="progresso.php" role="menuitem"><i class="fas fa-chart-line"></i> Progresso</a>
						<a href="relatorioDiretor.php" role="menuitem"><i class="fas fa-chart-bar"></i> Relatórios</a>
						<a href="gerenciarAlunos.php" role="menuitem"><i class="fas fa-users-cog"></i> Gerenciar Alunos</a>
						<a href="configuracoes.php" role="menuitem"><i class="fas fa-cog"></i> Configurações</a>
						<hr style="margin:6px 0;border:0;border-top:1px solid rgba(13,75,158,0.15)">
						<a href="sair.php" role="menuitem"><i class="fas fa-sign-out-alt"></i> Sair</a>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</header>

<script>
	// menu.js (escopo isolado)
	(() => {
		const toggle = document.getElementById('csUserToggle');
		const dropdown = document.getElementById('csUserDropdown');

		if (toggle && dropdown) {
			toggle.addEventListener('click', (e) => {
				e.stopPropagation();
				const isOpen = dropdown.classList.toggle('cs-show');
				toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			});

			document.addEventListener('click', () => {
				if (dropdown.classList.contains('cs-show')) {
					dropdown.classList.remove('cs-show');
					toggle.setAttribute('aria-expanded', 'false');
				}
			});

			// Fecha com ESC
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && dropdown.classList.contains('cs-show')) {
					dropdown.classList.remove('cs-show');
					toggle.setAttribute('aria-expanded', 'false');
					toggle.focus();
				}
			});
		}

		// Compensa header fixo no layout: se a página não tiver padding-top, aplica um mínimo
		try {
			const firstMain = document.querySelector('main');
			const computedPT = firstMain ? parseInt(getComputedStyle(firstMain).paddingTop, 10) : 0;
			if (firstMain && (isNaN(computedPT) || computedPT < 120)) {
				firstMain.style.paddingTop = '120px';
			}
		} catch(_) {}
	})();
</script>
<!-- /MENU/HEADER GLOBAL -->
