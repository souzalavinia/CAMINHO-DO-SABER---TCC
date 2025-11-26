<?php
session_start();

// Verificando se está logado
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Inclui o arquivo de conexão centralizado
require_once '../conexao/conecta.php';

// Processar cadastro de novo plano
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar'])) {
    // Coletar dados do formulário
    $nomePlanos = $_POST['nomePlanos'];
    $numAlunos = $_POST['numAlunos'];
    $simulados = $_POST['simulados'] ?? null;
    $redacao = $_POST['redacao'] ?? null;
    $preco = $_POST['preco'] ?? null;

    // Inserir no banco de dados
    $sql = "INSERT INTO tb_planos (nomePlanos, numAlunos, simulados, redacao, preco) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        echo "<script>alert('Erro na preparação da consulta: " . $conn->error . "');</script>";
    } else {
        $stmt->bind_param("sisss", $nomePlanos, $numAlunos, $simulados, $redacao, $preco);
        
        if ($stmt->execute()) {
            echo "<script>alert('Plano cadastrado com sucesso!');</script>";
            // Recarregar a página para mostrar o novo plano
            echo "<script>window.location.href = 'cadastrarPlanos.php';</script>";
            exit();
        } else {
            echo "<script>alert('Erro ao cadastrar plano: " . $stmt->error . "');</script>";
        }
        
        $stmt->close();
    }
}

// Processar exclusão
if (isset($_GET['delete'])) {
    $id_para_excluir = $_GET['delete'];

    // Usando prepared statement para a exclusão
    $sql_delete = "DELETE FROM tb_planos WHERE idPlanos = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    
    if ($stmt_delete === false) {
        echo "<script>alert('Erro ao preparar a exclusão: " . $conn->error . "');</script>";
    } else {
        $stmt_delete->bind_param("i", $id_para_excluir);
        
        if ($stmt_delete->execute()) {
            echo "<script>alert('Plano excluído com sucesso!');</script>";
            // Recarregar a página para atualizar a lista
            echo "<script>window.location.href = 'cadastrarPlanos.php';</script>";
            exit();
        } else {
            echo "<script>alert('Erro ao excluir: " . $stmt_delete->error . "');</script>";
        }
        $stmt_delete->close();
    }
}

// Buscar planos
$sql = "SELECT idPlanos, nomePlanos, numAlunos, simulados, redacao, preco 
        FROM tb_planos 
        ORDER BY idPlanos DESC";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();

$planos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $planos[] = $row;
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
    <title>Cadastro de Planos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/stylePlanos.css">
</head>
<body>
    
    <?php include 'menu.php'; ?>

    <main>
        <div class="form-container">
            <h1><i class="fas fa-cube"></i> Cadastro de Planos</h1>
            
            <form action="cadastrarPlanos.php" method="post">
                <input type="hidden" name="cadastrar" value="1">
                
                <div class="form-group">
                    <label for="nomePlanos">Nome do Plano</label>
                    <input type="text" id="nomePlanos" name="nomePlanos" required>
                </div>
                
                <div class="form-group students-input-container">
                    <label for="numAlunos">Número de Alunos</label>
                    <input type="number" id="numAlunos" name="numAlunos" value="800" required class="form-control students-input">
                </div>

                <div class="form-group">
                    <label for="simulados">Simulados</label>
                    <input type="text" id="simulados" name="simulados" placeholder="Ex: Ilimitados, 10 por mês">
                </div>

                <div class="form-group">
                    <label for="redacao">Redações</label>
                    <input type="text" id="redacao" name="redacao" placeholder="Ex: 3 por semana, Ilimitadas">
                </div>

                <div class="form-group price-input-container">
                    <label for="preco">Preço</label>
                    <input type="number" id="preco" name="preco" step="0.01" placeholder="0.00" class="form-control price-input">
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Cadastrar Plano
                </button>
            </form>
            
            <h2 style="margin-top: 30px; color: var(--primary-color);">Planos Cadastrados</h2>
            
            <?php if (empty($planos)): ?>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <i class="fas fa-info-circle" style="font-size: 48px; color: #6c757d; margin-bottom: 15px;"></i>
                    <p style="color: #6c757d; font-size: 16px;">Nenhum plano cadastrado ainda.</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Alunos</th>
                        <th>Simulados</th>
                        <th>Redações</th>
                        <th>Preço</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($planos as $plano): ?>
                    <tr>
                        <td><?= htmlspecialchars($plano['idPlanos']) ?></td>
                        <td><?= htmlspecialchars($plano['nomePlanos']) ?></td>
                        <td><?= htmlspecialchars($plano['numAlunos']) ?></td>
                        <td><?= htmlspecialchars($plano['simulados'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($plano['redacao'] ?? 'N/A') ?></td>
                        <td>R$ <?= number_format($plano['preco'] ?? 0, 2, ',', '.') ?></td>
                        <td>
                            <a href="editPlano.php?id=<?= htmlspecialchars($plano['idPlanos']) ?>" class="btn-action btn-edit">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="cadastrarPlanos.php?delete=<?= htmlspecialchars($plano['idPlanos']) ?>" class="btn-action btn-delete" onclick="return confirm('Tem certeza que deseja excluir este plano?');">
                                <i class="fas fa-trash"></i> Excluir
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
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

    // Limpar formulário após cadastro bem-sucedido (opcional)
    document.addEventListener('DOMContentLoaded', function() {
        // Verificar se há parâmetros de sucesso na URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success')) {
            document.querySelector('form').reset();
        }
    });
    </script>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
        <a href="../POLITICA.php">Política de privacidade</a>
    </footer>
</body>
</html>