<?php
// Inicie a sessão ANTES de qualquer saída
// Definir um tempo de vida mais longo para o cookie da sessão (por exemplo, 7 dias = 604800 segundos)
$session_lifetime = 604800; // 7 dias

ini_set('session.gc_maxlifetime', $session_lifetime);
session_set_cookie_params($session_lifetime);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se o usuário já estiver logado e o tipo de usuário estiver definido
if (isset($_SESSION['id']) && isset($_SESSION['tipoUsuario'])) {
    $tipoUsuario = strtolower($_SESSION['tipoUsuario']);

    switch ($tipoUsuario) {
        case 'estudante':
            header("Location: home.php"); // Redireciona para a home do estudante
            exit();
            
        case 'diretor':
            header("Location: diretor/home.php"); // Redireciona para a home do diretor
            exit();

        case 'administrador':
            header("Location: administrador/exibirProvas.php"); // Redireciona para a home do administrador
            exit();

        default:
            // Tipo inválido, encerra sessão e volta ao login
            session_destroy();
            header("Location: login.php?erro=tipoInvalido");
            exit();
    }
}
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
        /* Reset e Variáveis de Design Moderno */
        :root{
            /* Cores Mantidas */
            --azul-primario:#0d4b9e;--azul-claro:rgba(13,75,158,.5);--azul-escuro:#0a3a7a;
            --gold-color:#D4AF37;--branco:#fff;--branco-claro:#f5f8ff;--preto:#212529;
            --destaque:#1283c5;
            /* Novas Variáveis para Modernização */
            --sombra-card: 0 10px 30px rgba(0,0,0,.08);
            --sombra-botao: 0 4px 15px rgba(13,75,158,.3);
            --transicao: all .4s cubic-bezier(0.25, 0.8, 0.25, 1);
            --borda-radius: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Montserrat', sans-serif; 
            color: var(--preto); 
            background: var(--branco-claro);
            line-height: 1.6; 
            overflow-x: hidden; 
            transition: filter var(--transicao);
        }
        h1, h2, h3, h4 { 
            font-family: 'Merriweather', serif; 
            font-weight: 700; 
            line-height: 1.1; 
            color: var(--azul-escuro);
        }
        a { text-decoration: none; color: var(--azul-primario); transition: var(--transicao); }
        a:hover { color: var(--destaque); }

        /* --- CSS de Acessibilidade --- */
        .grayscale-mode { filter: grayscale(100%); }
        #accessibility-toggle { /* Olho principal */
            position: fixed; bottom: 20px; left: 20px;
            background: var(--destaque); color: var(--branco); padding: 15px;
            border-radius: 50%; border: none; cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,.3); font-size: 1.4rem; z-index: 1000;
            transition: var(--transicao);
        }
        #accessibility-toggle:hover { background: var(--azul-escuro); transform: scale(1.05); }

        /* Nova Caixa de Controles (Glassmorphism/Borda Suave) */
        #accessibility-controls {
            position: fixed; bottom: 85px; left: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(13,75,158,.1);
            border-radius: var(--borda-radius);
            padding: 20px; width: 280px;
            box-shadow: var(--sombra-card);
            z-index: 999;
            opacity: 0; visibility: hidden;
            transform: translateY(20px);
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s 0.3s;
        }
        #accessibility-controls.active {
            opacity: 1; visibility: visible; transform: translateY(0);
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s;
        }
        #accessibility-controls h4 { font-size: 1.1rem; margin-bottom: 15px; color: var(--azul-primario); }
        #toggle-filter-button {
            display: block; width: 100%; padding: 12px; margin-top: 10px;
            background: var(--azul-primario); color: var(--branco);
            border: none; border-radius: 8px; cursor: pointer;
            font-weight: 600; transition: var(--transicao);
        }
        #toggle-filter-button:hover { background: var(--azul-escuro); box-shadow: 0 2px 10px rgba(13,75,158,.5); }
        /* --- Fim do CSS de Acessibilidade --- */

        /* Estrutura Principal */
        main { padding-top: 80px; min-height: 100vh; }
        
        /* Seção Hero (Impacto Visual) */
        .hero { 
            text-align: center; 
            padding: 100px 20px 80px; 
            max-width: 100%; 
            background: linear-gradient(135deg, var(--branco-claro) 0%, var(--branco) 100%);
        }
        .hero h2 { 
            font-size: 3rem; 
            margin-bottom: 20px; 
            color: var(--azul-escuro);
            text-shadow: 1px 1px 0 rgba(0,0,0,.05);
        }
        .hero p { font-size: 1.25rem; margin-bottom: 40px; color: var(--preto); max-width: 800px; margin-left: auto; margin-right: auto; }
        .btn-main {
            display: inline-block; 
            background: var(--azul-primario); 
            color: var(--branco); 
            padding: 15px 40px; 
            border-radius: 50px;
            font-weight: 700; 
            font-size: 1.1rem; 
            transition: var(--transicao); 
            border: none; 
            cursor: pointer; 
            box-shadow: var(--sombra-botao);
        }
        .btn-main:hover {
    background: var(--branco); /* Mantém a cor original */
    transform: translateY(-3px); 
    box-shadow: 0 8px 20px rgba(13,75,158,.4);
    /* Se quiser mudar a cor do texto e ícone no hover: */
    /* color: var(--branco); */
}

        /* Seções de Conteúdo */
        .content-section {
            background: var(--branco); 
            border-radius: var(--borda-radius); 
            padding: 60px; 
            margin: 40px auto;
            max-width: 1200px; 
            box-shadow: var(--sombra-card); 
            border: 1px solid rgba(13,75,158,.05);
        }
        .section-title { 
            font-size: 2.2rem; 
            margin-bottom: 30px; 
            color: var(--azul-primario);
        }
        .text-content { font-size: 1.05rem; margin-bottom: 20px; text-align: justify; line-height: 1.8; }
        
        /* Layout com Imagem */
        .text-and-image { display: flex; flex-direction: column; gap: 40px; align-items: center; }
        .content-image {
            max-width: 100%; height: auto; 
            border-radius: var(--borda-radius); 
            box-shadow: var(--sombra-card); 
            transition: var(--transicao); 
            border: 1px solid rgba(0,0,0,.05);
        }
        .content-image:hover { transform: scale(1.01); }
        
        @media (min-width: 769px) {
            /* CORREÇÃO DE ALINHAMENTO: Centraliza verticalmente texto e imagem para equilíbrio */
            .text-and-image { 
                flex-direction: row; 
                justify-content: space-between; 
                align-items: center; /* ALINHAMENTO PRINCIPAL AQUI: 'center' */
            }
            
            .text-and-image.reverse { flex-direction: row-reverse; }
            .text-and-image .content-image { width: 45%; max-width: 450px; }
            .text-and-image .text-group { flex: 1; padding-right: 30px; }
            .text-and-image.reverse .text-group { padding-right: 0; padding-left: 30px; }
        }

        /* Cards de Planos (Layout Grid Moderno) */
        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }
        .plan-card {
            background: var(--branco);
            border-radius: var(--borda-radius);
            padding: 30px;
            box-shadow: var(--sombra-card);
            border: 1px solid rgba(13,75,158,.1);
            position: relative;
            transition: var(--transicao);
            display: flex;
            flex-direction: column;
        }
        .plan-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,.1); }
        .plan-card h4 { font-size: 1.6rem; margin-bottom: 5px; color: var(--azul-primario); }
        .plan-description { color: #6c757d; margin-bottom: 25px; font-style: italic; font-size: 0.95rem; }
        .plan-price { font-size: 2.8rem; font-weight: 800; color: var(--azul-escuro); margin: 10px 0 20px; line-height: 1; }
        .plan-price span { font-size: 1rem; font-weight: 500; color: var(--preto); }
        .plan-features { list-style: none; margin: 15px 0 30px; padding: 0; flex-grow: 1; }
        .plan-features li { padding: 10px 0; border-bottom: 1px solid rgba(13,75,158,.1); display: flex; align-items: center; }
        .plan-features li i { color: var(--destaque); margin-right: 10px; font-size: 1rem; }
        .plan-features li:last-child { border-bottom: none; }
        .btn-plan {
            display: block; 
            background: var(--azul-primario); 
            color: var(--branco); 
            padding: 12px 20px; 
            border-radius: 8px;
            font-weight: 600; 
            text-align: center; 
            transition: var(--transicao);
        }
        .popular { border: 3px solid var(--destaque); background: var(--branco-claro); }
        .popular-badge {
            position: absolute; top: -15px; right: 20px; background: var(--destaque); color: var(--branco);
            padding: 5px 15px; border-radius: 50px; font-size: .9rem; font-weight: 700;
            box-shadow: 0 5px 15px rgba(18, 131, 197, 0.4);
        }

        /* Footer Moderno */
        footer { 
            background: var(--azul-primario); 
            color: var(--branco); 
            text-align: center; 
            padding: 30px 0; 
            margin-top: 60px; 
            border-top-left-radius: var(--borda-radius); 
            border-top-right-radius: var(--borda-radius);
        }
        footer p { margin-bottom: 10px; font-weight: 400; font-size: 0.95rem; }
        .footer-link { color: var(--branco); font-weight: 500; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.5); }
        .footer-link:hover { border-bottom-color: var(--destaque); }
        
        /* --- Novo CSS para Destaques de Conteúdo (Checklist e Cards) --- */
        .icon-list {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }

        .icon-list li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            font-size: 1.05rem;
            line-height: 1.5;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(0,0,0,0.05);
        }

        .icon-list li:last-child {
            border-bottom: none;
        }

        .icon-list li .fas {
            color: var(--destaque);
            font-size: 1.4rem;
            margin-right: 15px;
            margin-top: 3px;
            min-width: 25px;
        }

        /* Cards de Destaque para Aprofundamento */
        .feature-cards {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .feature-card {
            background: var(--branco-claro);
            padding: 20px;
            border-radius: var(--borda-radius);
            box-shadow: 0 5px 15px rgba(13, 75, 158, 0.1);
            width: 100%;
            max-width: 350px;
            border-left: 5px solid var(--azul-primario);
            transition: var(--transicao);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(13, 75, 158, 0.2);
        }

        .feature-card h5 {
            font-size: 1.2rem;
            color: var(--azul-escuro);
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--preto);
        }

        /* Responsividade para Mobile */
        @media (max-width: 768px){
            .hero h2 { font-size: 2.2rem; }
            .hero p { font-size: 1.1rem; }
            .content-section { padding: 30px 20px; }
            .section-title { font-size: 1.8rem; }
            .text-and-image .text-group { padding: 0; }
            .feature-cards { flex-direction: column; align-items: center; }
        }
        @media (max-width: 480px){
            .hero h2 { font-size: 1.8rem; }
            #accessibility-controls { width: 90%; }
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/menu.php'; ?>

    <main>
        <section class="hero">
            <h2>Transforme Seu Aprendizado em Conquistas</h2>
            <p>
                Bem-vindo ao Caminho do Saber, seu portal completo de preparação para o <strong>ENEM e Vestibulares</strong>.
                Alcance a excelência acadêmica com recursos focados e apoio dedicado.
            </p>
            <a href="home.php" class="btn-main">Comece Sua Jornada Hoje <i class="fas fa-arrow-right"></i></a>
        </section>

        <section class="content-section">
            <h3 class="section-title">✨ Escolha Seu Plano de Estudos</h3>
            <div class="plans-container">
                
                <div class="plan-card">
                    <h4>Individual</h4>
                    <p class="plan-description">Focado no aluno único.</p>
                    <div class="plan-price">R$ 10,99<span>/mês</span></div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Até 1 aluno</li>
                        <li><i class="fas fa-check"></i> Simulados ilimitados</li>
                        <li><i class="fas fa-check"></i> 3 redações por aluno/semana</li>
                    </ul>
                    <a href="configuracao/configuracoes.php?tab=plans" class="btn-plan">Assinar Plano</a>
                </div>

                <div class="plan-card">
                    <h4>Essencial</h4>
                    <p class="plan-description">Ideal para pequenas instituições.</p>
                    <div class="plan-price">R$ 499<span>/mês</span></div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Até 100 alunos</li>
                        <li><i class="fas fa-check"></i> Simulados ilimitados</li>
                        <li><i class="fas fa-check"></i> 3 redações por aluno/semana</li>
                        <li><i class="fas fa-check"></i> Painel do diretor</li>
                    </ul>
                    <a href="configuracao/configuracoes.php?tab=plans" class="btn-plan">Assinar Plano</a>
                </div>

                <div class="plan-card popular">
                    <div class="popular-badge">Mais Popular</div>
                    <h4>Pro</h4>
                    <p class="plan-description">Para escolas em crescimento.</p>
                    <div class="plan-price">R$ 1.290<span>/mês</span></div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Até 300 alunos</li>
                        <li><i class="fas fa-check"></i> Simulados ilimitados</li>
                        <li><i class="fas fa-check"></i> 5 redações por aluno/semana</li>
                        <li><i class="fas fa-check"></i> Painel do diretor completo</li>
                    </ul>
                    <a href="configuracao/configuracoes.php?tab=plans" class="btn-plan">Assinar Plano</a>
                </div>

                <div class="plan-card">
                    <h4>Premium</h4>
                    <p class="plan-description">Solução completa para grandes redes.</p>
                    <div class="plan-price">R$ 2.990<span>/mês</span></div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Até 800 alunos</li>
                        <li><i class="fas fa-check"></i> Simulados ilimitados</li>
                        <li><i class="fas fa-check"></i> Redações ilimitadas</li>
                        <li><i class="fas fa-check"></i> Painel do diretor + Relatórios Avançados</li>
                    </ul>
                    <a href="configuracao/configuracoes.php?tab=plans" class="btn-plan">Assinar Plano</a>
                </div>
            </div>
        </section>

<section class="content-section">
    <h3 class="section-title">Rumo ao ENEM e ao Provão Paulista: O Futuro Começa Agora!</h3>
    <div class="text-and-image">
        <div class="text-group">
            <p class="text-content">
                Seu sucesso no ENEM e no Provão Paulista depende de uma preparação estratégica e direcionada. Nosso portal é construído com tecnologia educacional para garantir que você esteja um passo à frente.
            </p>

            <ul class="icon-list">
                <li>
                    <i class="fas fa-chart-line"></i> 
                    <div>
                        <strong>Metodologia Orientada por Dados:</strong><br>
                        Focamos em áreas que você precisa melhorar, otimizando seu tempo de estudo.
                    </div>
                </li>
                <li>
                    <i class="fas fa-graduation-cap"></i> 
                    <div>
                        <strong>Acesso a Oportunidades:</strong><br>
                        Preparamos você para garantir bolsas, financiamentos e vagas nas melhores universidades.
                    </div>
                </li>
                <li>
                    <i class="fas fa-brain"></i> 
                    <div>
                        <strong>Desenvolvimento Cognitivo:</strong><br>
                        Nossos simulados e questões práticas aprimoram suas habilidades de raciocínio e gerenciamento de tempo.
                    </div>
                </li>
            </ul>
        </div>
        <img src="imagem/wallpaper2.png" alt="Estudantes se preparando" class="content-image">
    </div>
</section>

        <section class="content-section">
            <h3 class="section-title">Por que o ENEM e Provão Paulista são essenciais?</h3>
            
            <div class="text-and-image reverse">
                <div class="text-group">
                    <p class="text-content">
                        Mais do que simples provas, estes exames definem o seu acesso ao ensino superior de qualidade e a uma vida profissional mais promissora. Entenda os três caminhos cruciais que se abrem para você:
                    </p>
                    
                    <div class="feature-cards">
                        
                        <div class="feature-card">
                            <h5><i class="fas fa-university"></i> Vagas em Federais (ENEM/SISU)</h5>
                            <p>O Sisu utiliza a nota do ENEM para classificar estudantes em mais de 100 universidades públicas federais e estaduais por todo o país.</p>
                        </div>
                        
                        <div class="feature-card">
                            <h5><i class="fas fa-hand-holding-usd"></i> Bolsas e Financiamentos (PROUNI/FIES)</h5>
                            <p>Sua nota no ENEM é a chave para programas de bolsa integral (PROUNI) ou crédito estudantil facilitado (FIES) em instituições privadas.</p>
                        </div>

                        <div class="feature-card">
                            <h5><i class="fas fa-map-marked-alt"></i> Acesso Paulista (Provão/Vunesp)</h5>
                            <p>O Provão Paulista é o acesso direto para a USP, UNESP e UNICAMP, as melhores estaduais do estado, garantindo o futuro na sua região.</p>
                        </div>
                    </div>
                </div>
                <img src="imagem/wallpaperestudos.png" alt="Importância dos exames" class="content-image">
            </div>
        </section>

        <section class="content-section">
            <h3 class="section-title">Estratégias de Estudo Eficientes</h3>
            <div class="text-and-image">
                <div class="text-group">
                    <p class="text-content">Conheça a estrutura das provas e monte um <strong>cronograma equilibrado</strong> com nossa IA. Pratique <strong>redação</strong> com correções detalhadas e resolva milhares de questões de provas anteriores em um ambiente gamificado.</p>
                    <p class="text-content">Nosso sistema ajuda você a gerenciar o tempo e focar nos pontos fracos, garantindo que você chegue no dia da prova com a máxima confiança.</p>
                </div>
                <img src="imagem/sla.jpeg" alt="Métodos de estudo" class="content-image">
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados. | CNPJ: 12.345.678/0001-90</p>
        <a href="POLITICA.php" class="footer-link">Política de Privacidade</a> | 
        <a href="#" class="footer-link">Termos de Uso</a>
    </footer>

    <button id="accessibility-toggle" title="Abrir Opções de Acessibilidade">
        <i class="fas fa-eye"></i>
    </button>
    
    <div id="accessibility-controls">
        <h4><i class="fas fa-universal-access"></i> Opções de Acessibilidade</h4>
        <p>Ajuste o filtro de cores:</p>
        <button id="toggle-filter-button">Ativar Filtro (Daltônico)</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const openPanelButton = document.getElementById('accessibility-toggle');
            const controlsPanel = document.getElementById('accessibility-controls');
            const toggleFilterButton = document.getElementById('toggle-filter-button');
            const body = document.body;
            const storageKey = 'grayscaleModeEnabled';

            // --- Lógica de Alternância do Filtro ---

            function updateGrayscaleMode(isEnabled) {
                if (isEnabled) {
                    body.classList.add('grayscale-mode');
                    toggleFilterButton.textContent = "Desativar Filtro (Normal)";
                    toggleFilterButton.style.backgroundColor = 'var(--destaque)'; 
                } else {
                    body.classList.remove('grayscale-mode');
                    toggleFilterButton.textContent = "Ativar Filtro (Daltônico)";
                    toggleFilterButton.style.backgroundColor = 'var(--azul-primario)';
                }
                localStorage.setItem(storageKey, isEnabled);
            }

            // 1. Carregar estado do Local Storage
            const isEnabled = localStorage.getItem(storageKey) === 'true';
            updateGrayscaleMode(isEnabled);

            // 2. Evento para o botão de alternância DENTRO do painel
            toggleFilterButton.addEventListener('click', function() {
                const isCurrentlyEnabled = body.classList.contains('grayscale-mode');
                const newState = !isCurrentlyEnabled; 
                updateGrayscaleMode(newState);
            });

            // --- Lógica de Exibição do Painel ---
            
            // 3. Evento para o botão principal (o olho)
            openPanelButton.addEventListener('click', function(event) {
                controlsPanel.classList.toggle('active');
                event.stopPropagation();
            });

            // 4. Fechar o painel ao clicar em qualquer lugar fora dele
            document.addEventListener('click', function(event) {
                if (controlsPanel.classList.contains('active') && !controlsPanel.contains(event.target) && event.target !== openPanelButton) {
                    controlsPanel.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>