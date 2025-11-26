<?php
session_start();
require_once '../conexao/conecta.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$idUsuario = (int) $_SESSION['id'];

// Busca os dados do usuário para verificar as condições
$stmt = $conn->prepare("SELECT tipoUsuario, plano, codigoEscola FROM tb_usuario WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da consulta: " . $conn->error);
}

$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if (!$usuario) {
    echo "<script>alert('Usuário não encontrado.'); window.location='configuracoes.php';</script>";
    exit();
}

// Verifica se o usuário pode cancelar a assinatura
if ($usuario['plano'] === 'Basico') {
    echo "<script>alert('Seu plano já é o Básico, não é possível cancelar a assinatura.'); window.location='configuracoes.php';</script>";
    exit();
}

// Inicia uma transação para garantir a atomicidade das operações
$conn->begin_transaction();
$sucesso = false;
$mensagem = '';

try {
    // Se o usuário é um Diretor
    if ($usuario['tipoUsuario'] === 'Diretor' && !empty($usuario['codigoEscola'])) {
        $codigoEscola = $usuario['codigoEscola'];
        
        // 1. Desvincula TODOS OS ALUNOS da escola (muda plano para Básico e limpa codigoEscola)
        // Usamos um WHERE id != ? para excluir o diretor dessa operação, pois ele será tratado no passo 2
        $sql_alunos = "UPDATE tb_usuario SET plano = 'Basico', codigoEscola = NULL, statusPlano='pendente' WHERE codigoEscola = ? AND id != ?";
        $stmt_alunos = $conn->prepare($sql_alunos);
        $stmt_alunos->bind_param("si", $codigoEscola, $idUsuario);
        $stmt_alunos->execute();
        $stmt_alunos->close();

        // 2. MUDANÇA PRINCIPAL: Atualiza o Diretor (plano=Básico, tipo=estudante, codigoEscola=NULL)
        $sql_diretor = "UPDATE tb_usuario SET plano = 'Basico', tipoUsuario = 'estudante', codigoEscola = NULL WHERE id = ?";
        $stmt_diretor = $conn->prepare($sql_diretor);
        $stmt_diretor->bind_param("i", $idUsuario);
        $stmt_diretor->execute();
        $stmt_diretor->close();
        
        // 3. Deleta o registro da assinatura na tb_assinaturas (usando codigoEscola para pegar todos os alunos e o Diretor)
        $sql_delete_assinatura = "DELETE FROM tb_assinaturas WHERE codigoEscola = ?";
        $stmt_assinatura = $conn->prepare($sql_delete_assinatura);
        $stmt_assinatura->bind_param("s", $codigoEscola);
        $stmt_assinatura->execute();
        $stmt_assinatura->close();

        // 4. Deleta o registro da escola na tb_escola
        $sql_delete_escola = "DELETE FROM tb_escola WHERE codigoEscola = ?";
        $stmt_escola = $conn->prepare($sql_delete_escola);
        $stmt_escola->bind_param("s", $codigoEscola);
        $stmt_escola->execute();
        $stmt_escola->close();

        // Se chegou até aqui, todas as consultas foram bem-sucedidas
        $conn->commit();
        $sucesso = true;
        $mensagem = 'Sua assinatura e o cadastro da escola foram cancelados. Seu perfil foi redefinido para estudante Básico e todos os alunos foram desvinculados.';

    } else { // Se o usuário é um aluno ou um usuário comum
        
        // 1. Atualiza apenas o plano do usuário atual para 'Basico' e limpa o codigoEscola
        $sql_update_user = "UPDATE tb_usuario SET plano = 'Basico', codigoEscola = NULL WHERE id = ?";
        $stmt_user = $conn->prepare($sql_update_user);
        $stmt_user->bind_param("i", $idUsuario);
        $stmt_user->execute();
        $stmt_user->close();
        
        // 2. Deleta o registro de assinatura individual (pelo idUsuario)
        $sql_delete_assinatura = "DELETE FROM tb_assinaturas WHERE idUsuario = ?";
        $stmt_assinatura = $conn->prepare($sql_delete_assinatura);
        $stmt_assinatura->bind_param("i", $idUsuario);
        $stmt_assinatura->execute();
        $stmt_assinatura->close();

        // Se chegou até aqui
        $conn->commit();
        $sucesso = true;
        $mensagem = 'Sua assinatura foi cancelada com sucesso. Seu plano foi alterado para Básico.';
    }

} catch (mysqli_sql_exception $e) {
    // Em caso de qualquer erro, desfaz todas as operações
    $conn->rollback();
    $mensagem = "Erro ao processar o cancelamento: " . $e->getMessage();
    $sucesso = false;
}

// === LÓGICA DE REDIRECIONAMENTO E SESSÃO ===
if ($sucesso) {
    // Exibe a mensagem de sucesso e destrói a sessão forçando o login
    echo "<script>
        alert('$mensagem');
        window.location='../login.php'; // Redireciona para o login
    </script>";
    
    // Destrói a sessão atual e encerra o script
    session_destroy();
    exit();
} else {
    // Exibe a mensagem de erro e retorna para a página de configuração
    echo "<script>alert('$mensagem'); window.location='configuracoes.php';</script>";
}

$conn->close();
?>