<?php
session_start();
// 1. Verificação de Acesso: Apenas Diretor ou Administrador
if (!isset($_SESSION['tipoUsuario']) || ($_SESSION['tipoUsuario'] !== 'diretor' && $_SESSION['tipoUsuario'] !== 'administrador')) {
    header("Location: ../login.php");
    exit();
}

// Incluir o arquivo de conexão
include '../conexao/conecta.php';

$diretor_id = $_SESSION['id'];
$codigo_escola = '';
$plano_diretor = 'Básico'; // Valor padrão seguro
$escola_nome = 'Desconhecida';
$limite_alunos = 0; 

// 2. BUSCA DE INFORMAÇÕES DO DIRETOR E DA ESCOLA/PLANO
$stmt_diretor = $conn->prepare("SELECT codigoEscola, plano FROM tb_usuario WHERE id = ?");
$stmt_diretor->bind_param("i", $diretor_id);
$stmt_diretor->execute();
$result_diretor = $stmt_diretor->get_result();

if ($result_diretor->num_rows > 0) {
    $row_diretor = $result_diretor->fetch_assoc();
    $codigo_escola = $row_diretor['codigoEscola'];
    // Usa o plano do diretor, se existir. Caso contrário, mantém o 'Básico'
    if (!empty($row_diretor['plano'])) {
        $plano_diretor = $row_diretor['plano'];
    }
    
    // Buscar o nome da escola
    $stmt_escola = $conn->prepare("SELECT nome FROM tb_escola WHERE codigoEscola = ?");
    $stmt_escola->bind_param("s", $codigo_escola);
    $stmt_escola->execute();
    $result_escola = $stmt_escola->get_result();
    
    if ($result_escola->num_rows > 0) {
        $row_escola = $result_escola->fetch_assoc();
        $escola_nome = $row_escola['nome'];
    }
    $stmt_escola->close();
    
    // Buscar detalhes do plano (limite de alunos)
    $stmt_plano = $conn->prepare("SELECT numAlunos FROM tb_planos WHERE nomePlanos = ?");
    $stmt_plano->bind_param("s", $plano_diretor);
    $stmt_plano->execute();
    $result_plano = $stmt_plano->get_result();
    
    if ($result_plano->num_rows > 0) {
        $row_plano = $result_plano->fetch_assoc();
        $limite_alunos = $row_plano['numAlunos'];
    } else {
        // Se não encontrar o plano, usa um limite padrão
        $limite_alunos = 800; // Valor padrão
    }
    $stmt_plano->close();
}
$stmt_diretor->close();


