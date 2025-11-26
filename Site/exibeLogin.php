<?php
session_start();

include 'conexao/conecta.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioLogin = trim($_POST['usuarioLogin']);
    $loginSenha = $_POST['loginSenha'];

    // A consulta já está correta, buscando codigoEscola
    $stmt = $conn->prepare("SELECT senha, id, tipoUsuario, plano, codigoEscola, statusPlano FROM tb_usuario WHERE nomeUsuario = ?");
    $stmt->bind_param("s", $usuarioLogin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $senha_hash = $row["senha"];

        if (password_verify($loginSenha, $senha_hash)) {
            // Login bem-sucedido
            $_SESSION['nomeUsuario'] = $usuarioLogin;
            $_SESSION['id'] = $row["id"];
            
            // ==========================================================
            // *** CORREÇÃO APLICADA AQUI: SALVANDO O CÓDIGO DA ESCOLA ***
            // Garante que o valor da coluna 'codigoEscola' seja salvo na sessão.
            $_SESSION['codigoEscola'] = $row['codigoEscola'] ?? ''; 
            // ==========================================================
            
            // PADRONIZAÇÃO E VALIDAÇÃO:
            // Converte o tipo de usuário para minúsculas e armazena
            $tipoUsuario = strtolower(trim($row["tipoUsuario"]));
            $_SESSION['tipoUsuario'] = $tipoUsuario;

            // Lógica do plano (sem alterações)
            $planoFinal = $row["plano"];
            if (strtolower($planoFinal) === 'basico' && !empty($row["codigoEscola"]) && strtolower($row["statusPlano"]) === 'habilitado') {
                $stmtEscola = $conn->prepare("SELECT plano FROM tb_escola WHERE codigoEscola = ?");
                $stmtEscola->bind_param("s", $row["codigoEscola"]);
                $stmtEscola->execute();
                $resultEscola = $stmtEscola->get_result();
                if ($resultEscola->num_rows > 0) {
                    $rowEscola = $resultEscola->fetch_assoc();
                    $planoFinal = $rowEscola['plano'];
                }
                $stmtEscola->close();
            }
            $_SESSION['planoUsuario'] = $planoFinal;

            // REDIRECIONAMENTO COM VALIDAÇÃO ROBUSTA:
            $tiposValidos = ['estudante', 'diretor', 'administrador'];
            
            if (in_array($tipoUsuario, $tiposValidos)) {
                switch ($tipoUsuario) {
                    case 'estudante':
                        header("Location: home.php");
                        exit();
                    case 'diretor':
                        // O diretor agora terá o codigoEscola na sessão para usar no relatorioDiretor.php
                        header("Location: diretor/home.php");
                        exit();
                    case 'administrador':
                        header("Location: administrador/exibirProvas.php");
                        exit();
                }
            } else {
                // Se o tipo do usuário não for reconhecido
                session_destroy();
                header("Location: login.php?status=error&message=Tipo de usuário inválido.");
                exit();
            }

        } else {
            // Senha incorreta
            header("Location: login.php?status=error&message=Senha incorreta!");
            exit();
        }
    } else {
        // Usuário não encontrado
        header("Location: login.php?status=error&message=Usuário não encontrado!");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>