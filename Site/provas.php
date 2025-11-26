<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prova</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
        }
        .questao {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin: 5px 0;
        }
        button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }

    </style>
</head>
<body>

<?php

include 'conexao/conecta.php';

// Prepara a consulta SQL
$sql = "SELECT id, nome FROM tb_prova";
$result = $conn->query($sql);

// Verifica se há resultados
if ($result->num_rows > 0) {
    echo "<h1>Provas</h1>";
    echo "<ul>";

    // Exibe cada linha como um link
    while ($row = $result->fetch_assoc()) {
        echo "<a href='mostraQuest.php?id=" . $row['id'] . "'>" . $row['nome'] . "</a><br>";
    }

    echo "</ul>";
} else {
    echo "Nenhum resultado encontrado.";
}

$conn->close();
?>


</body>
</html>