// =========================================================================
// 3. PROCESSAMENTO DE AÇÕES (Vincular/Desvincular)
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- AÇÃO DE VÍNCULO/DESVÍNCULO INDIVIDUAL ---
    if (isset($_POST['vincular_aluno']) || isset($_POST['desvincular_aluno'])) {
        $aluno_id = filter_input(INPUT_POST, 'aluno_id', FILTER_VALIDATE_INT);
        
        if ($aluno_id === false || $aluno_id === null) {
            header("Location: gerenciarAlunos.php?erro=ID_Invalido");
            exit();
        }

        if (isset($_POST['vincular_aluno'])) {
            // Ação VINCULAR INDIVIDUAL - Verificar se há vagas disponíveis
            $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM tb_usuario WHERE codigoEscola = ? AND statusPlano = 'habilitado' AND tipoUsuario = 'estudante'");
            $stmt_count->bind_param("s", $codigo_escola);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result();
            $row_count = $result_count->fetch_assoc();
            $alunos_vinculados_atual = $row_count['total'];
            $stmt_count->close();
            
            // Verifica se atingiu o limite do plano (para 1 aluno)
            if ($alunos_vinculados_atual >= $limite_alunos) {
                header("Location: gerenciarAlunos.php?erro=Limite_Atingido&limite=" . $limite_alunos);
                exit();
            }
            
            $status_novo = 'habilitado';
            $plano_novo = $plano_diretor; // Usa o plano da escola
        } else {
            // Ação DESVINCULAR INDIVIDUAL
            $status_novo = 'pendente';
            $plano_novo = 'Básico'; // Volta para o plano básico ao desvincular
        }
        
        // Instrução UPDATE Unificada
        $stmt_update = $conn->prepare("UPDATE tb_usuario SET statusPlano = ?, plano = ? WHERE id = ? AND codigoEscola = ? AND tipoUsuario = 'estudante'");
        $stmt_update->bind_param("ssis", $status_novo, $plano_novo, $aluno_id, $codigo_escola); 
        $stmt_update->execute();
        $stmt_update->close();
        
        // Recarregar a página para ver as alterações
        header("Location: gerenciarAlunos.php");
        exit();
    } 

    // --- AÇÃO DE VÍNCULO MÚLTIPLO ---
    if (isset($_POST['vincular_multiplos']) && !empty($_POST['aluno_ids'])) {
        $aluno_ids = array_map('intval', $_POST['aluno_ids']); // Garante que todos são inteiros
        $num_a_vincular = count($aluno_ids);

        // 1. Contar alunos vinculados ATUALMENTE
        $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM tb_usuario WHERE codigoEscola = ? AND statusPlano = 'habilitado' AND tipoUsuario = 'estudante'");
        $stmt_count->bind_param("s", $codigo_escola);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $row_count = $result_count->fetch_assoc();
        $alunos_vinculados_atual = $row_count['total'];
        $stmt_count->close();

        // 2. Verificar se a ação excederá o limite
        if (($alunos_vinculados_atual + $num_a_vincular) > $limite_alunos) {
            $max_possivel = $limite_alunos - $alunos_vinculados_atual;
            header("Location: gerenciarAlunos.php?erro=Limite_Excedido_Multiplo&limite=" . $limite_alunos . "&tentativa=" . $num_a_vincular . "&possivel=" . $max_possivel);
            exit();
        }

        // 3. Processar o vínculo (UPDATE para todos os IDs)
        $ids_string = implode(',', array_fill(0, $num_a_vincular, '?')); // Cria string de '?' para a query
        $types = str_repeat('i', $num_a_vincular);
        $status_novo = 'habilitado';
        $plano_novo = $plano_diretor;

        $sql_multi_update = "UPDATE tb_usuario SET statusPlano = ?, plano = ? WHERE id IN (" . $ids_string . ") AND codigoEscola = ? AND tipoUsuario = 'estudante'";
        
        // Parâmetros para bind_param: status, plano, ID1, ID2, ..., codigoEscola
        $params = array_merge([$status_novo, $plano_novo], $aluno_ids, [$codigo_escola]);
        $types_multi = "ss" . $types . "s"; 
        
        // Cria um array de referências para o bind_param (necessário para o mysqli)
        $bind_params = [];
        foreach ($params as $key => &$value) {
            $bind_params[] = &$value;
        }

        $stmt_multi_update = $conn->prepare($sql_multi_update);
        if ($stmt_multi_update) {
            // Chama bind_param dinamicamente
            call_user_func_array([$stmt_multi_update, 'bind_param'], array_merge([$types_multi], $bind_params));
            $stmt_multi_update->execute();
            $stmt_multi_update->close();
        }
        
        // Recarregar a página com mensagem de sucesso
        header("Location: gerenciarAlunos.php?sucesso=vinculado_multi&count=" . $num_a_vincular);
        exit();
    }
}

// =========================================================================
// 4. CONSULTAS PARA LISTAGEM
// =========================================================================

// Função auxiliar para executar consultas e retornar o array
function fetch_users($conn, $sql, $params, $types) {
    $data = [];
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    }
    return $data;
}

// 1. Buscar TODOS os usuários da escola
$sql_todos = "SELECT id, nomeCompleto, email, nomeUsuario, telefone, datNasc, metaProvas, plano, cpf, tipoUsuario, statusPlano 
              FROM tb_usuario 
              WHERE codigoEscola = ? 
              ORDER BY tipoUsuario, nomeCompleto";
$todos_cadastros = fetch_users($conn, $sql_todos, [$codigo_escola], "s");

