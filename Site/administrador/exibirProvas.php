<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

$tipoUsuarioSessao = strtolower(trim($_SESSION['tipoUsuario'] ?? ''));

if ($tipoUsuarioSessao !== 'administrador') {
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
}

// Conexão e Busca de Instituições para Filtro (AINDA NECESSÁRIO PARA OS BOTÕES DE FILTRO)
require_once '../conexao/conecta.php';

$instituicoes = []; 

if (!$conn->connect_error) {
    // Busca todos os nomes de instituições para os botões de filtro
    $sql_instituicoes = "SELECT DISTINCT id, nome FROM tb_instituicao ORDER BY nome ASC"; 
    $result_inst = $conn->query($sql_instituicoes);

    if ($result_inst && $result_inst->num_rows > 0) {
        while ($row_inst = $result_inst->fetch_assoc()) {
            // Guarda o nome (uppercase e trim) para usar no JavaScript do filtro
            $instituicoes[$row_inst['id']] = trim(strtoupper($row_inst['nome']));
        }
    }
}
// A conexão ($conn) é mantida aberta.
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provas - Caminho do Saber</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/StyleExibirProvas.css"/> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <?php include 'menu.php'; ?>

    <div class="main-container">
        <h1 class="page-title">Provas Disponíveis</h1>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="filters-container">
            <button class="filter-btn all active" onclick="filterProvas('all')">Todas</button>
            <?php
            // Gera os botões de filtro. O valor é o NOME da instituição.
            foreach ($instituicoes as $instituicao_id => $instituicao_nome) {
                // A classe é o nome (minúsculo) e o valor para o JS é o nome (maiúsculo)
                $btn_class = strtolower(str_replace(' ', '-', $instituicao_nome));
                $btn_text = htmlspecialchars($instituicao_nome); 
                echo "<button class='filter-btn $btn_class' onclick=\"filterProvas('$btn_text')\">$btn_text</button>";
            }
            ?>
        </div>

        <div class="search-container">
            <form method="GET">
                <div class="input-container">
                    <input type="text" id="nome" name="nome" placeholder="Pesquise por provas..." value="<?php echo isset($_GET['nome']) ? htmlspecialchars($_GET['nome']) : ''; ?>">
                    <button type="submit"><i class="fas fa-search"></i> Buscar</button>
                </div>
            </form>
        </div>

        <?php if (isset($_GET['nome']) && !empty($_GET['nome'])): ?>
    <div class="search-results">
        <h2>Resultados encontrados:</h2>
        <div class="provas-list">
            <?php
            if ($conn->connect_error) {
                die("Erro na conexão: " . $conn->connect_error);
            }

            $nome_busca = $_GET['nome'];
            
            // Query usando LEFT JOIN para buscar o nome da instituição (apenas para filtro/data-type)
            $sql = "SELECT p.*, i.nome AS nome_instituicao 
                    FROM tb_prova p
                    LEFT JOIN tb_instituicao i ON p.id_instituicao = i.id
                    WHERE p.nome LIKE ?";
            
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                die("Erro ao preparar a consulta: " . $conn->error);
            }

            $nome_like = "%" . $nome_busca . "%";
            $stmt->bind_param("s", $nome_like);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Obtém o tipo de prova APENAS para o filtro JavaScript (data-type)
                    $tipoProva = !empty($row['nome_instituicao']) ? strtoupper($row['nome_instituicao']) : 'NÃO CLASSIFICADO';
                    
                    // O item de prova NÃO receberá classe de cor nem tag (badge)
                    echo "<div class='prova-item' data-type='$tipoProva'>"; 
                    echo "<a href='mostraQuest.php?id=" . $row['id'] . "' class='prova-link'>" . $row['nome'] . "</a>"; // TAG REMOVIDA
                    echo "<div class='prova-actions'>";
                    echo "<a href='mostraQuest.php?id=" . $row['id'] . "' class='action-btn view-btn'><i class='fas fa-eye'></i> Visualizar</a>";
                    echo "</div>";
                    echo "</div>";
                }
            } else {
                echo "<div class='no-provas'>";
                echo "<i class='fas fa-file-search'></i>";
                echo "<p>Nenhum resultado encontrado para sua pesquisa.</p>";
                echo "</div>";
            }

            $stmt->close();
            ?>
        </div>
    </div>
