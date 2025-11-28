<?php
require 'conexao.php';
session_start();

// 1. VERIFICAÇÃO DE ACESSO: O usuário deve estar logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    // Redireciona para o login se não estiver logado
    header("Location: login.html?acesso=negado"); 
    exit();
}

// 2. RECEBE O ID DA TRILHA
$id_trilha = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$nome_trilha = 'Trilha Desconhecida';
$imagens = [];

if (!$id_trilha) {
    // Se o ID não for fornecido ou for inválido, exibe um erro
    $erro_msg = "ID da trilha não fornecido ou inválido.";
} else {
    try {
        // 3. BUSCA O NOME DA TRILHA
        $sql_nome = "SELECT nome FROM tb_trilhas WHERE id_trilha = :id";
        $stmt_nome = $pdo->prepare($sql_nome);
        $stmt_nome->bindValue(':id', $id_trilha, PDO::PARAM_INT);
        $stmt_nome->execute();
        $resultado_nome = $stmt_nome->fetch(PDO::FETCH_ASSOC);

        if ($resultado_nome) {
            $nome_trilha = htmlspecialchars($resultado_nome['nome']);
        }

        // 4. BUSCA AS IMAGENS ASSOCIADAS
        // Assume a existência da tabela 'tb_trilhas_imagens'.
        $sql_imagens = "SELECT caminho_imagem FROM tb_trilhas_imagens WHERE id_trilha = :id ORDER BY ordem ASC";
        $stmt_imagens = $pdo->prepare($sql_imagens);
        $stmt_imagens->bindValue(':id', $id_trilha, PDO::PARAM_INT);
        $stmt_imagens->execute();
        $imagens = $stmt_imagens->fetchAll(PDO::FETCH_COLUMN, 0);

        if (empty($imagens)) {
            $erro_msg = "Nenhuma foto encontrada para a trilha $nome_trilha.";
        }

    } catch (PDOException $e) {
        $erro_msg = "Erro ao carregar as fotos: " . $e->getMessage();
    }
}

// 5. Verifica se o usuário é um administrador (para links da topbar)
$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="description" content="Galeria de fotos da trilha no CAPIADVENTURE."/>
  <title>CAPIADVENTURE - Galeria: <?php echo $nome_trilha; ?></title>
  <link rel="stylesheet" href="style.css">
  <style>
        /* Estilos básicos de cabeçalho e rodapé (para manter a consistência) */
        .brand .logo {
            width: 80px; 
            height: 80px; 
            background-color: transparent;
            border-radius: 50%;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
        }

        .brand .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain; 
        }
        
        .top-link:hover {
            text-decoration: none; 
            transform: none !important; 
            transition: none !important; 
        }
        
        .footer {
            background-color: black; 
            color: white; 
            text-align: center; 
            padding: 15px 0;
            width: 100%;
            position: relative; /* Ajuste para não flutuar */
            margin-top: 30px;
        }

        /* Estilos específicos da Galeria */
        .galeria-container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            text-align: center;
        }

        .galeria-container h2 {
            /* ALTERADO: Cor do título para 50% preto */
            color: rgba(0, 0, 0, 0.5);
            margin-bottom: 30px;
            /* ALTERADO: Linha debaixo do título removida */
            border-bottom: none; 
            padding-bottom: 10px;
        }

        /* AJUSTE AQUI: Estilo da Mensagem de instrução na topbar */
        .topbar-instrucao {
            /* Garante que o item ocupe o espaço */
            flex-grow: 1; 
            display: flex;
            /* ALINHAMENTO À DIREITA (flex-end) */
            justify-content: flex-end; 
            align-items: center; /* Centraliza verticalmente */
            color: white;
            /* TAMANHO IGUAL AOS LINKS ORIGINAIS */
            font-size: 1.1em; 
            font-weight: bold;
            padding: 10px 20px; /* Adiciona padding vertical e horizontal */
            /* SEM SOMBRA DE TEXTO */
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
            gap: 20px;
            padding: 10px;
        }

        .gallery-item {
            overflow: hidden;
            border-radius: 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #fff;
            transition: none; 
        }

        .gallery-item img {
            width: 100%;
            height: 350px; 
            object-fit: cover;
            display: block;
        }

    </style>
</head>
<body>
  <header class="topbar">
    <div class="brand">
        <div class="logo">
            <img src="capivara.png" alt="Logotipo CAPIADVENTURE - Capivara mascote">
        </div>
        <div class="title">CAPIADVENTURE</div>
    </div>
    
    <div class="topbar-instrucao" aria-label="Instrução de navegação">
        Clique em qualquer lugar para voltar
    </div>
    
  </header>

  <main class="main">
    <section class="screen" id="tela_galeria" aria-labelledby="galeria-heading">
      <div class="galeria-container">
        
        <h2 id="galeria-heading">
            Fotos da trilha: <?php echo $nome_trilha; ?>
        </h2>
        
        <?php if (isset($erro_msg)): ?>
            <p style="color: red; text-align: center; margin-top: 20px;"><?php echo $erro_msg; ?></p>
        <?php elseif (!empty($imagens)): ?>
            <div class="gallery-grid">
                <?php foreach ($imagens as $index => $caminho): ?>
                    <div class="gallery-item">
                        <img 
                            src="<?php echo htmlspecialchars($caminho); ?>" 
                            alt="Foto <?php echo $index + 1; ?> da trilha <?php echo $nome_trilha; ?>"
                        >
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
             <p style="font-size: 1.1em; color: #555; margin-top: 20px;">Não foi possível carregar as imagens desta trilha.</p>
        <?php endif; ?>
        
      </div>
    </section>
  </main>

  <footer class="footer">
    <p><?php echo date('Y'); ?> CAPIADVENTURE. Desenvolvido por Leandro Rocha & Gabriel Gonçalves.</p>
  </footer>
  
  <script>
    // Define o destino padrão, que é 'meus_agendamentos.php' (para trilheiros)
    let destino = 'meus_agendamentos.php';
    
    // Captura o valor de 'origem' da URL se existir
    const urlParams = new URLSearchParams(window.location.search);
    const origem = urlParams.get('origem');

    // Se a origem for 'admin', muda o destino para gerenciar_trilhas.php
    if (origem === 'admin') {
        destino = 'gerenciar_trilhas.php';
    }
    
    // Script para redirecionar para a tela de origem ao clicar em qualquer lugar
    document.addEventListener('click', function(event) {
        // Verifica se o clique ocorreu em um elemento <a> (link) ou <button>
        if (event.target.closest('a') || event.target.closest('button')) {
            return; // Sai da função e permite que o link/botão funcione normalmente
        }
        
        // Redireciona a página para o destino correto
        window.location.href = destino; 
    });
  </script>
</body>

</html>
