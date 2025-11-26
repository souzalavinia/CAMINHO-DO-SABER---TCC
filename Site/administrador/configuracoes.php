<?php
session_start();

// Garante que o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

// Converte o tipo de usuário para minúsculas para garantir a validação
$tipoUsuarioSessao = strtolower($_SESSION['tipoUsuario'] ?? '');

// Verifica se o tipo de usuário tem permissão de acesso (Se você quer restringir a admins, descomente)
/*
if ( $tipoUsuarioSessao !== 'administrador') {
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
};
*/

require_once '../conexao/conecta.php';

$id = $_SESSION['id'];

$success_message = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : null;
$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;

// Busca dados do usuário
$stmt = $conn->prepare("SELECT nomeCompleto, email, nomeUsuario, telefone, datNasc, metaProvas, tipoUsuario, fotoUsuario, tipoImagem FROM tb_usuario WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da consulta: " . $conn->error);
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$stmt->close();
$conn->close();

// INCLUI O MENU (que contém <html>, <head>, e <body>)
include 'menu.php'; 
?>

<style>
/* ==============================================
CSS EM BUTIDO (styleConfig.css)
==============================================
*/
:root {
    /* Cores baseadas no menu.php */
    --primary-color: #0d4b9e;
    --secondary-color: #D4AF37;
    --text-color: #333333;
    --background-light: #f4f7f6;
    --background-card: #ffffff;
    --border-color: #e0e0e0;
    --success-bg: #d4edda;
    --success-text: #155724;
    --danger-bg: #f8d7da;
    --danger-text: #721c24;
    --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--background-light);
    color: var(--text-color);
    margin: 0;
    padding: 0;
    line-height: 1.6;
}

/* Garante que o conteúdo principal não fique escondido atrás do header fixo (se aplicável) */
main {
    padding-top: 100px; /* Ajuste para dar espaço ao header do menu */
    min-height: 100vh;
    padding-bottom: 50px;
    display: flex;
    justify-content: center;
    align-items: flex-start; /* Alinha o conteúdo ao topo */
}

/* === Container Principal === */
.settings-container {
    width: 95%;
    max-width: 900px;
    background-color: var(--background-card);
    border-radius: 12px;
    box-shadow: var(--shadow);
    padding: 30px;
    margin-top: 30px;
}

.settings-title {
    color: var(--primary-color);
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 30px;
    text-align: center;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 15px;
}

/* === Abas (Tabs) === */
.settings-tabs {
    display: flex;
    justify-content: center;
    margin-bottom: 25px;
    border-bottom: 1px solid var(--border-color);
}

.tab-button {
    background: none;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-color);
    transition: var(--transition);
    border-bottom: 3px solid transparent;
}

.tab-button:hover {
    color: var(--primary-color);
}

.tab-button.active {
    color: var(--primary-color);
    border-bottom-color: var(--secondary-color);
}

.tab-content {
    display: none;
    padding-top: 20px;
}

.tab-content.active {
    display: block;
}

/* === Formulário === */
.settings-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group.full-width {
    grid-column: 1 / -1; /* Ocupa a largura total da grid */
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--primary-color);
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.form-control:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 2px rgba(13, 75, 158, 0.2);
}

/* === Botões e Alertas === */
.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: var(--transition);
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--background-card);
}

.btn-primary:hover {
    background-color: var(--azul-escuro);
    transform: translateY(-1px);
}

.btn-block {
    width: 100%;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 6px;
    font-weight: 500;
}

.alert-success {
    background-color: var(--success-bg);
    color: var(--success-text);
    border-color: #c3e6cb;
}

.alert-danger {
    background-color: var(--danger-bg);
    color: var(--danger-text);
    border-color: #f5c6cb;
}

/* === Avatar e Upload === */
.avatar-container {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 10px;
}

.avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary-color);
}

