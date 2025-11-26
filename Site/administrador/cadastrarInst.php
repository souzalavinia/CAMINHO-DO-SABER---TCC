<?php
session_start();

// Verificando se está logado
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Inclui o arquivo de conexão centralizado
require_once '../conexao/conecta.php';

// Processar exclusão com prepared statement para segurança
if (isset($_GET['delete'])) {
    $id_para_excluir = $_GET['delete'];

    // Exclui primeiro as questões relacionadas à prova
    $sqlQuestoes = "DELETE FROM tb_prova WHERE id_instituicao = ?";
    $stmtQuestoes = $conn->prepare($sqlQuestoes);

    if ($stmtQuestoes === false) {
        echo "Erro na preparação da consulta (questões): " . $conn->error;
        exit();
    } else {
        $stmtQuestoes->bind_param("i", $id_para_excluir);
        if (!$stmtQuestoes->execute()) {
            echo "Erro na execução da consulta (questões): " . $stmtQuestoes->error;
            exit();
        }
        $stmtQuestoes->close();
    }
        
    // Usando prepared statement para a exclusão
    $sql_delete = "DELETE FROM tb_instituicao WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    
    if ($stmt_delete === false) {
        echo "<script>alert('Erro ao preparar a exclusão: " . $conn->error . "');</script>";
    } else {
        $stmt_delete->bind_param("i", $id_para_excluir);
        
        if ($stmt_delete->execute()) {
            echo "<script>alert('Prova excluída com sucesso!');</script>";
        } else {
            echo "<script>alert('Erro ao excluir: " . $stmt_delete->error . "');</script>";
        }
        $stmt_delete->close();
    }
}

    // Buscar provas existentes em ordem decrescente (última cadastrada primeiro)
    $sql = "SELECT id, nome FROM tb_instituicao ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Erro na preparação da consulta: " . $conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $provas = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $provas[] = $row;
            }
        }
        $stmt->close();
        $conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Provas</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styleInstituição.css">
</head>
<body>
    
    <?php include 'menu.php'; ?>

    <main>
        <div class="form-container">
            <h1><i class="fas fa-file-alt"></i> Cadastro de Instituições</h1>
            
            <form action="insertInst.php" method="post">
                <div class="form-group">
                    <label for="nome">Nome da Prova</label>
                    <input type="text" id="nome" name="nomeInstituicao" required>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Cadastrar Instituição
                </button>
            </form>
            
            <h2 style="margin-top: 30px; color: var(--primary-color);">Instituição Cadastradas</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($provas as $prova): ?>
                    <tr>
                        <td><?= htmlspecialchars($prova['id']) ?></td>
                        <td><?= htmlspecialchars($prova['nome']) ?></td>
                        <td>
                            <a href="editInst.php?id=<?= htmlspecialchars($prova['id']) ?>" class="btn-action btn-edit">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="cadastrarInst.php?delete=<?= htmlspecialchars($prova['id']) ?>" class="btn-action btn-delete" onclick="return confirm('Tem certeza que deseja excluir esta prova?');">
                                <i class="fas fa-trash"></i> Excluir
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
    // Menu do usuário
    document.getElementById('userToggle').addEventListener('click', function() {
        document.getElementById('userDropdown').classList.toggle('show');
    });

    // Fechar menu quando clicar fora
    window.addEventListener('click', function(event) {
        if (!event.target.matches('.user-toggle') && !event.target.closest('.user-toggle')) {
            var dropdowns = document.getElementsByClassName("user-dropdown");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    });
    </script>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
        <a href="../POLITICA.php">Política de privacidade</a>
    </footer>
</body>
</html>
