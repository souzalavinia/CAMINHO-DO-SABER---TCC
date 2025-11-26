<?php
session_start();

// Garante que o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

// =========================================================================
// 1. CONFIGURAÇÃO DE PERMISSÃO E BUSCA DO CÓDIGO DA ESCOLA
// =========================================================================

// Verifica se o tipo de usuário tem permissão de acesso (Ex: 'diretor' ou 'administrador')
$tipoUsuarioSessao = strtolower($_SESSION['tipoUsuario'] ?? '');
// ATENÇÃO: Adapte esta verificação se o seu tipo de usuário for diferente de 'diretor'
if ( $tipoUsuarioSessao !== 'diretor' && $tipoUsuarioSessao !== 'administrador') {
    session_destroy();
    header("Location: /login.php?acessoNegado");
    exit();
}

// Puxa o código da escola do diretor logado
$codigoEscolaDiretor = $_SESSION['codigoEscola'] ?? '';

// Se o diretor não tiver um código de escola, ele não pode ver o relatório
if (empty($codigoEscolaDiretor)) {
    die("Acesso negado: Código de escola não encontrado para o seu perfil.");
}

// Inclui o arquivo de conexão centralizado
// NOTA: O caminho '../conexao/conecta.php' permanece inalterado.
require_once '../conexao/conecta.php';


// =========================================================================
// 2. CONFIGURAÇÃO DO FILTRO DE PROVAS
// =========================================================================

// Busca todas as provas para popular o filtro (não precisa de filtro de escola aqui)
$sqlProvas = "SELECT id, nome FROM tb_prova ORDER BY nome ASC";
$resultProvas = $conn->query($sqlProvas);

$provas = [];
if ($resultProvas) {
    while ($row = $resultProvas->fetch_assoc()) {
        $provas[] = $row;
    }
}

$idProvaFiltrada = isset($_GET['idProva']) ? intval($_GET['idProva']) : 0;
$nomeProvaFiltrada = "Todas as Provas";


// Define a cláusula WHERE base e o array de binding para o Prepared Statement
// A filtragem agora SEMPRE inclui o código da escola do diretor
$whereClauseBase = "u.codigoEscola = ?";
$bindTypes = "s";
$bindParams = [&$codigoEscolaDiretor]; // O primeiro parâmetro SEMPRE será o código da escola

if ($idProvaFiltrada > 0) {
    // Adiciona o filtro de prova à cláusula WHERE
    $whereClauseBase .= " AND t.idProva = ?";
    $bindTypes .= "i";
    $bindParams[] = &$idProvaFiltrada;

    // Busca o nome da prova filtrada
    foreach ($provas as $p) {
        if ($p['id'] == $idProvaFiltrada) {
            $nomeProvaFiltrada = $p['nome'];
            break;
        }
    }
}

// =========================================================================
// 3. CONSULTA PARA O RELATÓRIO AGREGADO (SÓ DA ESCOLA DO DIRETOR X PROVA)
// =========================================================================
$sqlAgregado = "
    SELECT
        p.nome AS nomeProva,
        SUM(t.acertos) AS totalAcertos,
        SUM(t.erros) AS totalErros,
        (SUM(t.acertos) + SUM(t.erros)) AS totalQuestoes,
        ROUND((SUM(t.acertos) * 100.0) / NULLIF((SUM(t.acertos) + SUM(t.erros)), 0), 2) AS percentualAcerto
    FROM 
        tb_tentativas t
    JOIN 
        tb_usuario u ON t.idUsuario = u.id
    JOIN 
        tb_prova p ON t.idProva = p.id
    WHERE
        " . $whereClauseBase . "
    GROUP BY
        p.nome,
        p.id
    ORDER BY
        p.nome ASC;
";

$stmtAgregado = $conn->prepare($sqlAgregado);
if ($stmtAgregado === false) {
    die("Erro na preparação da consulta agregada: " . $conn->error);
}

// Faz o bind dinamicamente para a escola e, opcionalmente, a prova
call_user_func_array([$stmtAgregado, 'bind_param'], array_merge([$bindTypes], $bindParams));

$stmtAgregado->execute();
$resultAgregado = $stmtAgregado->get_result();

$relatorioAgregado = [];
if ($resultAgregado->num_rows > 0) {
    while ($row = $resultAgregado->fetch_assoc()) {
        $relatorioAgregado[] = $row;
    }
}
$stmtAgregado->close();