// 2. Buscar APENAS alunos vinculados
$sql_vinculados = "SELECT id, nomeCompleto, email, nomeUsuario, telefone, datNasc, metaProvas, plano, cpf 
                   FROM tb_usuario 
                   WHERE codigoEscola = ? AND statusPlano = 'habilitado' AND tipoUsuario = 'estudante'
                   ORDER BY nomeCompleto";
$alunos_vinculados = fetch_users($conn, $sql_vinculados, [$codigo_escola], "s");

// 3. Buscar APENAS alunos pendentes
$sql_pendentes = "SELECT id, nomeCompleto, email, nomeUsuario, telefone, datNasc, metaProvas, plano, cpf 
                  FROM tb_usuario 
                  WHERE codigoEscola = ? AND statusPlano = 'pendente' AND tipoUsuario = 'estudante'
                  ORDER BY nomeCompleto";
$alunos_pendentes = fetch_users($conn, $sql_pendentes, [$codigo_escola], "s");

$conn->close();

// =========================================================================
// 5. FUNÇÕES DE APRESENTAÇÃO
// =========================================================================

// Função para formatar e ocultar o CPF (Melhoria de Privacidade)
function formatar_cpf_oculto($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) === 11) {
        // Exibe apenas os 3 primeiros e os 2 últimos dígitos
        return substr($cpf, 0, 3) . '.***.***-' . substr($cpf, 9, 2);
    }
    return 'Não Informado'; 
}

// Função para obter o Badge do Tipo de Usuário (Melhorado com cores mais distintas)
function get_tipo_badge($tipo, $statusPlano = null) {
    $class = 'bg-light text-dark';
    $icon = 'fas fa-user me-1';
    $label = ucfirst($tipo);

    switch ($tipo) {
        case 'diretor':
            $class = 'bg-danger text-white';
            $icon = 'fas fa-crown me-1';
            $label = 'Diretor(a)';
            break;
        case 'administrador':
            $class = 'bg-info text-dark';
            $icon = 'fas fa-user-shield me-1';
            $label = 'Admin';
            break;
        case 'professor':
            $class = 'bg-secondary text-white';
            $icon = 'fas fa-chalkboard-user me-1';
            $label = 'Professor(a)';
            break;
        case 'estudante':
            $icon = 'fas fa-user-graduate me-1';
            if ($statusPlano === 'habilitado') {
                $class = 'bg-success text-white';
                $label = 'Estudante (Habilitado)';
            } elseif ($statusPlano === 'pendente') {
                $class = 'bg-warning text-dark';
                $label = 'Estudante (Pendente)';
            } else {
                $class = 'bg-primary text-white';
                $label = 'Estudante';
            }
            break;
    }
    return '<span class="badge ' . $class . ' status-badge"><i class="' . $icon . '"></i> ' . $label . '</span>';
}

// =========================================================================
// 6. TOTAIS
// =========================================================================
$total_vinculados = count($alunos_vinculados);
$total_pendentes = count($alunos_pendentes);
$total_geral_escola = count($todos_cadastros); 

