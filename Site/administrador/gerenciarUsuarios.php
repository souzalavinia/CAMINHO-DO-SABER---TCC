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
// CERTIFIQUE-SE DE QUE ESTE CAMINHO ESTÁ CORRETO!
require_once '../conexao/conecta.php';

$tabela = 'tb_usuario';
$conn_pagination = $conn; 

// --- CONFIGURAÇÃO DA PAGINAÇÃO ---
$limite_por_pagina = 50;
$pagina_atual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($pagina_atual - 1) * $limite_por_pagina;

// --- CONFIGURAÇÃO DA BUSCA ---
$termo_busca = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$bind_types = '';
$bind_params = [];

if (!empty($termo_busca)) {
    // Adiciona cláusula WHERE para buscar em nomeCompleto, nomeUsuario ou email
    $where_clause = " WHERE nomeCompleto LIKE ? OR nomeUsuario LIKE ? OR email LIKE ?";
    $like_termo = "%" . $termo_busca . "%";
    $bind_types = "sss";
    $bind_params = [$like_termo, $like_termo, $like_termo];
}

// --- Processar Exclusão (REVISADO) ---
if (isset($_GET['delete'])) {
    $id_para_excluir = (int)$_GET['delete'];

    // ATENÇÃO: Se o banco tem Foreign Keys, a exclusão pode falhar aqui.
    // É recomendado tratar a exclusão de dados relacionados (provas, notas, etc.) antes!
    
    $sql_delete = "DELETE FROM $tabela WHERE id = ?";
    $stmt_delete = $conn_pagination->prepare($sql_delete);
    
    if ($stmt_delete === false) {
        echo "<script>alert('ERRO: Falha ao preparar a exclusão: " . $conn_pagination->error . "');</script>";
    } else {
        $stmt_delete->bind_param("i", $id_para_excluir);
        
        if ($stmt_delete->execute()) {
            // Sucesso na exclusão
            
            // Reconstroi a URL de redirecionamento para manter a página e a busca
            $redirect_url = "gerenciarUsuarios.php?page=" . $pagina_atual;
            if (!empty($termo_busca)) {
                $redirect_url .= "&search=" . urlencode($termo_busca);
            }
            
            header("Location: " . $redirect_url);
            exit(); // O EXIT é crucial!
        } else {
            // Se a execução da query falhar (geralmente Foreign Keys)
            $error_msg = "Não foi possível excluir o usuário. Verifique se ele possui dados (provas, notas) vinculados em outras tabelas.";
             echo "<script>alert('ERRO: {$error_msg}'); window.location='gerenciarUsuarios.php';</script>";
             exit(); 
        }
        $stmt_delete->close();
    }
}
// --- Fim Processar Exclusão ---

// 1. Contar o total de registros (com ou sem busca)
$sql_count = "SELECT COUNT(id) AS total FROM $tabela" . $where_clause;
$stmt_count = $conn_pagination->prepare($sql_count);

if (!empty($bind_params)) {
    $stmt_count->bind_param($bind_types, ...$bind_params);
}

$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_registros = $count_result->fetch_assoc()['total'];
$stmt_count->close();

$total_paginas = ceil($total_registros / $limite_por_pagina);

// Ajusta a página atual
if ($pagina_atual > $total_paginas && $total_paginas > 0) {
    $pagina_atual = $total_paginas;
    $offset = ($pagina_atual - 1) * $limite_por_pagina;
} elseif ($pagina_atual < 1) {
    $pagina_atual = 1;
    $offset = 0;
}


// 2. Buscar dados (com busca e paginação)
$sql_data = "SELECT id, nomeCompleto, email, nomeUsuario, tipoUsuario FROM $tabela" . $where_clause . " ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt_data = $conn_pagination->prepare($sql_data);

if ($stmt_data === false) {
    die("Erro na preparação da consulta de dados: " . $conn_pagination->error);
}

$data_bind_types = $bind_types . "ii"; 
$data_bind_params = array_merge($bind_params, [$limite_por_pagina, $offset]);

$stmt_data->bind_param($data_bind_types, ...$data_bind_params);

$stmt_data->execute();
$result_data = $stmt_data->get_result();

