<?php
session_start();
// VERIFICAÇÃO DE ACESSO: Deve ser ADMIN logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.html?acesso=negado");
    exit();
}

$nome_display = htmlspecialchars(explode(' ', $_SESSION['nome_usuario'])[0]);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CAPIADVENTURE - Painel Admin</title>
  <link rel="stylesheet" href="style.css">
  <style>
        /* Configuração da Logo com Sombra Preta */
        .brand .logo {
            width: 80px; 
            height: 80px; 
            background-color: transparent;
            /* Propriedades para arredondar o círculo e aplicar a sombra */
            border-radius: 50%; /* Faz o elemento ser um círculo */
            /* Sombra preta com 70% de opacidade */
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
        }

        .brand .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain; 
        }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="brand">
      <div class="logo">
        <img src="capivara.png" alt="Logotipo CAPIADVENTURE">
      </div>
      <div class="title">Painel Admin</div>
    </div>
    
    <nav class="nav-links" aria-label="Opções de administração">
        <ul>
            <li><a href="boas_vindas.php" class="top-link">Voltar</a></li>
            <li><a href="cadastro_trilha.php" class="top-link">Cadastrar Trilha</a></li>
            <li><a href="logout.php" class="top-link">Sair</a></li>
        </ul>
    </nav>
  </header>

  <main class="main">
    <section class="screen" id="tela_admin" aria-labelledby="admin-heading">
      <div class="login-box boas-vindas-box">
        
        <h2 id="admin-heading">Bem-vindo(a), Administrador(a) <?php echo $nome_display; ?>!</h2>

        <p>Use o menu acima para gerenciar as trilhas, usuários e agendamentos.</p>

        <a href="cadastro_trilha.php" class="btn">Cadastrar Nova Trilha</a>
      </div>
    </section>
  </main>
  
  <footer class="footer">
    </footer>
</body>
</html>