// Verificar se atingiu o limite do plano
$limite_atingido = ($total_vinculados >= $limite_alunos);
$vagas_disponiveis = $limite_alunos - $total_vinculados;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Escola - Diretor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        main {
            padding-top: 100px;
            padding-bottom: 30px;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        .student-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        .btn-vincular, .btn-desvincular {
            font-size: 0.9rem;
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 50px;
        }
        .btn-vincular {
            background-color: #1cc88a; 
            color: white;
        }
        .btn-vincular:hover {
            background-color: #17a673;
        }
        .btn-vincular:disabled {
            background-color: #b7b7b7;
            cursor: not-allowed;
        }
        .btn-desvincular {
            background-color: #e74a3b; 
            color: white;
        }
        .btn-desvincular:hover {
            background-color: #cc372c;
        }
        .cpf-display {
            font-size: 0.85rem;
            font-weight: 600;
            color: #5a5c69; 
            margin-bottom: 10px; 
            padding-top: 5px;
            border-top: 1px dashed #e9ecef; 
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
        }
        .student-avatar {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f8f9fc;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 600;
        }
        .nav-tabs {
            border-bottom: 2px solid #e3e6f0;
        }
        .nav-tabs .nav-link {
            color: #858796;
            font-weight: 600;
            padding: 10px 20px;
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }
        .nav-tabs .nav-link.active {
            color: #4e73df;
            border-bottom: 3px solid #4e73df;
            background: transparent;
        }
        .card-title-lg {
            font-size: 1.1rem;
            font-weight: 700;
        }
        .text-section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4e73df;
            border-bottom: 3px solid #e3e6f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .alert-limit {
            border-left: 4px solid #e74a3b;
        }
        .plan-info {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        /* Estilo para o checkbox no card */
        .card .form-check {
             position: absolute;
             top: 10px;
             right: 15px;
             z-index: 10;
        }
        /* Centraliza o checkbox em relação ao avatar */
        .student-card .form-check {
            position: relative;
            margin-right: 15px;
            margin-top: 0;
            align-self: center; /* Alinha o checkbox ao centro da linha d-flex */
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/menu.php'; // Incluir menu fora do main, se for fixo ?>
    
    <main>
        <div class="container">
            <h2 class="text-section-title text-center mb-5">Gerenciamento da Escola: <?php echo htmlspecialchars($escola_nome); ?></h2>
            
            <div class="plan-info">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-1"><i class="fas fa-crown me-2"></i>Plano: <?php echo htmlspecialchars($plano_diretor); ?></h4>
                        <p class="mb-0">Limite: <?php echo $limite_alunos; ?> alunos vinculados</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h3 class="mb-1"><?php echo $total_vinculados; ?> / <?php echo $limite_alunos; ?></h3>
                        <p class="mb-0"><?php echo $vagas_disponiveis; ?> vagas disponíveis</p>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['erro']) && $_GET['erro'] === 'Limite_Atingido'): ?>
                <div class="alert alert-danger alert-limit d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Limite do Plano Atingido!</h5>
                        <p class="mb-0">Você atingiu o limite máximo de <?php echo $_GET['limite']; ?> alunos vinculados permitidos pelo plano <?php echo htmlspecialchars($plano_diretor); ?>. 
                        Entre em contato para atualizar seu plano.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro']) && $_GET['erro'] === 'Limite_Excedido_Multiplo'): ?>
                <div class="alert alert-danger alert-limit d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Limite do Plano Excedido no Vínculo Múltiplo!</h5>
                        <p class="mb-0">Você tentou vincular **<?php echo $_GET['tentativa']; ?>** alunos, mas só há **<?php echo $_GET['possivel']; ?>** vagas disponíveis no seu plano (Limite: <?php echo $_GET['limite']; ?>). Selecione menos alunos ou desvincule alguns para liberar espaço.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'vinculado_multi'): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Vínculo Múltiplo Realizado!</h5>
                        <p class="mb-0">**<?php echo $_GET['count']; ?>** alunos foram vinculados com sucesso ao plano **<?php echo htmlspecialchars($plano_diretor); ?>**.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row mb-5 justify-content-center">
                <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center py-4">
                            <h5><i class="fas fa-users me-1"></i> Total de Usuários</h5>
                            <p class="stats-number"><?php echo $total_geral_escola; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center py-4">
                            <h5><i class="fas fa-link me-1"></i> Alunos Vinculados</h5>
                            <p class="stats-number"><?php echo $total_vinculados; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center py-4">
                            <h5><i class="fas fa-hourglass-half me-1"></i> Alunos Pendentes</h5>
                            <p class="stats-number"><?php echo $total_pendentes; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-users-tab" data-bs-toggle="tab" data-bs-target="#all-users" type="button" role="tab" aria-selected="true">Todos os Cadastros (<?php echo $total_geral_escola; ?>)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="linked-tab" data-bs-toggle="tab" data-bs-target="#linked" type="button" role="tab" aria-selected="false">Alunos Vinculados (<?php echo $total_vinculados; ?>)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-selected="false">Alunos Pendentes (<?php echo $total_pendentes; ?>)</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                <div class="tab-pane fade show active" id="all-users" role="tabpanel" aria-labelledby="all-users-tab">
                    <?php if ($limite_atingido): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Limite do plano atingido!</strong> Você não pode vincular mais alunos. 
                            <?php echo $vagas_disponiveis; ?> vagas disponíveis de <?php echo $limite_alunos; ?>.
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <?php 
                        foreach ($todos_cadastros as $usuario): 
                            // Ignorar o diretor logado para evitar ação de desvincular em si mesmo
                            if ($usuario['id'] == $diretor_id && $usuario['tipoUsuario'] == 'diretor') continue;

                            $cpf_oculto = formatar_cpf_oculto(htmlspecialchars($usuario['cpf'] ?? ''));
                            $tipo_badge = get_tipo_badge(htmlspecialchars($usuario['tipoUsuario']), htmlspecialchars($usuario['statusPlano'] ?? null));
                            
                            // Determinar a cor de fundo do avatar com base no tipo
                            $bg_color = match(htmlspecialchars($usuario['tipoUsuario'])) {
                                'diretor' => 'dc3545',
                                'administrador' => '17a2b8',
                                'professor' => '6c757d',
                                default => ($usuario['statusPlano'] === 'habilitado' ? '1cc88a' : 'ffc107'),
                            };
                        ?>
                        <div class="col-sm-6 col-lg-4 mb-4">
                            <div class="card student-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($usuario['nomeCompleto']); ?>&background=<?php echo $bg_color; ?>&color=fff" class="student-avatar me-3">
                                        <div>
                                            <h5 class="card-title-lg mb-0 text-truncate"><?php echo htmlspecialchars($usuario['nomeCompleto']); ?></h5>
                                            <span class="text-muted small d-block"><?php echo htmlspecialchars($usuario['email']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="cpf-display">
                                        <i class="fas fa-id-card me-2 text-secondary"></i> 
                                        CPF: **<?php echo $cpf_oculto; ?>**
                                    </div>
                                    
                                    <p class="card-text mb-2">
                                        <small class="text-muted d-block"><i class="fas fa-user me-1"></i> Usuário: <?php echo htmlspecialchars($usuario['nomeUsuario']); ?></small>
                                        <small class="text-muted d-block"><i class="fas fa-phone me-1"></i> Telefone: <?php echo htmlspecialchars($usuario['telefone']); ?></small>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <?php echo $tipo_badge; ?>
                                        
                                        <?php 
                                        // Ação de vincular/desvincular SÓ aparece para Estudantes
                                        if ($usuario['tipoUsuario'] === 'estudante'):
                                            $is_vinculado = $usuario['statusPlano'] === 'habilitado';
                                        ?>
                                            <?php if (!$is_vinculado): ?>
                                            <form method="POST" action="gerenciarAlunos.php" onsubmit="return confirm('Tem certeza que deseja VINCULAR este aluno ao plano da escola (<?php echo htmlspecialchars($plano_diretor); ?>)?')">
                                                <input type="hidden" name="aluno_id" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit" name="vincular_aluno" class="btn btn-sm btn-vincular" <?php echo $limite_atingido ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-link me-1"></i><?php echo $limite_atingido ? 'Limite Atingido' : 'Vincular'; ?>
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <form method="POST" action="gerenciarAlunos.php" onsubmit="return confirm('Tem certeza que deseja DESVINCULAR este aluno? O plano dele será redefinido para Básico.')">
                                                <input type="hidden" name="aluno_id" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit" name="desvincular_aluno" class="btn btn-sm btn-desvincular">
                                                    <i class="fas fa-unlink me-1"></i>Desvincular
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-secondary small">Ação Indisponível</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if ($total_geral_escola <= 1 && $diretor_id && !empty($codigo_escola)): // Verifica se só há o diretor ou está vazio ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Nenhum outro usuário encontrado</h4>
                                <p class="text-muted">Apenas o seu cadastro de diretor está vinculado a esta escola.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="linked" role="tabpanel" aria-labelledby="linked-tab">
                    <div class="row">
                        <?php foreach ($alunos_vinculados as $aluno): ?>
                        <div class="col-sm-6 col-lg-4 mb-4">
                            <div class="card student-card border-success border-2">
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($aluno['nomeCompleto']); ?>&background=1cc88a&color=fff" class="student-avatar me-3">
                                        <div>
                                            <h5 class="card-title-lg mb-0 text-truncate"><?php echo htmlspecialchars($aluno['nomeCompleto']); ?></h5>
                                            <span class="text-muted small d-block"><?php echo htmlspecialchars($aluno['email']); ?></span>
                                        </div>
                                    </div>

                                    <div class="cpf-display">
                                        <i class="fas fa-id-card me-2 text-success"></i> 
                                        CPF: **<?php echo formatar_cpf_oculto(htmlspecialchars($aluno['cpf'] ?? '')); ?>**
                                    </div>

                                    <p class="card-text mb-2">
                                        <small class="text-muted d-block"><i class="fas fa-user me-1"></i> Usuário: <?php echo htmlspecialchars($aluno['nomeUsuario']); ?></small>
                                        <small class="text-muted d-block"><i class="fas fa-phone me-1"></i> Telefone: <?php echo htmlspecialchars($aluno['telefone']); ?></small>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <?php echo get_tipo_badge('estudante', 'habilitado'); ?>
                                        
                                        <form method="POST" action="gerenciarAlunos.php" onsubmit="return confirm('Tem certeza que deseja DESVINCULAR este aluno? O plano dele será redefinido para Básico.')">
                                            <input type="hidden" name="aluno_id" value="<?php echo $aluno['id']; ?>">
                                            <button type="submit" name="desvincular_aluno" class="btn btn-sm btn-desvincular">
                                                <i class="fas fa-unlink me-1"></i>Desvincular
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if ($total_vinculados === 0): ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-link fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Nenhum aluno vinculado</h4>
                                <p class="text-muted">Use a aba "Alunos Pendentes" para vincular um aluno.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                    <?php if ($limite_atingido): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Limite do plano atingido!</strong> Você não pode vincular mais alunos. 
                            <?php echo $vagas_disponiveis; ?> vagas disponíveis de <?php echo $limite_alunos; ?>.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="gerenciarAlunos.php" id="form-vincular-multiplos">
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selecionarTodos" onchange="toggleSelectAll(this)" 
                                        <?php echo $total_pendentes === 0 ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="selecionarTodos">
                                    **Selecionar Todos os Pendentes**
                                </label>
                            </div>
                            <button type="submit" name="vincular_multiplos" class="btn btn-lg btn-vincular" id="btn-vincular-multiplos" 
                                    <?php echo $limite_atingido || $total_pendentes === 0 ? 'disabled' : ''; ?> 
                                    title="Vincular todos os alunos selecionados ao plano da escola."
                                    onclick="return checkMultiVincular(<?php echo $vagas_disponiveis; ?>)">
                                <i class="fas fa-link me-1"></i> Vincular Selecionados (<span id="count-selecionados">0</span>)
                            </button>
                        </div>

                        <div class="row">
                            <?php foreach ($alunos_pendentes as $aluno): ?>
                            <div class="col-sm-6 col-lg-4 mb-4">
                                <div class="card student-card border-warning border-2">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start mb-3">
                                            
                                            <div class="form-check me-3 mt-2">
                                                <input class="form-check-input aluno-checkbox" type="checkbox" name="aluno_ids[]" 
                                                    value="<?php echo $aluno['id']; ?>" id="aluno_<?php echo $aluno['id']; ?>"
                                                    onchange="updateMultiButton()"
                                                    <?php echo $limite_atingido ? 'disabled' : ''; ?>>
                                                <label class="form-check-label" for="aluno_<?php echo $aluno['id']; ?>">
                                                    </label>
                                            </div>

                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($aluno['nomeCompleto']); ?>&background=ffc107&color=343a40" class="student-avatar me-3">
                                            <div>
                                                <h5 class="card-title-lg mb-0 text-truncate"><?php echo htmlspecialchars($aluno['nomeCompleto']); ?></h5>
                                                <span class="text-muted small d-block"><?php echo htmlspecialchars($aluno['email']); ?></span>
                                            </div>
                                        </div>

                                        <div class="cpf-display">
                                            <i class="fas fa-id-card me-2 text-warning"></i> 
                                            CPF: **<?php echo formatar_cpf_oculto(htmlspecialchars($aluno['cpf'] ?? '')); ?>**
                                        </div>

                                        <p class="card-text mb-2">
                                            <small class="text-muted d-block"><i class="fas fa-user me-1"></i> Usuário: <?php echo htmlspecialchars($aluno['nomeUsuario']); ?></small>
                                            <small class="text-muted d-block"><i class="fas fa-phone me-1"></i> Telefone: <?php echo htmlspecialchars($aluno['telefone']); ?></small>
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <?php echo get_tipo_badge('estudante', 'pendente'); ?>
                                            <button type="button" class="btn btn-sm btn-vincular" 
                                                    <?php echo $limite_atingido ? 'disabled' : ''; ?> 
                                                    onclick="vincularIndividual(<?php echo $aluno['id']; ?>)">
                                                <i class="fas fa-link me-1"></i> Individual
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if ($total_pendentes === 0): ?>
                                <div class="col-12 text-center py-5">
                                    <i class="fas fa-hourglass-half fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Nenhum aluno pendente</h4>
                                    <p class="text-muted">Todos os alunos cadastrados estão vinculados à sua escola.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const limiteAtingido = <?php echo $limite_atingido ? 'true' : 'false'; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Inicializa o estado do botão de múltiplos vínculos
            updateMultiButton();
        });

        /**
         * Alterna a seleção de todos os checkboxes de alunos pendentes.
         */
        function toggleSelectAll(source) {
            let checkboxes = document.querySelectorAll('#pending .aluno-checkbox');
            for (let i = 0; i < checkboxes.length; i++) {
                if (!checkboxes[i].disabled) {
                    checkboxes[i].checked = source.checked;
                }
            }
            updateMultiButton();
        }

        /**
         * Atualiza o estado e a contagem do botão de vínculo múltiplo.
         */
        function updateMultiButton() {
            let selectedCount = document.querySelectorAll('#pending .aluno-checkbox:checked').length;
            let multiButton = document.getElementById('btn-vincular-multiplos');
            let countSpan = document.getElementById('count-selecionados');
            let selectAllCheckbox = document.getElementById('selecionarTodos');

            countSpan.textContent = selectedCount;

            // Desabilita o botão se a contagem for 0 OU se o limite já tiver sido atingido
            if (selectedCount === 0 || limiteAtingido) {
                multiButton.disabled = true;
            } else {
                multiButton.disabled = false;
            }
            
            // Atualiza o estado do "Selecionar Todos"
            let totalAvailable = document.querySelectorAll('#pending .aluno-checkbox:not(:disabled)').length;
            if (totalAvailable > 0) {
                selectAllCheckbox.checked = selectedCount === totalAvailable;
            }
        }

        /**
         * Confirmação e validação do limite (lado do cliente) para o vínculo múltiplo.
         */
        function checkMultiVincular(vagas_disponiveis) {
            let selectedCount = document.querySelectorAll('#pending .aluno-checkbox:checked').length;

            if (selectedCount === 0) {
                alert("Selecione pelo menos um aluno para vincular.");
                return false;
            }
            
            if (selectedCount > vagas_disponiveis) {
                alert(`Você tem ${selectedCount} alunos selecionados, mas apenas ${vagas_disponiveis} vagas disponíveis! Desmarque alguns alunos.`);
                return false;
            }

            return confirm(`Você tem certeza que deseja VINCULAR os ${selectedCount} alunos selecionados ao plano da escola (<?php echo htmlspecialchars($plano_diretor); ?>)?`);
        }

        /**
         * Cria um formulário temporário para o vínculo individual (mantido para compatibilidade).
         */
        function vincularIndividual(alunoId) {
            if (limiteAtingido) {
                alert("O limite do plano foi atingido. Não é possível vincular novos alunos.");
                return;
            }
            if (!confirm('Tem certeza que deseja VINCULAR este aluno ao plano da escola (<?php echo htmlspecialchars($plano_diretor); ?>)?')) {
                return;
            }
            // Cria um formulário temporário para a ação individual
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'gerenciarAlunos.php';
            
            let idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'aluno_id';
            idInput.value = alunoId;
            form.appendChild(idInput);
            
            let actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'vincular_aluno';
            actionInput.value = '1';
            form.appendChild(actionInput);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
    </body>
</html>