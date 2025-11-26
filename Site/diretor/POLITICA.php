<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - Caminho do Saber</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
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
            
            --sombra: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transicao: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light-gray);
            color: var(--black);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            background-color: var(--white);
            padding: 40px;
            box-shadow: var(--sombra);
            border-radius: 12px;
            position: relative;
        }

        header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            text-align: center;
        }

        header h1 {
            font-family: 'Merriweather', serif;
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 15px;
        }

        header h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--gold-color));
            border-radius: 2px;
        }

        .btn-home {
            align-self: flex-start;
            background: linear-gradient(45deg, var(--primary-color), var(--primary-light));
            color: var(--white);
            padding: 12px 30px;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: var(--sombra);
            transition: var(--transicao);
            border: none;
            cursor: pointer;
            margin-bottom: 30px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-home:hover {
            background: linear-gradient(45deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(13, 75, 158, 0.3);
        }

        .btn-home:active {
            transform: translateY(1px);
        }

        .btn-home::before {
            content: '←';
            font-size: 1.2rem;
        }

        .content {
            padding: 20px 0;
        }

        .content h2 {
            font-family: 'Merriweather', serif;
            font-size: 1.8rem;
            color: var(--primary-dark);
            margin: 30px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--medium-gray);
        }

        .content h3 {
            font-size: 1.4rem;
            margin: 25px 0 10px;
            color: var(--primary-color);
        }

        .content p {
            font-size: 1.05rem;
            margin-bottom: 15px;
            text-align: justify;
        }

        .content ul, .content ol {
            margin: 0 0 20px 30px;
        }

        .content li {
            margin-bottom: 10px;
            position: relative;
            padding-left: 20px;
        }

        .content ul li::before {
            content: '•';
            color: var(--gold-color);
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .content ol {
            counter-reset: item;
            list-style-type: none;
        }

        .content ol li::before {
            content: counter(item) ".";
            counter-increment: item;
            color: var(--gold-color);
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .content a {
            color: var(--primary-color);
            font-weight: 500;
            transition: var(--transicao);
        }

        .content a:hover {
            text-decoration: underline;
            color: var(--primary-dark);
        }

        .highlight-box {
            background-color: rgba(13, 75, 158, 0.1);
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }

        footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid var(--medium-gray);
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            .content h2 {
                font-size: 1.6rem;
            }
            
            .content h3 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 25px 15px;
            }
            
            header h1 {
                font-size: 1.8rem;
            }
            
            .btn-home {
                padding: 10px 20px;
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a id="backButton" class="btn-home">Voltar</a>
        
        <header>
            <h1>Política de Privacidade</h1>
            <p>Última atualização: 26 de Maio de 2025</p>
        </header>

        <section class="content">
            <div class="highlight-box">
                <p>Na <strong>Caminho do Saber</strong>, sua privacidade é nossa prioridade. Esta política detalha como coletamos, usamos, protegemos e compartilhamos suas informações quando você utiliza nossos serviços.</p>
            </div>

            <h2>1. Informações que Coletamos</h2>
            <p>Coletamos diferentes tipos de informações para fornecer e melhorar nossos serviços:</p>
            
            <h3>1.1 Informações fornecidas voluntariamente</h3>
            <ul>
                <li><strong>Dados de cadastro</strong>: Nome completo, e-mail, data de nascimento, telefone</li>
                <li><strong>Dados acadêmicos</strong>: Histórico escolar, áreas de interesse, desempenho em exercícios</li>
            </ul>
            
            <h3>1.2 Informações coletadas automaticamente</h3>
            <ul>
                <li><strong>Dados de uso</strong>: Páginas visitadas, tempo gasto, links clicados</li>
                <li><strong>Dados técnicos</strong>: Endereço IP, tipo de navegador, dispositivo utilizado, sistema operacional</li>
                <li><strong>Cookies</strong>: Utilizamos cookies essenciais e de desempenho para melhorar sua experiência</li>
            </ul>

            <h2>2. Finalidades do Tratamento de Dados</h2>
            <p>Utilizamos suas informações para:</p>
            <ol>
                <li>Fornecer e personalizar nossos serviços educacionais</li>
                <li>Desenvolver e melhorar nossos produtos</li>
                <li>Comunicar-se sobre atualizações, materiais e eventos relevantes</li>
                <li>Garantir a segurança e prevenir fraudes</li>
                <li>Cumprir obrigações legais</li>
            </ol>

            <h2>3. Compartilhamento de Dados</h2>
            <p>Seus dados podem ser compartilhados nas seguintes situações:</p>
            <ul>
                <li><strong>Parceiros educacionais</strong>: Instituições de ensino para emissão de certificados (apenas com seu consentimento)</li>
                <li><strong>Provedores de serviço</strong>: Empresas que nos auxiliam na operação (hosting, análise de dados, suporte)</li>
                <li><strong>Requisitos legais</strong>: Quando exigido por lei ou processo judicial</li>
            </ul>
            <p><strong>Nunca</strong> vendemos seus dados pessoais a terceiros.</p>

            <h2>4. Bases Legais para o Tratamento</h2>
            <p>Todo tratamento de dados é baseado em:</p>
            <ul>
                <li><strong>Consentimento</strong>: Para envio de comunicações e uso de dados sensíveis</li>
                <li><strong>Contrato</strong>: Para execução dos serviços contratados</li>
                <li><strong>Legítimo interesse</strong>: Para melhorar nossos serviços e segurança</li>
                <li><strong>Obrigação legal</strong>: Para cumprir com exigências regulatórias</li>
            </ul>

            <h2>5. Segurança de Dados</h2>
            <p>Implementamos medidas robustas para proteger suas informações:</p>
            <ul>
                <li>Criptografia SSL/TLS para transferência de dados</li>
                <li>Armazenamento em servidores seguros com acesso restrito</li>
                <li>Protocolos de segurança para prevenção de vazamentos</li>
                <li>Treinamento regular de nossa equipe em proteção de dados</li>
            </ul>

            <h2>6. Seus Direitos</h2>
            <p>Conforme a LGPD, você tem direito a:</p>
            <ol>
                <li><strong>Acesso</strong>: Solicitar cópia dos dados que possuímos sobre você</li>
                <li><strong>Correção</strong>: Atualizar informações incompletas ou incorretas</li>
                <li><strong>Exclusão</strong>: Solicitar a eliminação de dados pessoais</li>
                <li><strong>Portabilidade</strong>: Receber dados em formato estruturado</li>
                <li><strong>Revogação</strong>: Retirar consentimentos concedidos</li>
                <li><strong>Oposição</strong>: Questionar tratamentos baseados em legítimo interesse</li>
            </ol>
            <p>Para exercer esses direitos, entre em contato através do e-mail: <a href="mailto:privacidade@caminhodosaber.com">privacidade@caminhodosaber.com</a></p>

            <h2>7. Retenção de Dados</h2>
            <p>Mantemos suas informações apenas pelo tempo necessário para:</p>
            <ul>
                <li>Cumprir finalidades descritas nesta política</li>
                <li>Atender obrigações legais ou regulatórias</li>
                <li>Resolver disputas ou garantir segurança</li>
            </ul>
            <p>Dados de navegação são armazenados por até 12 meses. Dados cadastrais são mantidos enquanto sua conta estiver ativa ou conforme exigido por lei.</p>

            <h2>8. Menores de Idade</h2>
            <p>Nossos serviços são destinados a maiores de 16 anos. Para menores nesta faixa etária, exigimos consentimento dos pais ou responsáveis legais.</p>

            <h2>9. Alterações nesta Política</h2>
            <p>Podemos atualizar esta política periodicamente. Notificaremos sobre mudanças significativas através de:</p>
            <ul>
                <li>Notificações em nosso site</li>
                <li>Comunicações por e-mail (para usuários cadastrados)</li>
            </ul>
            <p>A versão atualizada substituirá automaticamente todas as versões anteriores.</p>

            <h2>10. Contato</h2>
            <p>Para dúvidas sobre esta política ou tratamento de dados:</p>
            <ul>
                <li><strong>Encarregado de Dados (DPO)</strong>: João Silva</li>
                <li><strong>E-mail</strong>: <a href="mailto:dpo@caminhodosaber.com">dpo@caminhodosaber.com</a></li>
                <li><strong>Endereço</strong>: Rua das Academias, 123, São Paulo/SP</li>
            </ul>
            <p>Respostas serão fornecidas em até 15 dias úteis.</p>
        </section>

        <footer>
            <p>Caminho do Saber © 2025 - Todos os direitos reservados</p>
        </footer>
    </div>

    <script>
        // Botão voltar com histórico
        document.getElementById('backButton').addEventListener('click', function() {
            // Verifica se há histórico para voltar
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Se não houver histórico, redireciona para a home
                window.location.href = 'home.php';
            }
        });

        // Adiciona referência à página anterior no URL se vier de outra página
        if (document.referrer && document.referrer.indexOf(window.location.hostname) !== -1) {
            document.getElementById('backButton').setAttribute('data-referrer', document.referrer);
        }
    </script>
</body>

</html>