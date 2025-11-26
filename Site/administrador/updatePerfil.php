<?php
session_start();

require_once '../conexao/conecta.php';

// A variável de conexão $conn agora vem do arquivo 'conecta.php'.
// A lógica local de conexão foi removida.

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_SESSION["id"];
    $nomeCompleto = trim($_POST["nomeCompleto"]);
    $email = $_POST["email"];
    $nomeUsuario = trim($_POST["nomeUsuario"]);
    $telefone = $_POST["telefone"];
    $datNasc = $_POST["datNasc"];
    $metaProvas = $_POST["metaProvas"];

    // Verificar se o nome de usuário já existe
    $sql_check = "SELECT id FROM tb_usuario WHERE nomeUsuario = ? AND id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $nomeUsuario, $id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $stmt_check->close();
        $conn->close();
        header("Location: configuracoes.php?error=Este+nome+de+usuário+já+está+em+uso.+Por+favor+escolha+outro.");
        exit();
    }
    $stmt_check->close();

    // Processamento da imagem
    $fotoUsuario = null;
    $updateFoto = false;
    $tipoImagem = null;

    if (isset($_FILES['fotoUsuario']) && $_FILES['fotoUsuario']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['fotoUsuario']['tmp_name']);
        
        $allowedTypes = [
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];

        if (!array_key_exists($mime, $allowedTypes)) {
            header("Location: configuracoes.php?error=Tipo+de+arquivo+inválido.+Apenas+JPG,+PNG+e+GIF+são+permitidos.");
            exit();
        }

        if ($_FILES['fotoUsuario']['size'] > 2097152) {
            header("Location: configuracoes.php?error=O+arquivo+é+grande+demais.+O+tamanho+máximo+permitido+é+2MB.");
            exit();
        }

        $tipoImagem = $mime;
        $fotoUsuario = file_get_contents($_FILES['fotoUsuario']['tmp_name']);
        $updateFoto = true;
    }

    // Preparação da query SQL
    if ($updateFoto) {
        $sql = "UPDATE tb_usuario SET nomeCompleto=?, email=?, nomeUsuario=?, telefone=?, datNasc=?, metaProvas=?, fotoUsuario=?, tipoImagem=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssisss", $nomeCompleto, $email, $nomeUsuario, $telefone, $datNasc, $metaProvas, $fotoUsuario, $tipoImagem, $id);
    } else {
        $sql = "UPDATE tb_usuario SET nomeCompleto=?, email=?, nomeUsuario=?, telefone=?, datNasc=?, metaProvas=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $nomeCompleto, $email, $nomeUsuario, $telefone, $datNasc, $metaProvas, $id);
    }

    if ($stmt->execute()) {
        if (isset($_SESSION['nomeUsuario']) && $_SESSION['nomeUsuario'] !== $nomeUsuario) {
            $_SESSION['nomeUsuario'] = $nomeUsuario;
        }
        header("Location: configuracoes.php?success=Perfil+atualizado+com+sucesso!");
    } else {
        header("Location: configuracoes.php?error=Erro+ao+atualizar+registro:+".urlencode($stmt->error));
    }

    $stmt->close();
}
$conn->close();
?>