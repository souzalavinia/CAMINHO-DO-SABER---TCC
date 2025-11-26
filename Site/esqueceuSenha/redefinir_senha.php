<?php
session_start();
require 'conecta.php';

$email = filter_var($_GET['email'] ?? '', FILTER_SANITIZE_EMAIL);
$token = $_GET['token'] ?? '';

$conn = new Conecta();
$valido = $conn->validarToken($email, $token);

if (!$valido) {
    die("<div class='alert alert-error'>Link inválido ou expirado. <a href='/esqueceuSenha.php/esqueceuSenha.php'>Solicite um novo link</a></div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novaSenha = $_POST['nova_senha'];
    $confirmarSenha = $_POST['confirmar_senha'];
    
    if (strlen($novaSenha) < 8) {
        $erro = "A senha deve ter no mínimo 8 caracteres!";
    } elseif ($novaSenha !== $confirmarSenha) {
        $erro = "As senhas não coincidem!";
    } else {
        $conn->atualizarSenha($valido['id'], $novaSenha);
        $sucesso = "Senha alterada com sucesso!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Caminho do Saber</title>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <!-- Banner Section -->
        <div class="banner-section">
            <div class="banner-content animate-in">
                <img src="imagem/estudante7.png" alt="Estudante" class="banner-image delay-1">
                <h2 class="banner-title delay-1">Redefina sua senha</h2>
                <p class="banner-text delay-2">Crie uma nova senha segura para proteger sua conta.</p>
                <p class="banner-text delay-2">Recomendamos usar uma combinação de letras, números e símbolos.</p>
                <span class="highlight delay-2">Segurança em primeiro lugar!</span>
            </div>
        </div>

        <!-- Login Section -->
        <div class="login-section">
            <div class="login-form-container animate-in">
                <div class="form-header">
                    <h1 class="form-title">Nova Senha</h1>
                    <p class="form-subtitle">Digite e confirme sua nova senha</p>
                </div>

                <?php if (isset($sucesso)): ?>
                    <div class="alert alert-success"><?php echo $sucesso; ?></div>
                    <div class="form-footer">
                        <a href="login.html" class="btn btn-primary">Voltar para o Login</a>
                    </div>
                <?php else: ?>
                    <?php if (isset($erro)): ?>
                        <div class="alert alert-error"><?php echo $erro; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" id="nova_senha" name="nova_senha" class="form-control" placeholder="********" required minlength="8">
                            <span class="password-toggle" onclick="togglePassword('nova_senha', this)">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>

                        <div class="form-group">
                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" placeholder="Confirme sua nova senha" required>
                            <span class="password-toggle" onclick="togglePassword('confirmar_senha', this)">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>

                        <button type="submit" class="btn btn-primary">Salvar Nova Senha</button>

                        <div class="form-foooter">
                            <p><a href="/">Voltar à página inicial</a></p>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>

    function togglePassword(id) {
        const passwordField = document.getElementById(id);
        const icon = document.querySelector(`[onclick="togglePassword('${id}')"] i`);
        
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        } else {
            passwordField.type = "password";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        }
    }

    // Validação de senha robusta
    function validarSenha(senha) {
        // Mínimo 8 caracteres
        if (senha.length < 8) {
            return { valido: false, mensagem: "A senha deve ter pelo menos 8 caracteres." };
        }
        
        // Pelo menos 1 letra maiúscula
        if (!/[A-Z]/.test(senha)) {
            return { valido: false, mensagem: "A senha deve conter pelo menos 1 letra maiúscula." };
        }
        
        // Pelo menos 1 número
        if (!/[0-9]/.test(senha)) {
            return { valido: false, mensagem: "A senha deve conter pelo menos 1 número." };
        }
        
        // Pelo menos 1 caractere especial
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(senha)) {
            return { valido: false, mensagem: "A senha deve conter pelo menos 1 caractere especial." };
        }
        
        return { valido: true, mensagem: "Senha válida." };
    }

    // Validação ao enviar o formulário
    document.querySelector('form').addEventListener('submit', function(e) {
        const senha = document.getElementById('nova_senha').value;
        const confirmacao = document.getElementById('confirmar_senha').value;
        
        // Valida a força da senha
        const validacao = validarSenha(senha);
        if (!validacao.valido) {
            e.preventDefault();
            alert(validacao.mensagem);
            return false;
        }
        
        // Verifica se as senhas coincidem
        if (senha !== confirmacao) {
            e.preventDefault();
            alert("As senhas não coincidem!");
            return false;
        }
        
        return true;
    });

    // Feedback em tempo real para o usuário
    document.getElementById('nova_senha').addEventListener('input', function() {
        const senha = this.value;
        const forcaSenha = document.getElementById('forca-senha');
        
        if (!forcaSenha) {
            // Cria o elemento de feedback se não existir
            const divFeedback = document.createElement('div');
            divFeedback.id = 'forca-senha';
            divFeedback.style.marginTop = '5px';
            divFeedback.style.fontSize = '0.8rem';
            this.parentNode.appendChild(divFeedback);
        }
        
        const feedback = document.getElementById('forca-senha');
        const validacao = validarSenha(senha);
        
        if (senha.length === 0) {
            feedback.textContent = '';
            feedback.style.color = '';
        } else if (!validacao.valido) {
            feedback.textContent = validacao.mensagem;
            feedback.style.color = 'var(--error-color)';
        } else {
            feedback.textContent = 'Senha forte!';
            feedback.style.color = 'var(--success-color)';
        }
    });

        // Adiciona classes de animação aos elementos
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate-in, .delay-1, .delay-2');
            
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                }, index * 200);
            });
        });
    </script>
</body>
</html>