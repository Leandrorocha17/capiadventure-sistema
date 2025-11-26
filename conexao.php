<?php
// Define as credenciais do banco de dados (padrão WAMP)
$host = 'localhost';
$db   = 'bd_capiadventure';
$user = 'root';
$pass = ''; // Geralmente vazio no WAMP por padrão
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Exibir erro em ambiente de desenvolvimento
     die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
?>