<?php
session_start();
// VERIFICAÇÃO DE ACESSO: Se não estiver logado, redireciona para o login
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.html?acesso=negado"); 
    exit();
}

// Verifica se a senha foi alterada com sucesso (parâmetro enviado pelo processamento)
$sucesso_alteracao = isset($_GET['status']) && $_GET['status'] === 'sucesso';

$nome_display = htmlspecialchars(explode(' ', $_SESSION['nome_usuario'])[0]);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="description" content="Página de alteração de senha para usuários logados no CAPIADVENTURE."/>
  <title>CAPIADVENTURE - Alterar Senha</title>
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

        /* ESTILO DO RODAPÉ (PRETO SÓLIDO 100% - COPIADO DE index.php) */
        .footer {
            background-color: black; /* Cor PRETA SÓLIDA (100%) */
            color: white; 
            text-align: center; 
            padding: 15px 0;
            width: 100%;
        }

        /* REMOVENDO SUBILINHADO E MOVIMENTO DOS LINKS SUPERIORES */
        .top-link:hover {
            text-decoration: none !important;
            transform: none !important; /* Remove qualquer movimento */
            transition: none !important; /* Remove qualquer transição que cause movimento */
        }
        
        /* REMOVENDO SUBILINHADO E MOVIMENTO DO BOTÃO DE CONFIRMAÇÃO */
        .login-box button[type="submit"]:hover {
            /* Garante que o sublinhado seja removido se houver herança */
            text-decoration: none !important; 
            /* Garante que não haja movimento (transform) */
            transform: none !important; 
            background-color: #2e9eb6; 
        }

        /* Estilizando o botão de confirmação para ter o mesmo tamanho da tela index */
        .login-box button[type="submit"] {
            display: block; /* Ocupa a largura total do container */
            margin: 15px auto;
            /* AJUSTES FINAIS PARA IGUALAR O BOTÃO 'QUERO AGENDAR ESTA TRILHA' */
            padding: 12px 25px; 
            font-size: 1.15em; 
            text-align: center;
            max-width: 300px; /* Largura máxima igual a index.html */
            /* FIM DOS AJUSTES */
            font-weight: bold;
            border-radius: 8px;
            /* Herda as cores do .btn padrão ou ajuste se necessário */
            background-color: #34B5CC; 
            color: white;
            border: none;
            transition: background-color 0.3s;
        }
        
        /* Estilizando o título H2 (Alterar Senha) para Preto 50% */
        .login-box h2 {
            color: rgba(0, 0, 0, 0.5); /* Preto com 50% de opacidade */
        }
        
        /* NOVO ESTILO: Garante que o container do formulário seja relativo para posicionar o botão 'x' */
        .login-box {
            position: relative; 
        }
        
        /* NOVO ESTILO: Botão de fechar (x) */
        .close-button {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5em; /* Aumenta o tamanho do 'x' */
            font-weight: bold;
            color: #555;
            text-decoration: none;
            transition: color 0.3s;
            line-height: 1;
            padding: 5px;
        }

        .close-button:hover {
            color: #dc3545; /* Vermelho suave ao passar o mouse */
            text-decoration: none;
            cursor: pointer;
        }
        
        /* A estilização específica para o botão 'Voltar' foi removida,
           para que ele herde o estilo padrão do .top-link. */
  </style>

  <?php if ($sucesso_alteracao): ?>
  <meta http-equiv="refresh" content="3;url=trilhas.php">
  <?php endif; ?>

</head>
<body>
  <header class="topbar">
    <div class="brand">
      <div class="logo">
        <img src="capivara.png" alt="Logotipo CAPIADVENTURE - Capivara mascote">
      </div>
      <div class="title">CAPIADVENTURE</div>
    </div>
    
    <nav class="nav-links" aria-label="Navegação de Retorno">
        <ul>
            <li><a href="javascript:history.back()" class="top-link" title="Voltar para a página anterior">Voltar</a></li>
        </ul>
    </nav>
  </header>

  <main class="main">
    <section class="screen" id="tela_alterar_senha_logado" aria-labelledby="senha-heading">
      <div class="login-box alteracao-logado-box">
        
        <a href="javascript:history.back()" class="close-button" title="Voltar para a tela anterior">
            &times;
        </a>
        
        <h2 id="senha-heading">Alterar Senha</h2> 

        <?php if ($sucesso_alteracao): ?>
            <div style="padding: 20px; background-color: #ddf; color: #00a; border: 1px solid #aaf; border-radius: 4px; text-align: center;">
                <p style="font-size: 1.1em; font-weight: bold;">Senha alterada com sucesso!</p>
                <p>Você será redirecionado em 3 segundos...</p>
                <p><a href="trilhas.php" style="color: #00a;">Clique aqui para ir agora.</a></p>
            </div>

        <?php else: ?>
            <?php if (isset($_GET['erro']) && $_GET['erro'] === 'atual'): ?>
                <div style="padding: 10px; margin-bottom: 15px; background-color: #fdd; color: #a00; border: 1px solid #faa; border-radius: 4px;">
                    Erro: Senha atual incorreta.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro']) && $_GET['erro'] === 'invalida'): ?>
                <div style="padding: 10px; margin-bottom: 15px; background-color: #fdd; color: #a00; border: 1px solid #faa; border-radius: 4px;">
                    Erro: A nova senha não pode ser vazia ou as senhas não coincidem.
                </div>
            <?php endif; ?>

            <form action="processa_nova_senha.php" method="POST" autocomplete="off">
              
              <label for="senha_atual" class="sr-only">Senha Atual:</label>
              <input id="senha_atual" type="password" placeholder="Senha Atual" name="senha_atual" autocomplete="current-password" required>

              <label for="nova_senha" class="sr-only">Nova Senha:</label>
              <input id="nova_senha" type="password" placeholder="Nova Senha" name="nova_senha" autocomplete="new-password" required>
              
              <label for="confirma_senha" class="sr-only">Confirme a Nova Senha:</label>
              <input id="confirma_senha" type="password" placeholder="Confirme a Nova Senha" name="confirma_senha" autocomplete="new-password" required>
              
              <button type="submit" class="btn">Confirmar Alteração</button>
            </form>

        <?php endif; ?>

      </div>
    </section>
  </main>
  
  <footer class="footer">
    <p><?php echo date('Y'); ?> CAPIADVENTURE. Desenvolvido por Leandro Rocha & Gabriel Gonçalves.</p>
  </footer>
</body>
</html>
