<?php
session_start();
require_once '../conexao/conecta.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: configuracoes.php");
    exit();
}

$id = $_SESSION["id"];
$nomeCompleto = trim($_POST["nomeCompleto"]);
$email = trim($_POST["email"]);
$nomeUsuario = trim($_POST["nomeUsuario"]);
$telefone = preg_replace('/\D/', '', $_POST["telefone"]);
$datNasc = preg_replace('/\D/', '', $_POST["datNasc"]);
$metaProvas = (int)$_POST["metaProvas"];
$cpf = preg_replace('/\D/', '', $_POST["cpf"] ?? '');

// Verificar se o nome de usuário já existe
$sql_check = "SELECT id FROM tb_usuario WHERE nomeUsuario = ? AND id != ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("si", $nomeUsuario, $id);
$stmt_check->execute();
$result = $stmt_check->get_result();
if ($result->num_rows > 0) {
    $stmt_check->close();
    $conn->close();
    header("Location: configuracoes.php?error=Este+nome+de+usuário+já+está+em+uso");
    exit();
}
$stmt_check->close();

// Processar imagem
$updateFoto = false;
$fotoUsuario = null;
$tipoImagem = null;

if (isset($_FILES['fotoUsuario']) && $_FILES['fotoUsuario']['error'] === UPLOAD_ERR_OK) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['fotoUsuario']['tmp_name']);
    $allowedTypes = ['image/jpeg' => 'jpeg', 'image/png' => 'png', 'image/gif' => 'gif'];

    if (!array_key_exists($mime, $allowedTypes)) {
        header("Location: configuracoes.php?error=Tipo+de+arquivo+inválido");
        exit();
    }
    if ($_FILES['fotoUsuario']['size'] > 2097152) {
        header("Location: configuracoes.php?error=Arquivo+maior+que+2MB");
        exit();
    }

    $tipoImagem = $mime;
    $fotoUsuario = file_get_contents($_FILES['fotoUsuario']['tmp_name']);
    $updateFoto = true;
}

// Montar query
if ($updateFoto) {
    $sql = "UPDATE tb_usuario 
            SET nomeCompleto=?, email=?, nomeUsuario=?, telefone=?, datNasc=?, metaProvas=?, cpf=?, fotoUsuario=?, tipoImagem=? 
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro na preparação: " . $conn->error);
    }

    // Bind com blob (fotoUsuario)
    $stmt->bind_param("sssssisbsi", 
        $nomeCompleto, $email, $nomeUsuario, $telefone, $datNasc, $metaProvas, $cpf, $fotoUsuario, $tipoImagem, $id
    );
    $stmt->send_long_data(7, $fotoUsuario); // índice 7 = fotoUsuario
} else {
    $sql = "UPDATE tb_usuario 
            SET nomeCompleto=?, email=?, nomeUsuario=?, telefone=?, datNasc=?, metaProvas=?, cpf=? 
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro na preparação: " . $conn->error);
    }

    // Bind sem foto
    $stmt->bind_param("sssssisi", 
        $nomeCompleto, $email, $nomeUsuario, $telefone, $datNasc, $metaProvas, $cpf, $id
    );
}

// Executar
if ($stmt->execute()) {
    $_SESSION['nomeUsuario'] = $nomeUsuario;
    header("Location: configuracoes.php?success=Perfil+atualizado+com+sucesso");
} else {
    header("Location: configuracoes.php?error=Erro+ao+atualizar:" . urlencode($stmt->error));
}

$stmt->close();
$conn->close();
?>
