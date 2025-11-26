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

require_once '../conexao/conecta.php';

$tabela = 'tb_usuario';
$usuario = null;
$mensagem = '';
$sucesso = false;

// 1. Processar a Edição (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Usando filter_input para sanitização e validação
    $id_usuario = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nomeCompleto = filter_input(INPUT_POST, 'nomeCompleto', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // NOVO CAMPO CPF
    $nomeUsuario = filter_input(INPUT_POST, 'nomeUsuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $tipoUsuario = filter_input(INPUT_POST, 'tipoUsuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $codigoEscola = filter_input(INPUT_POST, 'codigoEscola', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $senha_nova = filter_input(INPUT_POST, 'senha_nova'); 
    
    // Agora o CPF é parte dos dados essenciais para o SQL
    if (!$id_usuario || !$nomeCompleto || !$email || !$cpf || !$nomeUsuario || !$tipoUsuario) {
        $mensagem = "Erro: Dados do formulário incompletos ou inválidos.";
    } else {
        // ATUALIZAÇÃO DO SQL: Adicione 'cpf = ?' e 's' nos parâmetros e $cpf no bind_data
        $sql_update = "UPDATE $tabela SET nomeCompleto = ?, email = ?, cpf = ?, nomeUsuario = ?, tipoUsuario = ?, codigoEscola = ?";
        $params = "ssssss"; // 's' para nome, 's' para email, 's' para CPF, 's' para nomeUsuario, 's' para tipo, 's' para codigo
        $bind_data = [$nomeCompleto, $email, $cpf, $nomeUsuario, $tipoUsuario, $codigoEscola];
        
        // Se uma nova senha for fornecida, adicione-a à atualização
        if (!empty($senha_nova)) {
            $senha_hashed = password_hash($senha_nova, PASSWORD_DEFAULT);
            $sql_update .= ", senha = ?";
            $params .= "s";
            $bind_data[] = $senha_hashed;
        }

        // Adiciona a cláusula WHERE
        $sql_update .= " WHERE id = ?";
        $params .= "i";
        $bind_data[] = $id_usuario;

        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update === false) {
            $mensagem = "Erro na preparação da consulta: " . $conn->error;
        } else {
            // Lógica dinâmica de bind_param para lidar com a senha opcional
            $bind_names[] = $params;
            for ($i = 0; $i < count($bind_data); $i++) {
                $bind_name = 'bind' . $i;
                $$bind_name = $bind_data[$i];
                $bind_names[] = &$$bind_name;
            }
            
            call_user_func_array(array($stmt_update, 'bind_param'), $bind_names);

            if ($stmt_update->execute()) {
                $mensagem = "Usuário **" . htmlspecialchars($nomeCompleto) . "** atualizado com sucesso!";
                $sucesso = true;
            } else {
                $mensagem = "Erro ao atualizar usuário: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
}


// 2. Buscar Dados do Usuário (GET ou após o POST)
$id_buscar = $id_usuario ?? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_buscar) {
    header("Location: gerenciarUsuarios.php");
    exit();
}

$sql_select = "SELECT * FROM $tabela WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);

if ($stmt_select === false) {
    die("Erro na preparação da consulta de busca: " . $conn->error);
}

$stmt_select->bind_param("i", $id_buscar);
$stmt_select->execute();
$result_select = $stmt_select->get_result();

if ($result_select->num_rows === 1) {
    // Os dados do usuário, incluindo o CPF, são carregados aqui
    $usuario = $result_select->fetch_assoc();
} else {
    die("Erro: Usuário com ID $id_buscar não encontrado.");
}
$stmt_select->close();
$conn->close();

$tipos_usuario = ['estudante', 'administrador', 'Diretor']; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
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
            max-width: 600px; 
            background-color: var(--branco);
            padding: 30px;
            margin: 20px 0;
            border-radius: 12px;
            box-shadow: var(--sombra);
        }

        h1 {
            color: var(--azul-primario);
            font-size: 1.8rem;
            border-bottom: 2px solid var(--gold-color);
            padding-bottom: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* --- Estilos do Formulário --- */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--azul-escuro);
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--cinza-borda);
            border-radius: 6px;
            box-sizing: border-box; 
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--azul-primario);
            outline: none;
        }

        /* --- Estilos dos Botões --- */
        .btn-submit {
            background-color: #28a745; 
            color: var(--branco);
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transicao);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background-color: #218838;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-action {
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: var(--transicao);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-action:hover {
            opacity: 0.9;
        }

        /* --- Mensagens de Alerta --- */
        .alert {
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 5px; 
            font-weight: 500;
            border: 1px solid;
        }

        .alert-success {
            background-color: #d4edda; 
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da; 
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        /* --- Responsividade --- */
        @media (max-width: 600px) {
            .form-container {
                padding: 15px;
                margin: 10px;
            }
            h1 {
                font-size: 1.4rem;
            }
            .form-group input, .form-group select {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    
    <?php include 'menu.php'; ?>

    <main>
        <div class="form-container">
            <h1><i class="fas fa-user-edit"></i> Editar Usuário: <?= htmlspecialchars($usuario['nomeCompleto']) ?></h1>
            
            <?php if (!empty($mensagem)): ?>
                <div class="alert <?= $sucesso ? 'alert-success' : 'alert-danger' ?>">
                    <?= $mensagem ?>
                </div>
            <?php endif; ?>

            <form action="editUsuario.php" method="post">
                <input type="hidden" name="id" value="<?= htmlspecialchars($usuario['id']) ?>">
                
                <div class="form-group">
                    <label for="nomeCompleto">Nome Completo</label>
                    <input type="text" id="nomeCompleto" name="nomeCompleto" value="<?= htmlspecialchars($usuario['nomeCompleto']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($usuario['cpf'] ?? '') ?>" maxlength="14" placeholder="Ex: 000.000.000-00" required>
                    <small style="color: #6c757d;">Use o formato XXXXXXXXXXX (11 dígitos) ou XXXXXXXX-XX.</small>
                </div>
                <div class="form-group">
                    <label for="nomeUsuario">Nome de Usuário</label>
                    <input type="text" id="nomeUsuario" name="nomeUsuario" value="<?= htmlspecialchars($usuario['nomeUsuario']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="tipoUsuario">Tipo de Usuário</label>
                    <select id="tipoUsuario" name="tipoUsuario" required>
                        <?php foreach ($tipos_usuario as $tipo): ?>
                            <option value="<?= $tipo ?>" <?= (strtolower($usuario['tipoUsuario']) == $tipo) ? 'selected' : '' ?>>
                                <?= ucfirst($tipo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="codigoEscola">Código da Escola (Chave de Acesso)</label>
                    <input type="text" id="codigoEscola" name="codigoEscola" value="<?= htmlspecialchars($usuario['codigoEscola'] ?? '') ?>">
                    <small style="color: #6c757d;">Campo opcional, usado para identificar a instituição.</small>
                </div>
                
                <hr style="margin: 30px 0; border-color: #ddd;">

                <div class="form-group">
                    <label for="senha_nova">Nova Senha</label>
                    <input type="password" id="senha_nova" name="senha_nova">
                    <small style="color: #dc3545; font-weight: 500;">Preencha este campo **apenas** se quiser alterar a senha.</small>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a href="gerenciarUsuarios.php" class="btn-action" style="background-color: #6c757d; color: var(--branco); margin-left: 10px; padding: 12px 20px;">
                        <i class="fas fa-list"></i> Voltar para Gerenciamento
                    </a>
                </div>
            </form>
        </div>
    </main>

    </body>
</html>