// =========================================================================
// 4. CONSULTA PARA O RELATÓRIO DETALHADO (ALUNO X PROVA)
// =========================================================================
$sqlDetalhado = "
    SELECT
        u.nomeCompleto,
        p.nome AS nomeProva,
        t.acertos,
        t.erros,
        (t.acertos + t.erros) AS totalQuestoes,
        ROUND((t.acertos * 100.0) / NULLIF((t.acertos + t.erros), 0), 2) AS percentualAcerto,
        t.dataTentativa
    FROM 
        tb_tentativas t
    JOIN 
        tb_usuario u ON t.idUsuario = u.id
    JOIN 
        tb_prova p ON t.idProva = p.id
    WHERE
        " . $whereClauseBase . "
    ORDER BY
        p.nome ASC,
        u.nomeCompleto ASC,
        t.dataTentativa DESC;
";

$stmtDetalhado = $conn->prepare($sqlDetalhado);
if ($stmtDetalhado === false) {
    die("Erro na preparação da consulta detalhada: " . $conn->error);
}

// Faz o bind dinamicamente (reutiliza os mesmos parâmetros)
call_user_func_array([$stmtDetalhado, 'bind_param'], array_merge([$bindTypes], $bindParams));

$stmtDetalhado->execute();
$resultDetalhado = $stmtDetalhado->get_result();

