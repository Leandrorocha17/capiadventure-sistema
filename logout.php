<?php
// Inicia a sessão
session_start();

// Limpa todas as variáveis de sessão
session_unset();

// Destrói a sessão
session_destroy();

// Redireciona para a página principal
header("Location: trilhas.php");
exit();
?>