<?php

// Verifica se a variável de ambiente JAWSDB_URL existe (ambiente Heroku/Produção)
if (getenv('JAWSDB_URL')) {
    
    // Pega a string de conexão do ambiente Heroku (ex: mysql://user:pass@host:port/dbname)
    $url = getenv('JAWSDB_URL');
    
    // Analisa a URL para extrair as partes (Host, DB, User, Pass)
    $dbparts = parse_url($url);
    
    $host = $dbparts['host'];
    $user = $dbparts['user'];
    $pass = $dbparts['pass'];
    
    // O nome do banco de dados está no 'path' (caminho), e removemos a barra inicial (/)
    $db = ltrim($dbparts['path'], '/');
    
    $charset = 'utf8mb4'; // Mantém o charset
    
} else {
    // === AMBIENTE DE DESENVOLVIMENTO LOCAL (WAMP/MAMP/XAMPP) ===
    
    $host = 'localhost';
    $db   = 'bd_capiadventure';
    $user = 'root';
    $pass = ''; // Geralmente vazio no WAMP por padrão
    $charset = 'utf8mb4';
}

// ------------------------------------------------------------------
// LÓGICA DE CONEXÃO PDO (É A MESMA PARA AMBOS OS AMBIENTES)
// ------------------------------------------------------------------

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     // Tenta estabelecer a conexão PDO com os parâmetros definidos acima
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Exibe erro de conexão
     die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// O objeto de conexão ($pdo) agora está disponível para ser usado no resto da sua aplicação.
?>
