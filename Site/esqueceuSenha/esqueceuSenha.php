<?php
session_start();
require 'conecta.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido!";
    } else {
        $conn = new Conecta();
        $dados = $conn->geraChaveAcesso($email);
        
        if ($dados) {
            $link = "https://www.caminhodosaber.online/esqueceuSenha/redefinir_senha.php?email=".urlencode($dados['email'])."&token=".$dados['chave'];
            
            require '../email/envia.php'; // após gerar o link

$resultado = enviarEmailRedefinicao($dados['email'], $link);

if ($resultado === true) {
    $sucesso = "<strong><i class='fas fa-check-circle'></i> Sucesso!</strong> Enviamos um link de redefinição para <em>$email</em>. Verifique sua caixa de entrada e sua pasta de spam.";

} else {
    $erro = "Erro ao enviar o e-mail: " . $resultado;
}
        } else {
            $erro = "E-mail não encontrado!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Caminho do Saber</title>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d4b9e;
            --primary-dark: #0a3a7a;
            --primary-light: #3a6cb5;
            --primary-extra-light: rgba(13, 75, 158, 0.1);
            --gold-color: #D4AF37;
            --gold-light: #E6C200;
            --gold-dark: #996515;
            --black: #212529;
            --dark-black: #121212;
            --white: #ffffff;
            --light-gray: #f5f7fa;
            --medium-gray: #e0e5ec;
            --dark-gray: #6c757d;
            --success-color: #28a745;
            --error-color: #dc3545;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --gold-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
            color: var(--black);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        .login-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            width: 100%;
        }

        /* Banner Section */
        .banner-section {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-black));
            color: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-right: 3px solid var(--gold-color);
        }

        .banner-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
            z-index: 1;
        }

        .banner-content {
            position: relative;
            z-index: 2;
            max-width: 500px;
        }

        .banner-image {
            width: 100%;
            max-width: 350px;
            height: auto;
            border-radius: var(--border-radius);
            border: 3px solid var(--gold-color);
            box-shadow: var(--gold-shadow);
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .banner-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            line-height: 1.3;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .banner-text {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .highlight {
            font-weight: 600;
            color: var(--gold-color);
            display: block;
            margin-top: 1rem;
            font-size: 1.3rem;
        }

        /* Login Section */
        .login-section {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background-color: var(--white);
        }

        .login-form-container {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: 1px solid var(--gold-light);
        }

        .login-form-container:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15), 0 0 0 1px var(--gold-color);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--gold-color);
            border-radius: 3px;
        }

        .form-subtitle {
            color: var(--dark-gray);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--black);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.9rem 1.2rem;
            font-size: 1rem;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            transition: var(--transition);
            background-color: var(--light-gray);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gold-color);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
            background-color: var(--white);
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 0.9rem;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: var(--white);
            box-shadow: 0 4px 15px rgba(13, 75, 158, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 75, 158, 0.4);
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, 
                          rgba(212, 175, 55, 0.2), 
                          rgba(212, 175, 55, 0.1));
            opacity: 0;
            transition: var(--transition);
        }

        .btn-primary:hover::after {
            opacity: 1;
        }

        .form-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .form-footer a {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
        }

        .form-footer a:hover {
            color: var(--gold-dark);
        }

        .form-footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gold-color);
            transition: var(--transition);
        }

        .form-footer a:hover::after {
            width: 100%;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--dark-gray);
            font-size: 0.8rem;
        }

        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid var(--medium-gray);
        }

        .divider::before {
            margin-right: 1rem;
        }

        .divider::after {
            margin-left: 1rem;
        }

        /* Mensagens */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .login-container {
                grid-template-columns: 1fr;
            }
            
            .banner-section {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .login-form-container {
                padding: 1.5rem;
                box-shadow: none;
                border: none;
            }
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.4s;
        }
        .fade-out {
    animation: fadeOut 6s forwards;
}

@keyframes fadeOut {
    0% { opacity: 1; }
    80% { opacity: 1; }
    100% { opacity: 0; display: none; }
}
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Banner Section -->
        <div class="banner-section">
            <div class="banner-content animate-in">
                <img src="/imagem/estudante7.png" alt="Estudante" class="banner-image delay-1">
                <h2 class="banner-title delay-1">Problemas com sua senha?</h2>
                <p class="banner-text delay-2">Nós podemos te ajudar a recuperar o acesso à sua conta.</p>
                <p class="banner-text delay-2">Basta inserir seu e-mail cadastrado abaixo.</p>
                <span class="highlight delay-2">Sua segurança é nossa prioridade!</span>
            </div>
        </div>

        <div class="login-section">
            <div class="login-form-container animate-in">
                <div class="form-header">
                    <h1 class="form-title">Recuperar Senha</h1>
                    <p class="form-subtitle">Digite seu e-mail para receber o link de recuperação</p>

<?php if (isset($sucesso)): ?>
    <div class="alert alert-success fade-out">
        <?php echo $sucesso; ?>
    </div>
<?php endif; ?>
<?php if (isset($erro)): ?>
    <div class="alert alert-error"><?php echo $erro; ?></div>
<?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="email" class="form-label">E-mail cadastrado</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Digite seu e-mail" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Enviar Link</button>

                    <div class="form-footer">
                        <p>Lembrou sua senha? <a href="/login.html">Faça login</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>