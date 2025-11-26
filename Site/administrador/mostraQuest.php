<?php
// Inicie a sessão ANTES de qualquer saída
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verifique se o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

// 2. Converta o tipo de usuário para minúsculas e remova espaços
$tipoUsuarioSessao = strtolower(trim($_SESSION['tipoUsuario'] ?? ''));

// 3. Verifique se o tipo de usuário tem permissão para acessar a página
// Neste exemplo, a página é restrita a 'diretor' e 'administrador'.
// Adapte a lógica conforme a necessidade de cada página.
if ($tipoUsuarioSessao !== 'administrador') {
    // Se o usuário não tiver a permissão necessária,
    // a sessão é destruída e ele é redirecionado para o login com uma mensagem de negação.
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
}

// A partir daqui, o código só será executado se o usuário estiver logado
// e tiver o tipo de permissão correto (diretor ou administrador).


require_once '../conexao/conecta.php';

$idProva = $_GET['id'];

// Query para obter o nome da prova
$sqlNome = "SELECT nome FROM tb_prova WHERE id = ?";
$stmtNome = $conn->prepare($sqlNome);
$stmtNome->bind_param("i", $idProva);
$stmtNome->execute();
$resultNome = $stmtNome->get_result();

if ($resultNome->num_rows > 0) {
    $rowNome = $resultNome->fetch_assoc();
    $prova_nome = htmlspecialchars($rowNome['nome']);
} else {
    $prova_nome = "Prova não encontrada";
}
$stmtNome->close();

// Mensagens de feedback
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $prova_nome; ?> - Caminho do Saber</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d4b9e;
            --primary-dark: #0a3a7a;
            --gold-color: #D4AF37;
            --gold-dark: #996515;
            --black: #212529;
            --white: #ffffff;
            --light-gray: #f5f7fa;
            --medium-gray: #e0e5ec;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--light-gray);
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .btn-voltar {
            background: var(--primary-color);
            color: var(--white);
            padding: 10px 20px;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-voltar:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        h1 {
            text-align: center;
            color: var(--primary-dark);
            margin-bottom: 30px;
        }

        .questao {
            margin-bottom: 30px;
            border-bottom: 1px solid var(--medium-gray);
            padding-bottom: 20px;
            position: relative;
        }

        .questao h2 {
            color: var(--primary-color);
            font-size: 1.4rem;
        }

        .questao-actions {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            gap: 5px;
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: var(--border-radius);
            font-size: 0.8rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .edit-btn {
            background-color: var(--gold-color);
            color: var(--black);
        }

        .edit-btn:hover {
            background-color: var(--gold-dark);
        }

        .delete-btn {
            background-color: #dc3545;
            color: var(--white);
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        img {
            display: block;
            margin: 10px auto;
            border-radius: 5px;
            max-width: 100%;
            height: auto;
        }

        .alternativa {
            display: block;
            margin: 10px 0;
            background: var(--light-gray);
            padding: 12px;
            border-radius: 5px;
        }

        .alternativa.correta {
            background: #d4edda;
            font-weight: bold;
            border-left: 5px solid #28a745;
        }

        .alert {
            padding: 15px;
            margin: 20px auto;
            border-radius: var(--border-radius);
            text-align: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .questao-actions {
                position: static;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="exibirProvas.php" class="btn-voltar">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <h1><?php echo $prova_nome; ?></h1>

    <?php
    // Query para obter as questões
    $sql = "SELECT * FROM tb_quest WHERE prova = ? ORDER BY numQuestao";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idProva);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='questao'>";
            
            // Botões de ação para cada questão
            echo "<div class='questao-actions'>";
            echo "<a href='editarQuestao.php?id=" . $row['id'] . "' class='action-btn edit-btn'><i class='fas fa-edit'></i> Editar</a>";
            echo "<a href='excluirQuestao.php?id=" . $row['id'] . "' class='action-btn delete-btn' onclick='return confirm(\"Tem certeza que deseja excluir esta questão?\")'><i class='fas fa-trash'></i> Excluir</a>";
            echo "</div>";
            
            echo "<h2>Questão " . htmlspecialchars($row['numQuestao']) . "</h2>";
            
            if (!empty($row['foto'])) {
                echo "<img src='data:" . htmlspecialchars($row['tipo']) . ";base64," . base64_encode($row['foto']) . "' alt='Imagem da questão' />";
            }

            echo "<p>" . htmlspecialchars($row['quest']) . "</p>";

            $alternativas = [
                'A' => $row['alt_a'],
                'B' => $row['alt_b'],
                'C' => $row['alt_c'],
                'D' => $row['alt_d'],
                'E' => $row['alt_e']
            ];

            foreach ($alternativas as $letra => $alternativa) {
                if (!empty($alternativa)) {
                    $classe = ($row['alt_corre'] == $letra) ? 'alternativa correta' : 'alternativa';
                    echo "<div class='$classe'><strong>$letra)</strong> " . htmlspecialchars($alternativa) . "</div>";
                }
            }
            echo "</div>";
        }
    } else {
        echo "<p>Nenhuma questão encontrada para esta prova.</p>";
    }

    $stmt->close();
    ?>
</div>

</body>
</html>
