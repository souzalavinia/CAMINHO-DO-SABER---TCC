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

// ATUALIZAÇÃO 1: Inclui o campo 'serial' na busca
$stmt = $conn->prepare("SELECT id, nome, anoProva, simulado, serial FROM tb_prova WHERE id = ?");
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
    <title>Editar Prova</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styleCadastroprovas.css">
</head>
<body>
    <?php include 'menu.php'; ?>

    <main>
        <div class="form-container">
            <h1><i class="fas fa-edit"></i> Editar Prova</h1>
            
            <form action="updateProva.php" method="post">
                <input type="hidden" name="id" value="<?= htmlspecialchars($prova['id']) ?>">
                
                <div class="form-group">
                    <label for="nome">Nome da Prova</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($prova['nome']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="ano">Ano da Prova</label>
                    <input type="text" id="ano" name="anoProva" value="<?= htmlspecialchars($prova['anoProva']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="serial">Serial da Prova (Código de Acesso)</label>
                    <input type="text" id="serial" name="serial" value="<?= htmlspecialchars($prova['serial'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="simulado">É Simulado?</label>
                    <select id="simulado" name="simulado" required>
                        <option value="não" <?= (isset($prova['simulado']) && $prova['simulado'] == 'não') ? 'selected' : '' ?>>Não</option>
                        <option value="sim" <?= (isset($prova['simulado']) && $prova['simulado'] == 'sim') ? 'selected' : '' ?>>Sim</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Atualizar Prova
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