$usuarios = [];
if ($result_data->num_rows > 0) {
    while ($row = $result_data->fetch_assoc()) {
        $usuarios[] = $row;
    }
}
$stmt_data->close();
$conn_pagination->close();

// Função auxiliar para gerar links de paginação com o termo de busca
function get_pagination_link($page, $search_term) {
    $link = "gerenciarUsuarios.php?page=" . $page;
    if (!empty($search_term)) {
        $link .= "&search=" . urlencode($search_term);
    }
    return $link;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --azul-primario: #0d4b9e;
            --azul-escuro: #0a3a7a;
            --gold-color: #D4AF37;
            --branco: #ffffff;
            --cinza-claro: #f4f7f6;
            --cinza-borda: #e0e0e0;
            --sombra: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transicao: all 0.3s ease;
            
            /* CORES DE TAGS */
            --tag-estudante-bg: #ffe082; /* Amarelo Claro */
            --tag-estudante-text: #e65100; /* Laranja Escuro */
            --tag-diretor-bg: #e1bee7; /* Roxo Claro */
            --tag-diretor-text: #4a148c; /* Roxo Escuro */
            --tag-administrador-bg: #ffcdd2; /* Vermelho Claro */
            --tag-administrador-text: #b71c1c; /* Vermelho Escuro */
            --tag-default-bg: #e0e0e0;
            --tag-default-text: #424242;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--cinza-claro);
            margin: 0;
            padding: 0;
        }

        main {
            padding-top: 100px; 
            padding-bottom: 50px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .form-container {
            width: 95%;
            max-width: 1200px;
            background-color: var(--branco);
            padding: 30px;
            margin: 20px 0;
            border-radius: 12px;
            box-shadow: var(--sombra);
        }

        h1 {
            color: var(--azul-primario);
            font-size: 2rem;
            border-bottom: 2px solid var(--gold-color);
            padding-bottom: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h2 {
            font-size: 1.2rem;
            color: var(--azul-escuro);
            margin-top: 30px;
            margin-bottom: 15px;
        }

        /* --- Botão Novo Usuário --- */
        .btn-submit {
            background-color: var(--azul-primario);
            color: var(--branco);
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transicao);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background-color: var(--azul-escuro);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* --- Formulário de Busca --- */
        .search-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap; 
            gap: 15px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex-grow: 1;
            max-width: 400px;
        }

        .search-form input[type="search"] {
            padding: 10px 15px;
            border: 1px solid var(--cinza-borda);
            border-radius: 6px;
            flex-grow: 1;
            font-size: 1rem;
        }

        .search-form button {
            background-color: var(--gold-color);
            color: var(--azul-escuro);
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transicao);
        }
        
        .btn-clear-search {
            background-color: #6c757d !important; 
            color: var(--branco) !important;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transicao);
            display: inline-flex;
            align-items: center;
        }

        /* --- Estilos da Tabela --- */
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            background-color: var(--branco);
            min-width: 700px;
        }

        thead tr {
            background-color: var(--azul-primario);
            color: var(--branco);
            text-align: left;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* --- Tags de Tipo de Usuário --- */
        .user-tag {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .tag-estudante {
            background-color: var(--tag-estudante-bg);
            color: var(--tag-estudante-text);
        }

        .tag-diretor {
            background-color: var(--tag-diretor-bg);
            color: var(--tag-diretor-text);
        }

        .tag-administrador {
            background-color: var(--tag-administrador-bg);
            color: var(--tag-administrador-text);
        }

        /* --- Botões de Ação na Tabela (Com ícone e texto) --- */
        td:last-child {
            white-space: nowrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            margin-right: 5px;
            border-radius: 6px; 
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transicao);
            gap: 5px;
        }

        .btn-edit { 
            background-color: #007bff; /* Azul primário para Editar */
            color: var(--branco); 
        }

        .btn-delete { 
            background-color: #dc3545; /* Vermelho para Excluir */
            color: var(--branco); 
        }

        .btn-action:hover {
            opacity: 0.9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* --- Paginação --- */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pagination-link {
            text-decoration: none;
            padding: 8px 12px;
            border: 1px solid var(--cinza-borda);
            border-radius: 6px;
            color: var(--azul-primario);
            transition: var(--transicao);
        }

        .pagination-link.active {
            background-color: var(--azul-primario);
            color: var(--branco);
            border-color: var(--azul-primario);
            pointer-events: none; 
        }

        /* --- Responsividade --- */
        @media (max-width: 768px) {
            .search-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-submit {
                width: 100%;
                justify-content: center;
            }
            
            .search-form {
                max-width: none;
            }

            /* Esconde coluna Nome de Usuário */
            thead th:nth-child(3), tbody td:nth-child(3) { 
                display: none;
            }
            
            /* Ajusta botões para mobile */
            .btn-action {
                font-size: 0.75rem;
                padding: 6px 8px;
            }
        }
    </style>
</head>
<body>
    
    <?php 
    // CERTIFIQUE-SE DE QUE ESTE CAMINHO ESTÁ CORRETO!
    include 'menu.php'; 
    ?>

    <main>
        <div class="form-container">
            <h1><i class="fas fa-users-cog"></i> Gerenciamento de Usuários</h1>
            
            <div class="search-controls">
                <a href="cadastrarNovoUsuario.php" class="btn-submit">
                    <i class="fas fa-user-plus"></i> Novo Usuário
                </a>
                
                <form action="gerenciarUsuarios.php" method="GET" class="search-form">
                    <input type="hidden" name="page" value="1">
                    <input type="search" name="search" placeholder="Buscar por Nome, Usuário ou Email" value="<?= htmlspecialchars($termo_busca) ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                    <?php if (!empty($termo_busca)): ?>
                        <a href="gerenciarUsuarios.php" class="btn-clear-search">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <h2>
                <?= !empty($termo_busca) ? "Resultados da Busca (Total: {$total_registros})" : "Todos os Usuários Cadastrados (Total: {$total_registros})" ?>
            </h2>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome Completo</th>
                            <th>Nome de Usuário</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <?php 
                                    $tipo = strtolower($usuario['tipoUsuario']);
                                    $tag_class = '';
                                    switch ($tipo) {
                                        case 'estudante':
                                            $tag_class = 'tag-estudante';
                                            break;
                                        case 'diretor':
                                            $tag_class = 'tag-diretor';
                                            break;
                                        case 'administrador':
                                            $tag_class = 'tag-administrador';
                                            break;
                                        default:
                                            $tag_class = 'tag-default';
                                            break;
                                    }
                                ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['id']) ?></td>
                                <td><?= htmlspecialchars($usuario['nomeCompleto']) ?></td>
                                <td><?= htmlspecialchars($usuario['nomeUsuario']) ?></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td>
                                    <span class="user-tag <?= $tag_class ?>">
                                        <?= htmlspecialchars(ucfirst($usuario['tipoUsuario'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="editUsuario.php?id=<?= htmlspecialchars($usuario['id']) ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="gerenciarUsuarios.php?delete=<?= htmlspecialchars($usuario['id']) ?>&page=<?= $pagina_atual ?>&search=<?= urlencode($termo_busca) ?>" class="btn-action btn-delete" onclick="return confirm('ATENÇÃO: Tem certeza que deseja excluir o usuário <?= htmlspecialchars($usuario['nomeCompleto']) ?>? Isso pode apagar dados relacionados!');">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <?= !empty($termo_busca) ? "Nenhum usuário encontrado com o termo '{$termo_busca}'." : "Nenhum usuário encontrado no sistema." ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="pagination-container">
                    <?php if ($pagina_atual > 1): ?>
                        <a href="<?= get_pagination_link($pagina_atual - 1, $termo_busca) ?>" class="pagination-link">
                            <i class="fas fa-angle-left"></i> Anterior
                        </a>
                    <?php endif; ?>

                    <span class="pagination-info">Página <?= $pagina_atual ?> de <?= $total_paginas ?></span>

                    <?php if ($pagina_atual < $total_paginas): ?>
                        <a href="<?= get_pagination_link($pagina_atual + 1, $termo_busca) ?>" class="pagination-link">
                            Próxima <i class="fas fa-angle-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php elseif ($total_registros > 0): ?>
                <div class="pagination-info" style="text-align: center; margin-top: 20px;">
                    Total de <?= $total_registros ?> usuários.
                </div>
            <?php endif; ?>
            
        </div>
    </main>

    <footer>
    </footer>
</body>
</html>