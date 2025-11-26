<?php
$host = 'localhost';
$db = 'renant49_bdcaminho';
$user = 'renant49_master';
$pass = 'cQm9dZ8~aNMK';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
?>
