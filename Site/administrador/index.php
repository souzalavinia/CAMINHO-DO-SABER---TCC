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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caminho do Saber - Portal de Estudos</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Reset e Variáveis */
        :root{
            --azul-primario:#0d4b9e;--azul-claro:rgba(13,75,158,.5);--azul-escuro:#0a3a7a;
            --gold-color:#D4AF37;--branco:#fff;--branco-acinzentado:#f8f9fa;--preto:#333;
            --destaque:#1283c5;--sombra:0 4px 12px rgba(0,0,0,.1);--transicao:all .3s ease;
            --borda-arredondada:8px;--gold-shadow:0 5px 15px rgba(212,175,55,.3);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; color: var(--preto); background: white; line-height: 1.6; overflow-x: hidden; }
        h1, h2, h3, h4 { font-family: 'Merriweather', serif; font-weight: 700; line-height: 1.2; color: var(--azul-primario); }
        a { text-decoration: none; color: inherit; transition: var(--transicao); }

        /* Conteúdo Principal */
        main { padding-top: 120px; min-height: 100vh; }
        .hero { 
            text-align: center; padding: 80px 20px; background: rgba(255,255,255,.95); margin: 20px auto;
            border-radius: var(--borda-arredondada); max-width: 900px; box-shadow: var(--sombra);
            border: 1px solid rgba(13,75,158,.1);
        }
        .hero h2 { font-size: 2.2rem; margin-bottom: 20px; color: var(--azul-primario); }
        .hero p { font-size: 1.1rem; margin-bottom: 30px; }
        .btn-main {
            display: inline-block; background: var(--azul-primario); color: var(--branco); padding: 12px 30px; border-radius: 30px;
            font-weight: 600; font-size: 1.1rem; transition: var(--transicao); border: none; cursor: pointer; box-shadow: 0 4px 8px rgba(13,75,158,.2);
        }
        .btn-main:hover { background: var(--azul-escuro); transform: translateY(-3px); box-shadow: 0 6px 15px rgba(13,75,158,.3); }

        /* Seções */
        .content-section {
            background: rgba(255,255,255,.95); border-radius: var(--borda-arredondada); padding: 40px; margin: 40px auto;
            max-width: 1200px; box-shadow: var(--sombra); border: 1px solid rgba(13,75,158,.1);
        }
        .section-title { font-size: 1.8rem; margin-bottom: 25px; position: relative; padding-bottom: 10px; }
        .section-title::after { content: ''; position: absolute; bottom: 0; left: 0; width: 60px; height: 3px; background: var(--azul-primario); }
        .text-content { font-size: 1.05rem; margin-bottom: 20px; text-align: justify; line-height: 1.7; }
        .image-container { margin: 25px 0; display: flex; justify-content: center; flex-wrap: wrap; gap: 20px; }
        .content-image {
            max-width: 100%; height: auto; border-radius: var(--borda-arredondada); box-shadow: var(--sombra);
            transition: var(--transicao); border: 1px solid rgba(13,75,158,.1);
        }
        .content-image:hover { transform: scale(1.02); box-shadow: 0 8px 20px rgba(0,0,0,.15); }
        
        /* Flexbox para Seções de Texto e Imagem */
        .text-and-image {
            display: flex;
            flex-direction: column; 
            gap: 25px;
            align-items: center;
        }
        
        @media (min-width: 769px) {
            .text-and-image {
                flex-direction: row; 
                justify-content: space-between;
                align-items: flex-start;
            }
            .text-and-image.reverse {
                flex-direction: row-reverse;
            }
            .text-and-image .content-image {
                width: 40%;
                max-width: 350px;
            }
            .text-and-image .text-group {
                flex: 1;
            }
        }

        /* Cards de Planos */
        .plans-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 25px;
            margin: 40px 0;
        }
        .plan-card {
            background: var(--branco);
            border-radius: var(--borda-arredondada);
            padding: 30px;
            width: 100%; 
            max-width: 320px;
            box-shadow: var(--sombra);
            border: 1px solid rgba(13,75,158,.1);
            position: relative;
            transition: var(--transicao);
        }
        .plan-card:hover { transform: translateY(-10px); box-shadow: 0 10px 25px rgba(13,75,158,.15); }
        .plan-card h4 { font-size: 1.5rem; margin-bottom: 10px; color: var(--azul-primario); }
        .plan-price { font-size: 2.2rem; font-weight: 700; color: var(--azul-escuro); margin: 15px 0; }
        .plan-price span { font-size: 1rem; font-weight: 400; color: var(--preto); }
        .plan-description { color: var(--preto); margin-bottom: 20px; font-style: italic; }
        .plan-features { list-style: none; margin: 25px 0; }
        .plan-features li { padding: 8px 0; border-bottom: 1px dashed rgba(13,75,158,.2); }
        .plan-features li:last-child { border-bottom: none; }
        .btn-plan {
            display: block; background: var(--azul-primario); color: var(--branco); padding: 12px 20px; border-radius: 30px;
            font-weight: 600; text-align: center; margin-top: 20px; transition: var(--transicao);
        }
        .btn-plan:hover { background: var(--azul-escuro); transform: translateY(-3px); box-shadow: 0 4px 8px rgba(13,75,158,.3); }
        .popular { border: 2px solid var(--destaque); }
        .popular-badge {
            position: absolute; top: -12px; right: 20px; background: var(--destaque); color: var(--branco);
            padding: 5px 15px; border-radius: 20px; font-size: .8rem; font-weight: 600;
        }

        /* Footer */
        footer { 
            background: var(--azul-primario); color: var(--branco); text-align: center; padding: 25px 0; backdrop-filter: blur(8px); 
            margin-top: auto;
        }
        footer p { margin-bottom: 10px; font-weight: 500; }
        .footer-link { color: var(--branco); font-weight: 600; text-decoration: underline; }
        .footer-link:hover { opacity: .9; }

        /* Animações (mantidas, mas não aplicadas no HTML) */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(50px); } to { opacity: 1; transform: translateY(0); } }
        .animated { animation: slideUp .8s ease-out forwards; }

        /* Responsividade */
        @media (max-width: 768px){
            main { padding-top: 160px; } 
            .hero{ padding: 50px 15px; }
            .hero h2{ font-size: 1.8rem; }
            .content-section{ padding: 30px 20px; }
        }
        @media (max-width: 480px){
            main { padding-top: 150px; } 
            .hero h2{ font-size: 1.5rem; }
            .section-title{ font-size: 1.5rem; }
            .btn-main{ padding: 10px 20px; font-size: 1rem; }
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/menu.php'; ?>

    <main>
        <section class="hero">
            <h2>Transforme Seu Aprendizado em Conquistas</h2>
            <p>
                Bem-vindo ao Caminho do Saber, seu portal completo de preparação para o ENEM e Provão Paulista.
                Aqui você encontra as ferramentas, recursos e apoio necessário para alcançar excelência acadêmica
                e realizar seus sonhos de ingressar no ensino superior.
            </p>
            <a href="home.php" class="btn-main">Comece Agora</a>
        </section>

        <section class="content-section">
            <h3 class="section-title">Nossos Planos</h3>
            <div class="plans-container">
                <div class="plan-card">
                    <h4>Individual</h4>
                    <div class="plan-price">R$ 10,99<span>/mês</span></div>
                    <p class="plan-description">Plano Individual</p>
                    <ul class="plan-features">
                        <li>Até 1 aluno</li>
                        <li>Simulados ilimitados</li>
                        <li>3 redações por aluno/semana</li>
                    </ul>
                    <a href="configuracao/configuracoes.php?tab=plans" class="btn-plan">Assinar</a>
                </div>

                <div class="plan-card">
                    <h4>Essencial</h4>
                    <div class="plan-price">R$ 499<span>/mês</span></div>
                    <p class="plan-description">Ideal para pequenas escolas</p>
                    <ul class="plan-features">
                        <li>Até 100 alunos</li>
                        <li>Simulados ilimitados</li>
                        <li>3 redações por aluno/semana</li>
                        <li>Painel do diretor</li>
                    </ul>
                    <a href="configuracao/configuracoes.php?tab=plans" class="btn-plan">Assinar</a>
                </div>

                <div class="plan-card popular">
                    <div class="popular-badge">Mais Popular</div>
                    <h4>Pro</h4>
                    <div class="plan-price">R$ 1.290<span>/mês</span></div>
                    <p class="plan-description">Para escolas em crescimento</p>
                    <ul class="plan-features">
                        <li>Até 300 alunos</li>
                        <li>Simulados ilimitados</li>
                        <li>5 redações por aluno/semana</li>
                        <li>Painel do diretor</li>
                    </ul>
                    <a href="configuracao/configuracoes.php?tab=plans" class="btn-plan">Assinar</a>
                </div>

                <div class="plan-card">
                    <h4>Premium</h4>
                    <div class="plan-price">R$ 2.990<span>/mês</span></div>
                    <p class="plan-description">Solução completa</p>
                    <ul class="plan-features">
                        <li>Até 800 alunos</li>
                        <li>Simulados ilimitados</li>
                        <li>Redações ilimitadas</li>
                        <li>Painel do diretor</li>
                    </ul>
                    <a href="configuracao/configuracoes.php?tab=plans" class="btn-plan">Assinar</a>
                </div>
            </div>
            <p class="text-content" style="text-align:center;margin-top:30px;">
                <strong>Para escolas públicas:</strong> Oferecemos um plano gratuito com funcionalidades essenciais. <a href="configuracao/configuracoes.php?tab=plans">Saiba mais</a>
            </p>
        </section>

        <section class="content-section">
            <h3 class="section-title">Rumo ao ENEM e ao Provão Paulista: O Futuro Começa Agora!</h3>
            <div class="text-and-image">
                <div class="text-group">
                    <p class="text-content">
                        Olá, futuros universitários! O ENEM e o Provão Paulista não são apenas exames — são portas de entrada para um mundo de oportunidades…
                    </p>
                    <p class="text-content">
                        Segundo dados do IBGE, profissionais com diploma universitário ganham, em média, 2,5 vezes mais do que aqueles com apenas o ensino médio…
                    </p>
                </div>
                <img src="imagem/wallpaper2.png" alt="Estudantes se preparando" class="content-image">
            </div>
        </section>

        <section class="content-section">
            <h3 class="section-title">Por que o ENEM e Provão Paulista são essenciais?</h3>
            <div class="text-and-image reverse">
                <div class="text-group">
                    <p class="text-content">O ENEM e o Provão Paulista são as principais portas de entrada para o ensino superior no Brasil…</p>
                    <p class="text-content">Bolsas via PROUNI, FIES e oportunidades em universidades que aceitam a nota do ENEM…</p>
                    <p class="text-content">O Provão Paulista abre portas nas estaduais (USP/UNESP/UNICAMP)…</p>
                </div>
                <img src="imagem/wallpaperestudos.png" alt="Importância dos exames" class="content-image">
            </div>
        </section>

        <section class="content-section">
            <h3 class="section-title">Estratégias de Estudo Eficientes</h3>
            <div class="text-and-image">
                <div class="text-group">
                    <p class="text-content">Conheça a estrutura das provas e monte um cronograma equilibrado…</p>
                    <p class="text-content">Pratique redação e resolva provas anteriores…</p>
                    <p class="text-content">Gerencie o tempo e cuide da saúde…</p>
                </div>
                <img src="imagem/sla.jpeg" alt="Métodos de estudo" class="content-image">
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
        <a href="POLITICA.php" class="footer-link">Política de Privacidade</a>
    </footer>

    <script>
        // O script JavaScript para a animação das seções foi removido, pois não é mais necessário.
    </script>
</body>
</html>