<?php else: ?>
    <div class="provas-list" id="provasList">
        <?php
        if ($conn->connect_error) {
            die("Conexão falhou: " . $conn->connect_error);
        }

        // Query usando LEFT JOIN para buscar o nome da instituição (apenas para filtro/data-type)
        $sql = "SELECT p.id, p.nome, p.anoProva, i.nome AS nome_instituicao 
                FROM tb_prova p
                LEFT JOIN tb_instituicao i ON p.id_instituicao = i.id
                ORDER BY p.anoProva DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $currentYear = null;
            $yearCount = 0;
            
            while ($row = $result->fetch_assoc()) {
                // Obtém o tipo de prova APENAS para o filtro JavaScript (data-type)
                $tipoProva = !empty($row['nome_instituicao']) ? strtoupper($row['nome_instituicao']) : 'NÃO CLASSIFICADO'; 
                
                if ($row['anoProva'] !== $currentYear) {
                    if ($currentYear !== null) {
                        echo "</div>"; 
                    }
                    $currentYear = $row['anoProva'];
                    $yearCount = 1;
                    echo "<div class='year-group' data-year='$currentYear'>";
                    echo "<div class='year-title'>$currentYear <span class='year-count'>0</span></div>"; 
                } else {
                    $yearCount++;
                }
                
                // O item de prova NÃO receberá classe de cor nem tag (badge)
                echo "<div class='prova-item' data-type='$tipoProva'>";
                echo "<a href='mostraQuest.php?id=" . $row['id'] . "' class='prova-link'>" . $row['nome'] . "</a>"; // TAG REMOVIDA
                echo "<div class='prova-actions'>";
                echo "<a href='mostraQuest.php?id=" . $row['id'] . "' class='action-btn view-btn'><i class='fas fa-eye'></i> Visualizar</a>";
                echo "</div>";
                echo "</div>";
            }
            
            if ($currentYear !== null) {
                echo "</div>"; 
            }
        } else {
            echo "<div class='no-provas'>";
            echo "<i class='fas fa-file-alt'></i>";
            echo "<p>Nenhuma prova disponível no momento.</p>";
            echo "</div>";
        }

        if (isset($conn)) {
            $conn->close();
        }
        ?>
    </div>
<?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 Caminho do Saber. Todos os direitos reservados.</p>
        <a href="POLITICA.php">Política de privacidade</a>
    </footer>

    <script>
        function filterProvas(type) {
            // Atualiza botões ativos
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.classList.contains(type.toLowerCase().replace(/\s/g, '-')) || (type === 'all' && btn.classList.contains('all'))) {
                    btn.classList.add('active');
                }
            });

            // Mostra/oculta provas
            document.querySelectorAll('.prova-item').forEach(item => {
                if (type === 'all') {
                    item.style.display = 'flex';
                } else {
                    const itemType = item.getAttribute('data-type');
                    // Compara o tipo em maiúsculo para ser robusto
                    item.style.display = itemType.toUpperCase() === type.toUpperCase() ? 'flex' : 'none';
                }
            });

            // Ajusta a visibilidade dos grupos de ano e atualiza contadores
            document.querySelectorAll('.year-group').forEach(group => {
                const visibleItems = Array.from(group.querySelectorAll('.prova-item'))
                    .filter(item => item.style.display !== 'none');
                
                group.style.display = visibleItems.length > 0 ? 'block' : 'none';
                
                if (visibleItems.length > 0) {
                    const yearCount = group.querySelector('.year-count');
                    if (yearCount) {
                        yearCount.textContent = visibleItems.length;
                    }
                }
            });
        }

        // Inicializa mostrando todas as provas
        document.addEventListener('DOMContentLoaded', function() {
            filterProvas('all');
        });

        
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
</body>
</html>