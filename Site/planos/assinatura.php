<?php
session_start();

if (!isset($_SESSION['id'])) {
	header("Location: ../login.php");
	exit();
}

$plano = isset($_GET['plano']) ? $_GET['plano'] : '';

// Definir detalhes do plano com base na seleção
$planos = [
	'individual' => ['nome' => 'Individual', 'preco' => 'R$ 10,99', 'periodo' => 'mês', 'descricao' => 'Plano Individual com acesso básico, a partir de R$10,99'],
	'essencial'  => ['nome' => 'Essencial',  'preco' => 'R$ 499,99',  'periodo' => 'mês', 'descricao' => 'Plano Essencial para pequenas escolas, a partir de R$ 499,99'],
	'pro'        => ['nome' => 'Pro',        'preco' => 'R$ 1.290,99','periodo' => 'mês', 'descricao' => 'Plano Pro para escolas médias, a partir de R$ 1.290,99'],
	'premium'    => ['nome' => 'Premium',    'preco' => 'R$ 2.990,99','periodo' => 'mês', 'descricao' => 'Plano Premium para grandes instituições, a partir de R$ 2.990,99'],
	'gratuito'   => ['nome' => 'Gratuito',   'preco' => 'R$ 0,00',    'periodo' => 'sem custo', 'descricao' => 'Plano Gratuito com funcionalidades básicas']
];

