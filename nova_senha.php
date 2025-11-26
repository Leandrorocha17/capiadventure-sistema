<?php
session_start();

// Verifica se a chave temporária da sessão e da URL coincidem
if (!isset($_SESSION['recuperacao_chave']) || !isset($_GET['key']) || $_SESSION['recuperacao_chave'] !== $_GET['key']) {
    header("Location: login.html?erro=acesso_invalido");
    exit();
}

// O email é necessário para o script de processamento
if (!isset($_SESSION['recuperacao_email'])) {
    header("Location: login.html?erro=sessao_expirada");
    exit();
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="description" content="Página para definir nova senha CAPIADVENTURE."/>
  <title>CAPIADVENTURE - Nova Senha</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="topbar">
    <div class="brand">
      <div class="logo">
        <img src="2fdba1d2-ea30-41dc-ab45-1e600eb6700b.png" alt="Logotipo CAPIADVENTURE - Capivara mascote" style="width:60px; height:60px; border-radius:50%;">
      </div>
      <div class="title">CAPIADVENTURE</div>
    </div>

    <nav class="nav-links" aria-label="Acessos">
        <ul>
            <li><a href="login.html" class="top-link">Login</a></li>
        </ul>
    </nav>
  </header>

  <main class="main">
    <section class="screen" id="tela_nova_senha" aria-labelledby="nova-senha-heading">
      <div class="login-box validacao-box"> 
        <a href="login.html" class="close-btn" aria-label="Fechar e voltar para o login">×</a>
        
        <h2 id="nova-senha-heading">Definir Nova Senha</h2> 

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'mismatch'): ?>
                <p class="mensagem-erro">Erro: As senhas não coincidem.</p>
            <?php elseif ($_GET['status'] == 'sucesso'): ?>
                <p class="mensagem-sucesso">Senha alterada com sucesso! Redirecionando para o login...</p>
                <script>
                    setTimeout(function() {
                        window.location.href = 'login.html';
                    }, 3000);
                </script>
            <?php elseif ($_GET['status'] == 'erro'): ?>
                <p class="mensagem-erro">Erro: Falha ao tentar alterar a senha no banco de dados.</p>
            <?php endif; ?>
        <?php endif; ?>

        <form action="processa_nova_senha.php" method="POST" autocomplete="off">
          
          <input type="hidden" name="key" value="<?php echo htmlspecialchars($_SESSION['recuperacao_chave']); ?>">
          <input type="hidden" name="email_recuperacao" value="<?php echo htmlspecialchars($_SESSION['recuperacao_email']); ?>">

          <label for="nova-senha" class="sr-only">Nova Senha:</label>
          <input id="nova-senha" type="password" placeholder="Digite a nova senha" name="nova_senha" autocomplete="new-password" required>
          
          <label for="confirma-senha" class="sr-only">Confirmar Senha:</label>
          <input id="confirma-senha" type="password" placeholder="Confirme a nova senha" name="confirma_senha" autocomplete="new-password" required>

          <button type="submit" class="btn">Atualizar Senha</button>

        </form>
      </div>
    </section>
  </main>

  <footer class="footer">
    </footer>
</body>
</html>