$relatorioDetalhado = [];
if ($resultDetalhado->num_rows > 0) {
    while ($row = $resultDetalhado->fetch_assoc()) {
        $relatorioDetalhado[] = $row;
    }
}
$stmtDetalhado->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório da Escola - <?= htmlspecialchars($codigoEscolaDiretor) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Estilos base assumidos de styleCadastroprovas.css */
        :root {
            --primary-color: #007bff; /* Cor primária */
            --primary-color2: #ff0028; /* Cor primária */
            --dark-text: #333333;
            --light-bg: #f4f7f6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* INÍCIO: CORREÇÃO PARA MENU FIXO E RESPONSIVIDADE DO MAIN (Valores AUMENTADOS) */
        main {
            padding-top: 146px; /* Compensa a altura mínima do menu fixo (AUMENTADO) */
            padding-left: 40px; 
            padding-right: 40px;
            padding-bottom: 40px; 
            flex-grow: 1; 
        }

        @media (min-width: 992px) {
            main {
                padding-top: 200px; /* Mais espaço para o menu em telas maiores (AUMENTADO) */
                padding-left: 120px;
                padding-right: 120px;
            }
        }
        @media (max-width: 768px) {
            main {
                padding-top: 230px; /* Mais espaço para o menu em modo mobile (empilhado) (AUMENTADO) */
                padding-left: 20px;
                padding-right: 20px;
            }
        }
        /* FIM: CORREÇÃO PARA MENU FIXO */


        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 2em;
        }

        h2 {
            color: var(--dark-text);
            margin-top: 20px;
            margin-bottom: 15px;
            font-weight: 500;
            font-size: 1.5em;
        }
        
        .btn-submit {
            background-color: var(--primary-color2);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
            text-decoration: none; /* Adicionado para links */
            display: inline-block; /* Adicionado para links */
        }

        .btn-submit:hover {
            background-color: #0056b3;
        }

        /* Estilos do rodapé (footer) */
        footer {
            text-align: center;
            padding: 15px;
            background-color: #343a40;
            color: white;
            font-size: 0.9em;
            margin-top: auto; 
        }

        footer a {
            color: #cccccc;
            text-decoration: none;
            margin-left: 10px;
        }

        footer a:hover {
            color: white;
        }
        
        /* Estilos Específicos do Relatório */
        .form-container {
            max-width: 1200px; 
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin: 0 auto;
        }
        
        /* Estilos do filtro */
        .filter-form {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .filter-form .form-group {
            display: flex; 
            gap: 10px;
            align-items: flex-end;
        }
        .filter-form select {
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 5px; 
            width: 100%;
        }

        /* Tabelas */
        .table-wrapper {
             overflow-x: auto; /* Adiciona rolagem horizontal para a tabela em telas pequenas */
             margin-bottom: 40px;
        }
        .table-relatorio {
            width: 100%;
            min-width: 600px; 
            border-collapse: collapse;
        }
        .table-relatorio th, .table-relatorio td {
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
        }
        .escola-header, .prova-header {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: left !important;
            font-size: 1.1em;
        }
        .prova-header {
            background-color: #e9e9e9;
            font-size: 1em;
            padding-left: 20px !important;
        }
        .aluno-row td:first-child {
            text-align: left !important;
            padding-left: 30px;
        }
        
        /* ========================================================================= */
        /* MEDIA QUERIES PARA RESPONSIVIDADE (Tabelas -> Cards) */
        /* ========================================================================= */
        
        /* ESTILO PARA O CABEÇALHO COM O BOTÃO DE PDF */
        @media (max-width: 768px) {
            h1 {
                font-size: 1.5em; 
            }
            
            .form-container {
                padding: 15px; 
                margin: 0; 
                max-width: 100%;
            }
            
            /* Ajusta o cabeçalho para empilhar o título e o botão em telas pequenas */
            .form-container > div:first-child { 
                display: flex; 
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
            .form-container > div:first-child h1 {
                margin-bottom: 0;
                width: 100%;
            }
            .form-container > div:first-child .btn-submit {
                width: 100%; /* Faz o botão de download ocupar a largura total em mobile */
                text-align: center;
            }


            /* Filtro: muda para vertical */
            .filter-form .form-group {
                flex-direction: column; 
                gap: 15px;
                align-items: stretch;
            }
            .filter-form select, .btn-submit, .filter-form .form-group > div {
                 width: 100%;
            }

            /* Tabela Detalhada: Transforma em Cards/Lista */
            .table-relatorio-detalhada {
                border: none;
                width: 100%;
                min-width: 100%; 
            }

            .table-relatorio-detalhada thead {
                display: none; 
            }

            .table-relatorio-detalhada tr {
                display: block; 
                margin-bottom: 15px;
                border: 1px solid #ccc;
                border-radius: 5px;
                background-color: #fff;
            }
            
            .table-relatorio-detalhada td {
                display: block; 
                text-align: right !important; 
                border: none;
                padding: 8px 15px;
                position: relative;
            }

            /* Adiciona o rótulo da coluna antes do conteúdo da célula */
            .table-relatorio-detalhada td::before {
                content: attr(data-label); 
                float: left;
                font-weight: bold;
                text-transform: uppercase;
                font-size: 0.8em;
                color: var(--primary-color);
            }
            
            /* Ajusta cabeçalho de Prova no modo lista */
            .prova-header {
                 text-align: center !important;
                 padding: 10px 0 !important;
                 background-color: transparent;
                 border: none;
                 color: var(--dark-text);
                 font-size: 1.1em;
                 font-weight: 600;
            }
            .prova-header td {
                 text-align: center !important;
                 padding: 0 !important;
            }
            .table-relatorio-detalhada .aluno-row td:first-child {
                text-align: right !important; 
                padding-left: 15px;
                font-weight: bold;
            }
            .table-relatorio-detalhada .aluno-row td:first-child::before {
                 content: "ALUNO:";
            }
        }
    </style>
</head>
<body>
    
    <?php include 'menu.php'; ?>

    <main>
        <div class="form-container">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1><i class="fas fa-school"></i> Desempenho da Escola: <?= htmlspecialchars($codigoEscolaDiretor) ?></h1>
                
                <?php 
                // Monta a URL de destino para o script de geração de PDF, passando o filtro
                $pdf_url = 'gerar_pdf_relatorio_tcpdf.php?idProva=' . $idProvaFiltrada; // <-- APONTANDO PARA O NOVO SCRIPT
                ?>

                <a href="<?= $pdf_url ?>" class="btn-submit" style="white-space: nowrap; margin-bottom: 0;">
                    <i class="fas fa-file-pdf"></i> Baixar PDF
                </a>
            </div>
            <form action="relatorioDiretor.php" method="GET" class="filter-form">
                <div class="form-group">
                    <div style="flex-grow: 1;">
                        <label for="idProva" style="display: block; margin-bottom: 5px; font-weight: bold;">Filtrar por Prova:</label>
                        <select id="idProva" name="idProva">
                            <option value="0" <?= ($idProvaFiltrada == 0) ? 'selected' : '' ?>>Todas as Provas (<?= htmlspecialchars(count($provas)) ?>)</option>
                            <?php foreach ($provas as $prova): ?>
                                <option value="<?= htmlspecialchars($prova['id']) ?>" <?= ($idProvaFiltrada == $prova['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prova['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit" style="white-space: nowrap;">
                        <i class="fas fa-filter"></i> Aplicar Filtro
                    </button>
                </div>
                <?php if ($idProvaFiltrada > 0): ?>
                    <p style="margin-top: 10px; font-weight: bold;">
                        <i class="fas fa-check-circle" style="color: green;"></i> Filtro Ativo: <?= htmlspecialchars($nomeProvaFiltrada) ?>
                    </p>
                <?php endif; ?>
            </form>
            <hr style="margin-top: 20px; border-color: #ccc;">
            <h2 style="margin-top: 30px; color: var(--primary-color);">Desempenho Agregado por Prova</h2>
            
            <?php if (empty($relatorioAgregado)): ?>
                <p style="text-align: center; margin-top: 20px;">Nenhuma tentativa de prova encontrada para esta escola ou com o filtro aplicado.</p>
            <?php else: ?>
                
            <div class="table-wrapper">
                <table class="table-relatorio table-relatorio-agregada">
                    <thead>
                        <tr>
                            <th style="width: 50%; text-align: left !important; padding-left: 20px;">Nome da Prova</th>
                            <th>Total Questões</th>
                            <th>Total Acertos</th>
                            <th>Total Erros</th>
                            <th>% de Acerto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relatorioAgregado as $linha): ?>
                        <tr>
                            <td style="text-align: left; padding-left: 20px; font-weight: bold;"><?= htmlspecialchars($linha['nomeProva']) ?></td>
                            <td><?= htmlspecialchars($linha['totalQuestoes']) ?></td>
                            <td style="color: green; font-weight: bold;"><?= htmlspecialchars($linha['totalAcertos']) ?></td>
                            <td style="color: red; font-weight: bold;"><?= htmlspecialchars($linha['totalErros']) ?></td>
                            <td>
                                <span style="font-weight: bold;">
                                    <?= htmlspecialchars($linha['percentualAcerto'] ?? 0) ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div> <?php endif; ?>

            <h2 style="margin-top: 30px; color: var(--primary-color);">Resultados Detalhados por Aluno (Agrupado por Prova)</h2>

            <?php if (empty($relatorioDetalhado)): ?>
                <p style="text-align: center; margin-top: 20px;">Nenhuma tentativa de prova encontrada para esta escola ou com o filtro aplicado.</p>
            <?php else: ?>
                
            <div class="table-wrapper">
                <table class="table-relatorio table-relatorio-detalhada">
                    <thead>
                        <tr>
                            <th>Nome do Aluno</th>
                            <th>Data da Tentativa</th>
                            <th>Total Questões</th>
                            <th>Acertos</th>
                            <th>Erros</th>
                            <th>% Acerto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $provaAtual = '';
                        
                        foreach ($relatorioDetalhado as $linha): 
                            
                            // 1. Destaca mudança de prova
                            if ($provaAtual !== $linha['nomeProva']):
                                $provaAtual = $linha['nomeProva'];
                        ?>
                                <tr>
                                    <td colspan="6" class="prova-header">
                                        PROVA: <?= htmlspecialchars($provaAtual) ?>
                                    </td>
                                </tr>
                        <?php 
                            endif;
                        ?>
                        <tr class="aluno-row">
                            <td data-label="Nome do Aluno:"><?= htmlspecialchars($linha['nomeCompleto']) ?></td>
                            <td data-label="Data da Tentativa:"><?= htmlspecialchars($linha['dataTentativa']) ?></td>
                            <td data-label="Total Questões:"><?= htmlspecialchars($linha['totalQuestoes']) ?></td>
                            <td data-label="Acertos:" style="color: green;"><?= htmlspecialchars($linha['acertos']) ?></td>
                            <td data-label="Erros:" style="color: red;"><?= htmlspecialchars($linha['erros']) ?></td>
                            <td data-label="% Acerto:" style="font-weight: bold;"><?= htmlspecialchars($linha['percentualAcerto'] ?? 0) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div> <?php endif; ?>
        </div>
    </main>

    <script>
    // Script do menu (reutilizado - presumindo que é necessário)
    document.getElementById('userToggle')?.addEventListener('click', function() {
        document.getElementById('userDropdown')?.classList.toggle('show');
    });

    // Fechar menu quando clicar fora
    window.addEventListener('click', function(event) {
        // Usa o seletor do seu menu real: '.cs-user-toggle' (do menu.php)
        // Usa o seletor do seu dropdown real: '.cs-user-dropdown' (do menu.php)
        if (!event.target.closest('.cs-user-menu')) {
            const dropdowns = document.querySelectorAll('.cs-user-dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('cs-show'); // Ajustado para a classe 'cs-show'
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