$detalhesPlano = isset($planos[$plano]) ? $planos[$plano] : $planos['essencial'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Assinatura - Caminho do Saber</title>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />

	<style>
		:root{
			--primary-color:#0d4b9e;
			--primary-dark:#0a3a7a;
			--primary-light:#3a6cb5;
			--gold-color:#D4AF37;
			--gold-dark:#996515;
			--black:#212529;
			--white:#ffffff;
			--light-gray:#f5f7fa;
			--medium-gray:#e0e5ec;
			--dark-gray:#6c757d;
			--radius:12px;
			--shadow:0 10px 30px rgba(0,0,0,.10);
			--shadow-lg:0 16px 40px rgba(0,0,0,.15);
			--transition:all .25s cubic-bezier(.25,.8,.25,1);
		}

		*{box-sizing:border-box;margin:0;padding:0}
		html,body{height:100%}
		body{
			font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans","Poppins",sans-serif;
			background:var(--light-gray);
			color:var(--black);
			line-height:1.55;
		}

		/* Voltar */
		.back-button{
			position:fixed; top:18px; left:18px;
			display:inline-flex; align-items:center; gap:.5rem;
			background:linear-gradient(to right,var(--primary-color),var(--primary-dark));
			color:var(--white); text-decoration:none; font-weight:600;
			padding:10px 14px; border-radius:999px; box-shadow:var(--shadow);
			transition:var(--transition); z-index:10;
		}
		.back-button:hover{ transform:translateY(-2px); box-shadow:var(--shadow-lg) }

		/* Container principal */
		.payment-container{
			max-width:1100px; margin:90px auto 40px; padding:0 20px;
			display:grid; grid-template-columns:1.1fr 1fr; gap:28px;
		}
		@media (max-width:980px){
			.payment-container{ grid-template-columns:1fr }
		}

		/* Cards base */
		.card{
			background:var(--white); border-radius:var(--radius);
			box-shadow:var(--shadow); border:2px solid var(--gold-color);
			transition:var(--transition);
		}
		.card:hover{ transform:translateY(-4px); box-shadow:var(--shadow-lg) }

		/* Resumo do plano */
		.plan-summary{ padding:22px }
		.plan-summary > h2{
			font-size:1.25rem; color:var(--primary-color); margin-bottom:14px; font-weight:700;
		}
		.selected-plan{
			border-radius:var(--radius); padding:22px;
			background:linear-gradient(180deg,#fff, #fafbff);
			border:1px solid var(--medium-gray);
		}
		.selected-plan h3{ font-size:1.4rem; margin-bottom:8px }
		.plan-price{
			font-weight:800; font-size:2rem; color:var(--gold-dark);
			margin:.25rem 0 1rem;
		}
		.plan-price span{ font-size:.95rem; color:var(--dark-gray); font-weight:600 }
		.plan-features{ list-style:none; margin-top:.5rem }
		.plan-features li{
			display:flex; align-items:flex-start; gap:.6rem;
			padding:10px 0; border-bottom:1px dashed #e9ecf2; color:#24324a;
		}
		.plan-features li:last-child{ border-bottom:0 }
		.plan-features li::before{
			content:"\f00c"; font-family:"Font Awesome 6 Free"; font-weight:900;
			color:var(--gold-color); margin-top:2px;
		}

		/* Formulário */
		.payment-form{ padding:22px }
		.payment-form > h2{
			font-size:1.25rem; color:var(--primary-color); margin-bottom:14px; font-weight:700;
		}

		.form-group{ margin-bottom:14px }
		.form-label{
			display:block; font-size:.95rem; font-weight:600; color:#24324a; margin-bottom:6px;
		}
		.form-control{
			width:100%; padding:12px 14px; border-radius:10px;
			border:1px solid #d9deea; background:#fff; font-size:1rem;
			transition:var(--transition);
		}
		.form-control:focus{
			outline:none; border-color:var(--gold-color);
			box-shadow:0 0 0 3px rgba(212,175,55,.25);
		}

		/* Botões */
		.btn{
			display:inline-flex; align-items:center; justify-content:center; gap:.6rem;
			border:0; border-radius:10px; cursor:pointer;
			font-weight:700; padding:12px 16px; transition:var(--transition);
			box-shadow:var(--shadow);
		}
		.btn-primary{
			background:linear-gradient(to right,var(--primary-color),var(--primary-dark));
			color:var(--white);
		}
		.btn-primary:hover{
			transform:translateY(-2px);
			background:linear-gradient(to right,var(--gold-dark),var(--gold-color));
			color:#1d1d1d;
		}
		.btn-block{ width:100% }

		/* Aviso plano grátis */
		.free-plan-message{
			background:#fffdf5; border:1px solid #f2e6b6; color:#3c3506;
			border-radius:var(--radius); padding:16px; margin-bottom:14px;
		}

		/* Acessibilidade: foco visível para links/botões que não são inputs */
		a:focus, .btn:focus{
			outline:3px solid rgba(13,75,158,.25);
			outline-offset:2px;
		}
        /* ===== Compensação do menu fixo (menu.php) ===== */
        :root{
            /* ajuste aqui se o menu mudar de altura */
            --menu-height: 88px; /* 72–96px geralmente cobre bem */
        }

        /* opção A (sem alterar o HTML): aplica direto no body */
        body{
            padding-top: var(--menu-height); /* empurra todo o conteúdo pra baixo do menu */
        }

        /* se preferir desativar a margem fixa antes, zere aqui */
        .payment-container{
            margin-top: 24px; /* era ~90px; agora o body já tem padding-top */
        }

        /* botão "Voltar" não deve ficar sob o menu */
        .back-button{
            top: calc(var(--menu-height) + 18px); /* mantém o mesmo respiro */
            z-index: 1000; /* garante que fica sobre o conteúdo, mas abaixo do menu se este tiver z-index maior */
        }

        /* garante que o menu fique por cima do conteúdo quando necessário (caso seu menu não defina z-index) */
        header, .site-header, .topbar, nav.sticky, .menu-fixo{
            position: fixed; /* se já estiver, tudo bem */
            top: 0; left: 0; right: 0;
            z-index: 2000; /* acima do back-button e do conteúdo */
        }

        /* responsivo: menus mais altos em telas pequenas */
        @media (max-width: 640px){
            :root{ --menu-height: 96px; }
        }

	</style>
</head>
<body>
    <?php include __DIR__ . '/../menu.php'; ?>

	<div class="payment-container">
		<!-- Resumo do Plano -->
		<div class="plan-summary card">
			<h2>Resumo do Plano</h2>
			<div class="selected-plan">
				<h3><?= htmlspecialchars($detalhesPlano['nome']); ?></h3>
				<div class="plan-price">
					<?= htmlspecialchars($detalhesPlano['preco']); ?> <span>/<?= htmlspecialchars($detalhesPlano['periodo']); ?></span>
				</div>

				<?php if ($plano === 'individual'): ?>
					<ul class="plan-features">
						<li>Até 1 aluno</li>
						<li>Simulados ilimitados</li>
						<li>3 redação por aluno/semana</li>
					</ul>
				<?php elseif ($plano === 'essencial'): ?>
					<ul class="plan-features">
						<li>Até 100 alunos</li>
						<li>Simulados ilimitados</li>
						<li>3 redações por aluno/semana</li>
						<li>Painel do diretor</li>
					</ul>
				<?php elseif ($plano === 'pro'): ?>
					<ul class="plan-features">
						<li>Até 300 alunos</li>
						<li>Simulados ilimitados</li>
						<li>5 redações por aluno/semana</li>
						<li>Painel do diretor</li>
					</ul>
				<?php elseif ($plano === 'premium'): ?>
					<ul class="plan-features">
						<li>Até 800 alunos</li>
						<li>Simulados ilimitados</li>
						<li>Redações ilimitadas</li>
						<li>Painel do diretor</li>
					</ul>
				<?php else: ?>
					<ul class="plan-features">
						<li>Funcionalidades básicas</li>
						<li>Simulados ilimitados</li>
						<li>1 redação por aluno/mês</li>
						<li>Painel do diretor</li>
					</ul>
				<?php endif; ?>

			</div>
		</div>

		<!-- Formulário -->
		<div class="payment-form card">
			<h2>Informações para Contato</h2>

			<?php if ($plano !== 'gratuito'): ?>
				<form id="contact-form" method="POST" action="processar_assinatura.php">
					<input type="hidden" name="plano" value="<?= htmlspecialchars($plano); ?>">
					<input type="hidden" name="nomePlano" value="<?= htmlspecialchars($detalhesPlano['nome']); ?>">
					<input type="hidden" name="descricaoPlano" value="<?= htmlspecialchars($detalhesPlano['descricao']); ?>">

					<div class="form-group">
						<label for="nome-completo" class="form-label">Nome Completo</label>
						<input type="text" id="nome-completo" name="nomeUsuario" class="form-control" required>
					</div>

					<div class="form-group">
						<label for="telefone" class="form-label">Telefone</label>
						<input type="tel" id="telefone" name="telefoneUsuario" class="form-control" oninput="mascTelefone(this)" placeholder="(00) 00000-0000" required>
					</div>

					<div class="form-group">
						<label for="email" class="form-label">E-mail</label>
						<input type="email" id="email" name="emailUsuario" class="form-control" required>
					</div>

					<div class="form-group">
						<button type="submit" id="submit-button" class="btn btn-primary btn-block">
							Enviar Solicitação
						</button>
					</div>
				</form>
			<?php else: ?>
				<div class="free-plan-message">
					<p>O plano gratuito será ativado após análise da sua solicitação. Você receberá um e-mail com as instruções em até 48 horas.</p>
				</div>

				<form method="POST" action="processar_assinatura.php">
					<input type="hidden" name="plano" value="gratuito">
					<input type="hidden" name="nomePlano" value="<?= htmlspecialchars($detalhesPlano['nome']); ?>">
					<input type="hidden" name="descricaoPlano" value="<?= htmlspecialchars($detalhesPlano['descricao']); ?>">

					<div class="form-group">
						<label for="nome-completo" class="form-label">Nome Completo</label>
						<input type="text" id="nome-completo" name="nomeUsuario" class="form-control" required>
					</div>

					<div class="form-group">
						<label for="telefone" class="form-label">Telefone</label>
						<input type="tel" id="telefone" name="telefoneUsuario" class="form-control" oninput="mascTelefone(this)" placeholder="(00) 00000-0000" required>
					</div>

					<div class="form-group">
						<label for="email" class="form-label">E-mail</label>
						<input type="email" id="email" name="emailUsuario" class="form-control" required>
					</div>

					<button type="submit" class="btn btn-primary btn-block">
						Solicitar Plano Gratuito
					</button>
				</form>
			<?php endif; ?>
		</div>
	</div>

	<script>
        function mascTelefone(input) {
            let value = input.value.replace(/\D/g, '');
            value = value.substring(0, 11);

            if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
            } else {
                value = value.replace(/^(\d*)/, '($1');
            }

            input.value = value;
        }
	</script>
</body>
</html>
