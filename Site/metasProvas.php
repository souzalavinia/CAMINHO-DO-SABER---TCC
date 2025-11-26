<?php
// Verificando se está logado
session_start();
if (isset($_SESSION['id'])) {
    $id_sessao = $_SESSION["id"];
} else {
    header("Location: login.html");
    exit(); // Adicionado para garantir que o script pare de executar após o redirecionamento
}
 
include 'conexao/conecta.php';
 
// Usando uma consulta preparada para evitar injeção de SQL
$stmt = $conn->prepare("SELECT * FROM tb_usuario WHERE idUsuario = ?");
$stmt->bind_param("i", $id_sessao); // O "i" indica que o parâmetro é um inteiro
$stmt->execute();
 
$result = $stmt->get_result();
 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $id = $row["id"]; // Nota: Você está reatribuindo a variável $id. Talvez seja melhor usar um nome diferente, como $usuario_id.
}
 
$stmt->close();
$conn->close();
?>