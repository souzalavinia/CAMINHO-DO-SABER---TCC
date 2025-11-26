<?php
session_start();

// Garante que o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

// Converte o tipo de usuário para minúsculas para garantir a validação
$tipoUsuarioSessao = strtolower($_SESSION['tipoUsuario'] ?? '');

// Verifica se o tipo de usuário tem permissão de acesso
if ( $tipoUsuarioSessao !== 'administrador') {
    // Se não for um diretor ou administrador, destrói a sessão e redireciona
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
}

// Inclui o arquivo de conexão centralizado
require_once '../conexao/conecta.php';

// Verifica se o ID foi fornecido e é numérico
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID da prova inválido.");
}

$id = intval($_GET['id']); // Converte para inteiro

// Usa prepared statement para evitar SQL injection
$stmt = $conn->prepare("SELECT id, nome FROM tb_instituicao WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da consulta: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $prova = $result->fetch_assoc();
} else {
    die("Prova não encontrada.");
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Instituição</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styleInstituição.css">
</head>
<body>
    <?php include 'menu.php'; ?>

    <main>
        <div class="form-container">
            <h1><i class="fas fa-edit"></i> Editar Instituição</h1>
            
            <form action="updateInst.php" method="post">
                <input type="hidden" name="id" value="<?= htmlspecialchars($prova['id']) ?>">
                
                <div class="form-group">
                    <label for="nome">Nome da Instituição</label>
                    <input type="text" id="nome" name="nomeInstituicao" value="<?= htmlspecialchars($prova['nome']) ?>" required>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Atualizar Instituição
                </button>
            </form>
        </div>
    </main>

    <script>
        // Script do menu
        document.getElementById('userToggle')?.addEventListener('click', function() {
            document.getElementById('userDropdown')?.classList.toggle('show');
        });

        // Fechar menu quando clicar fora
        window.addEventListener('click', function(event) {
            const userToggle = document.querySelector('.user-toggle');
            if (!event.target.closest('.user-toggle')) {
                const dropdowns = document.querySelectorAll('.user-dropdown');
                dropdowns.forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
    </script>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
        <a href="../POLITICA.php">Política de privacidade</a>
    </footer>
</body>
</html>