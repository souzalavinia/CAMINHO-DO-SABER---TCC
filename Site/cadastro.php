<?php

include 'conexao/conecta.php';

// O $conn já foi criado em conecta.php, não precisa verificar novamente
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// --- 1. COLETA E SANITIZAÇÃO DE DADOS ---
$nomeCompleto = trim($_POST["nomeCompleto"]);
$nomeUsuario = trim($_POST["nomeUsuario"]);
$email = $_POST["email"];
$senha = $_POST["senha"];
$telefone = $_POST["telefone"];
$datNasc = $_POST["datNasc"];
$tipoUsuario = 'estudante';

// NOVO CAMPO CPF: Recebe, limpa e valida
$cpf_raw = isset($_POST['cpf']) ? $_POST['cpf'] : '';
// Remove qualquer coisa que não seja dígito (pontos, traços, espaços)
$cpf = preg_replace('/[^0-9]/', '', $cpf_raw); 

// Novo campo (pode vir vazio)
$codigoEscola = isset($_POST['codigoEscola']) ? trim($_POST['codigoEscola']) : '';

// Define plano padrão
$plano = 'Basico';

// --- 2. VALIDAÇÃO DO CPF (BÁSICA) ---
// Se o campo CPF estiver vazio ou não tiver 11 dígitos, retorna erro.
if (empty($cpf) || strlen($cpf) !== 11) {
    header("Location: cadastro.html?erro=cpf_invalido");
    exit();
}


// --- 3. VERIFICAÇÃO DE DUPLICIDADE (Usuário e CPF) ---

// Verifica se o nome de usuário OU o CPF já existem no banco
$sql_check = "SELECT nomeUsuario, cpf FROM tb_usuario WHERE nomeUsuario = ? OR cpf = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param('ss', $nomeUsuario, $cpf);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Para identificar qual campo está duplicado, buscamos os resultados
    $stmt->bind_result($existing_user, $existing_cpf);
    
    // Assumimos que o primeiro resultado encontrado é suficiente para o erro,
    // mas vamos garantir que a mensagem de erro seja a correta.
    while ($stmt->fetch()) {
        if ($existing_user === $nomeUsuario) {
            $stmt->close();
            header("Location: cadastro.html?erro=usuario_existente");
            exit();
        }
        // Como o CPF é limpo, a comparação deve ser feita com o valor limpo
        if ($existing_cpf === $cpf) {
            $stmt->close();
            header("Location: cadastro.html?erro=cpf_existente");
            exit();
        }
    }
}
$stmt->close();


// --- 4. VERIFICAÇÃO DO CÓDIGO DA ESCOLA ---
if (!empty($codigoEscola)) {
    // Consulta para verificar se o código da escola existe na tb_escola
    $sql_escola = "SELECT codigoEscola FROM tb_escola WHERE codigoEscola = ?";
    $stmt_escola = $conn->prepare($sql_escola);
    $stmt_escola->bind_param('s', $codigoEscola);
    $stmt_escola->execute();
    $stmt_escola->store_result();
    
    // Se não encontrou nenhuma linha, o código é inválido
    if ($stmt_escola->num_rows === 0) {
        $stmt_escola->close();
        $conn->close();
        header("Location: cadastro.html?erro=codigo_escola_invalido");
        exit();
    }
    
    $stmt_escola->close();
}


// --- 5. INSERÇÃO DO NOVO USUÁRIO ---
// Cria o hash da senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// ATUALIZAÇÃO DO SQL: Adiciona 'cpf' na lista de colunas
$sql = "INSERT INTO tb_usuario (nomeCompleto, email, cpf, nomeUsuario, senha, telefone, datNasc, tipoUsuario, codigoEscola, plano) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // Total de 10 placeholders (?)
$stmtInsert = $conn->prepare($sql);

// ATUALIZAÇÃO DO bind_param: Adiciona o $cpf como terceiro parâmetro ('s')
$stmtInsert->bind_param('ssssssssss', $nomeCompleto, $email, $cpf, $nomeUsuario, $senhaHash, $telefone, $datNasc, $tipoUsuario, $codigoEscola, $plano);

if ($stmtInsert->execute()) {
    // Redireciona para home.php após o sucesso
    echo "<script>window.location.href = 'home.php';</script>";
} else {
    // Erro genérico (melhor para segurança)
    echo "Erro ao cadastrar. Tente novamente mais tarde. Detalhe técnico: " . $conn->error;
}


if (isset($stmtInsert)) {
    $stmtInsert->close();
}
$conn->close();
?>