.avatar-upload {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

/* === Senha (Security Tab) === */
.password-container {
    position: relative;
    display: flex;
    align-items: center;
}

.password-container .form-control {
    padding-right: 40px; /* Espaço para o ícone */
}

.toggle-password {
    position: absolute;
    right: 12px;
    cursor: pointer;
    color: var(--primary-color);
    font-size: 0.9rem;
}

.form-text {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 5px;
    display: block;
}

.form-footer {
    grid-column: 1 / -1;
    text-align: right;
    font-size: 0.9rem;
    margin-top: 5px;
}
.form-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

/* === Responsividade === */
@media (max-width: 768px) {
    .settings-container {
        margin-top: 20px;
        padding: 20px 15px;
    }
    .settings-form {
        grid-template-columns: 1fr; /* Coluna única em telas menores */
    }
    .settings-title {
        font-size: 1.5rem;
    }
    .avatar-container {
        justify-content: center;
        flex-direction: column;
    }
    .avatar-upload {
        align-items: center;
        text-align: center;
    }
}
</style>

<main>

    <div class="settings-container animate-in">
        <h1 class="settings-title">Configurações</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="settings-tabs">
            <button class="tab-button active" data-tab="profile">Perfil</button>
            <button class="tab-button" data-tab="security">Segurança</button>
        </div>
        
        <div id="profile" class="tab-content active">
           <form method="POST" action="updatePerfil.php" class="settings-form" enctype="multipart/form-data">
                <div class="form-group full-width">
                    <div class="avatar-container">
                        <img src="<?php 
                            if (!empty($usuario['fotoUsuario'])) {
                                if (is_resource($usuario['fotoUsuario'])) {
                                    $usuario['fotoUsuario'] = stream_get_contents($usuario['fotoUsuario']);
                                }
                                echo 'data:' . htmlspecialchars($usuario['tipoImagem']) . ';base64,' . base64_encode($usuario['fotoUsuario']);
                            } else {
                                echo 'https://via.placeholder.com/80';
                            }
                        ?>" alt="Avatar" class="avatar">
                        <div class="avatar-upload">
                            <input type="file" id="fotoUsuario" name="fotoUsuario" accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('fotoUsuario').click()">Alterar Foto</button>
                            <small class="form-text">Formatos permitidos: JPG, PNG (Max. 2MB)</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="nomeCompleto" class="form-label">Nome completo</label>
                    <input type="text" id="nomeCompleto" name="nomeCompleto" class="form-control" required value="<?php echo htmlspecialchars($usuario['nomeCompleto']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($usuario['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="nomeUsuario" class="form-label">Nome de usuário</label>
                    <input type="text" id="nomeUsuario" name="nomeUsuario" class="form-control" required value="<?php echo htmlspecialchars($usuario['nomeUsuario']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" class="form-control" oninput="mascTelefone(this)" required value="<?php echo htmlspecialchars($usuario['telefone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="datNasc" class="form-label">Data de Nascimento</label>
                    <input type="text" id="datNasc" name="datNasc" class="form-control" oninput="aplicarMascara(this)" required value="<?php echo htmlspecialchars($usuario['datNasc']); ?>">
                </div>
    
                <div class="form-group">
                    <label for="tipoUsuario" class="form-label">Tipo Usuário</label>
                    <input type="text" id="tipoUsuario" name="tipoUsuario" min="0" class="form-control" required value="<?php echo htmlspecialchars($usuario['tipoUsuario']); ?>" readonly>
                </div>
                
                <div class="form-group full-width">
                    <button type="submit" class="btn btn-primary btn-block">Salvar Alterações</button>
                </div>
            </form>
        </div>
        
        <div id="security" class="tab-content">
            <form method="POST" action="updateSenha.php" class="settings-form" onsubmit="return validarSenha()">
                <div class="form-group full-width">
                    <label for="current_password" class="form-label">Senha Atual</label>
                    <div class="password-container">
                        <input type="password" id="current_password" name="senhaAtual" class="form-control" placeholder="Digite sua senha atual" required>
                        <span class="toggle-password" onclick="togglePassword('current_password')">
                            <i class="far fa-eye"></i>
                        </span>
                        <div class="form-footer">
                                <p>Esqueceu a senha?<a href="../esqueceuSenha/esqueceuSenha.php"> Clique aqui!</a></p>
                            </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_password" class="form-label">Nova Senha</label>
                    <div class="password-container">
                        <input type="password" id="new_password" name="novaSenha" class="form-control" placeholder="Digite uma nova senha" required>
                        <span class="toggle-password" onclick="togglePassword('new_password')">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                    <small class="form-text">Mínimo 8 caracteres, com 1 letra maiúscula, 1 número e 1 caractere especial</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                    <div class="password-container">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirme a nova senha" required>
                        <span class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <button type="submit" class="btn btn-primary btn-block">Alterar Senha</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// Funções JavaScript (anteriormente em scriptConfig.js)

function mascTelefone(field) {
    field.value = field.value.replace(/\D/g, "")
        .replace(/^(\d{2})(\d)/g, "($1) $2")
        .replace(/(\d)(\d{4})$/, "$1-$2");
}

function aplicarMascara(field) {
    field.value = field.value.replace(/\D/g, "")
        .replace(/(\d{2})(\d)/, "$1/$2")
        .replace(/(\d{2})(\d)/, "$1/$2")
        .replace(/(\d{4})\d+?$/, "$1");
}

function togglePassword(id) {
    const field = document.getElementById(id);
    // Ajustado para buscar o ícone corretamente dentro do elemento pai
    const icon = field.closest('.password-container').querySelector('.toggle-password i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function validarSenha() {
    const novaSenha = document.getElementById('new_password').value;
    const confirmaSenha = document.getElementById('confirm_password').value;

    if (novaSenha !== confirmaSenha) {
        alert('A nova senha e a confirmação de senha não coincidem!');
        return false;
    }
    
    // Expressão regular para forçar 8 caracteres, maiúscula, número e especial
    const regex = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]).{8,}$/;
    if (!regex.test(novaSenha) && novaSenha.length > 0) {
        alert('A nova senha deve ter no mínimo 8 caracteres, incluindo pelo menos uma letra maiúscula, um número e um caractere especial.');
        return false;
    }

    return true;
}

// Lógica de Abas
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab-button');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.getAttribute('data-tab');

            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            contents.forEach(content => {
                content.classList.remove('active');
                if (content.id === targetId) {
                    content.classList.add('active');
                }
            });
        });
    });
});
</script>