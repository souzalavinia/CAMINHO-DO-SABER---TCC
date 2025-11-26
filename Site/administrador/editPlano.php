<?php
session_start();

// Verificando se está logado
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Inclui o arquivo de conexão centralizado
require_once '../conexao/conecta.php';

// Buscar dados do plano para edição
$plano = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "SELECT idPlanos, nomePlanos, numAlunos, simulados, redacao, preco 
            FROM tb_planos 
            WHERE idPlanos = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $plano = $result->fetch_assoc();
    }
    
    $stmt->close();
}

// Processar atualização
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idPlanos = $_POST['idPlanos'];
    $nomePlanos = $_POST['nomePlanos'];
    $numAlunos = $_POST['numAlunos'];
    $simulados = $_POST['simulados'] ?? null;
    $redacao = $_POST['redacao'] ?? null;
    $preco = $_POST['preco'] ?? null;

    $sql_update = "UPDATE tb_planos 
                   SET nomePlanos = ?, numAlunos = ?, simulados = ?, redacao = ?, preco = ?
                   WHERE idPlanos = ?";
    
    $stmt_update = $conn->prepare($sql_update);
    
    if ($stmt_update === false) {
        die("Erro na preparação da atualização: " . $conn->error);
    }
    
    $stmt_update->bind_param("sisssi", $nomePlanos, $numAlunos, $simulados, $redacao, $preco, $idPlanos);
    
    if ($stmt_update->execute()) {
        echo "<script>alert('Plano atualizado com sucesso!'); window.location.href = 'cadastrarPlanos.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar plano: " . $stmt_update->error . "'); window.history.back();</script>";
    }
    
    $stmt_update->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Plano</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/stylePlanos.css">
</head>
<body>
    
    <?php include 'menu.php'; ?>

    <main>
        <div class="form-container">
            <h1><i class="fas fa-edit"></i> Editar Plano</h1>
            
            <?php if ($plano): ?>
            <form action="editPlano.php" method="post">
                <input type="hidden" name="idPlanos" value="<?= htmlspecialchars($plano['idPlanos']) ?>">
                
                <div class="form-group">
                    <label for="nomePlanos">Nome do Plano</label>
                    <input type="text" id="nomePlanos" name="nomePlanos" value="<?= htmlspecialchars($plano['nomePlanos']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="numAlunos">Número de Alunos</label>
                    <input type="number" id="numAlunos" name="numAlunos" value="<?= htmlspecialchars($plano['numAlunos']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="simulados">Simulados</label>
                    <input type="text" id="simulados" name="simulados" value="<?= htmlspecialchars($plano['simulados'] ?? '') ?>" placeholder="Ex: Ilimitados, 10 por mês">
                </div>

                <div class="form-group">
                    <label for="redacao">Redações</label>
                    <input type="text" id="redacao" name="redacao" value="<?= htmlspecialchars($plano['redacao'] ?? '') ?>" placeholder="Ex: 3 por semana, Ilimitadas">
                </div>

                <div class="form-group">
                    <label for="preco">Preço (R$)</label>
                    <input type="number" id="preco" name="preco" step="0.01" value="<?= htmlspecialchars($plano['preco'] ?? '') ?>" placeholder="0.00">
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Atualizar Plano
                </button>
                
                <a href="cadastrarPlanos.php" class="btn-action btn-cancel" style="margin-left: 10px;">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </form>
            <?php else: ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    Plano não encontrado.
                </div>
                <a href="cadastrarPlanos.php" class="btn-action btn-cancel">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
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
    </script>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
        <a href="../POLITICA.php">Política de privacidade</a>
    </footer>
</body>
</html>