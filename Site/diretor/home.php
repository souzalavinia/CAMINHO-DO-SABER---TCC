<?php
// home.php
// Inicie a sessão ANTES de qualquer saída
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificando se está logado
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}
$id = $_SESSION['id'];
$plano = $_SESSION['planoUsuario'];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>

    <!-- Fonte usada pelo conteúdo desta página -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d4b9e;
            --primary-dark: #0a3a7a;
            --primary-light: #3a6cb5;
            --gold-color: #D4AF37;
            --gold-light: #E6C200;
            --gold-dark: #996515;
            --black: #212529;
            --dark-black: #121212;
            --white: #ffffff;
            --light-gray: #f5f7fa;
            --medium-gray: #e0e5ec;
            --dark-gray: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background-color: var(--light-gray);
            color: var(--black);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ====== IMPORTANTE: o menu é fixo, então garantimos espaço no topo ====== */
        main {
            flex: 1;
            padding-top: 120px; /* o menu.php também compensa, mas mantemos por segurança */
            padding-bottom: 20px;
        }

        /* Barra de pesquisa */
        .search-container { margin-top: 10px; }
        form {
            width: 100%;
            margin: 30px auto 10px;
            padding: 0 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .input-container {
            display: flex;
            align-items: center;
            width: 50%;
            max-width: 600px;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px 20px;
            font-size: 1rem;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            background-color: var(--white);
            transition: var(--transition);
        }
        input[type="text"]:focus {
            outline: none;
            border-color: var(--gold-color);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }
        .enviar {
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: 600;
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            margin-left: 10px;
            box-shadow: var(--box-shadow);
        }
        button:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
        }

        /* Cards */
        .cards-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 30px;
            margin: 180px auto;
            padding: 0 20px;
            max-width: 1200px;
        }
        .card, .card3 {
            width: 100%;
            max-width: 480px;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: var(--border-radius);
            border: 2px solid var(--gold-color);
            background-color: var(--white);
            padding: 2rem;
            color: var(--black);
            text-align: left;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        .card:hover, .card3:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .card3 { max-width: 800px; margin: 30px auto; }
        .header { display: flex; flex-direction: column; margin-bottom: 1.5rem; }
        .title2 { font-size: 1.5rem; font-weight: 700; color: var(--primary-color); }
        .price { font-size: 2.5rem; font-weight: 700; color: var(--gold-dark); margin: 10px 0; }
        .desc { margin: 1rem 0; color: var(--dark-gray); font-size: 1rem; line-height: 1.6; }
        .action {
            border: none; border-radius: var(--border-radius);
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            padding: 12px 25px; text-align: center; font-weight: 600; color: var(--white);
            cursor: pointer; transition: var(--transition); text-decoration: none; display: inline-block;
            width: fit-content; box-shadow: var(--box-shadow);
        }
        .action:hover {
            background: linear-gradient(to right, var(--gold-dark), var(--gold-color));
            transform: translateY(-2px);
        }

        /* Resultados da busca */
        .search-results { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .search-results h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.8rem;
        }
        .search-results ul { list-style: none; }
        .search-results li {
            margin-bottom: 15px;
            padding: 15px;
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        .search-results li:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }

        /* Lista de provas */
        .provas-list { margin: 40px 0; }
        .year-group { margin-bottom: 30px; }
        .year-title {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 12px 20px; border-radius: var(--border-radius);
            font-size: 1.2rem; font-weight: 600; margin-bottom: 15px; box-shadow: var(--box-shadow);
        }
        .prova-item {
            background-color: var(--white);
            padding: 15px 20px; margin-bottom: 10px; border-radius: var(--border-radius);
            box-shadow: var(--box-shadow); transition: var(--transition); border-left: 4px solid var(--gold-color);
        }
        .prova-item:hover { transform: translateX(5px); box-shadow: 0 5px 15px rgba(0,0,0,0.15); }
        .prova-link { text-decoration: none; font-size: 1.1rem; font-weight: 500; color: var(--primary-color); transition: var(--transition); display: block; }
        .prova-link:hover { color: var(--gold-dark); }

        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-black));
            color: var(--white);
            text-align: center;
            padding: 20px 0;
            width: 100%;
            border-top: 3px solid var(--gold-color);
            position: relative;
            bottom: 0;
            margin-top: auto;
        }
        footer p { font-size: 0.9rem; margin-bottom: 10px; }
        footer a { color: var(--gold-color); text-decoration: none; font-weight: 500; transition: var(--transition); }
        footer a:hover { color: var(--gold-light); text-decoration: underline; }

        /* Responsivo */
        @media screen and (max-width: 992px) {
            .input-container { width: 80%; flex-direction: column; }
            button { width: 80%; margin: 10px 0 0 0; }
            .card, .card3 { width: 90%; }
        }
        @media screen and (max-width: 576px) {
            .input-container { width: 90%; }
            .card, .card3 { padding: 1.5rem; }
            .price { font-size: 2rem; }
        }

        /* Animações */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: fadeIn 0.6s ease-out forwards; }
    </style>
</head>
<body>

    <?php include __DIR__ . '/menu.php'; ?>

    <main>
        <div class="cards-container">
            <!-- Card: Provas -->
            <div class="card animate-in">
                <div class="header">
                    <span class="title2">Provas</span>
                    <span class="price">ACESSAR</span>
                </div>
                <p class="desc">Resolva provas anteriores e acompanhe sua evolução. Cada questão é uma oportunidade de aprender e crescer!</p>
                <a href="exibirProvas.php" class="action">Começar</a>
            </div>

            <!-- Card: Corretor de Redação -->
            <div class="card animate-in">
                <div class="header">
                    <span class="title2">Corretor de</span>
                    <span class="price">REDAÇÃO</span>
                </div>
                <p class="desc">Escreva, envie e receba uma correção automática com sugestões. Pratique sua escrita de forma objetiva e eficaz.</p>
                <a href="corretor.php" class="action">Escrever</a>
            </div>
        </div>
    </main>

    <!-- (REMOVIDO) JS do menu antigo. O menu.php já contém seu próprio script. -->

    <footer class="animate-in">
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
        <a href="../POLITICA.php">Política de privacidade</a>
    </footer>
</body>
</html>
