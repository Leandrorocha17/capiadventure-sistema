<?php
require 'conexao.php';
session_start();

// 1. VERIFICAÇÃO DE ACESSO: Deve ser usuário logado (trilheiro)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_trilheiro'])) {
    header("Location: login.html?acesso=negado"); 
    exit();
}

$id_trilheiro = $_SESSION['id_trilheiro'];
$nome_display = htmlspecialchars(explode(' ', $_SESSION['nome_usuario'])[0]);
$agendamentos = [];

// Variável para mensagem de status (Cancelamento/Edição)
$mensagem_status = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'cancelado_sucesso') {
        $mensagem_status = '<p style="padding: 10px; margin-bottom: 15px; border-radius: 4px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;">Agendamento cancelado com sucesso.</p>';
    } elseif ($_GET['status'] === 'erro') {
        $mensagem_status = '<p style="padding: 10px; margin-bottom: 15px; border-radius: 4px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">Erro ao cancelar o agendamento. Tente novamente.</p>';
    } elseif ($_GET['status'] === 'erro_edicao') {
         $mensagem_status = '<p style="padding: 10px; margin-bottom: 15px; border-radius: 4px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">Erro ao editar o agendamento. Verifique os dados.</p>';
    } elseif ($_GET['status'] === 'sucesso_edicao') {
         $mensagem_status = '<p style="padding: 10px; margin-bottom: 15px; border-radius: 4px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;">Agendamento editado com sucesso!</p>';
    }
}

// REMOVIDA: A função buscarImagensTrilha não é mais necessária nesta página

try {
    // 2. BUSCA DOS AGENDAMENTOS DO TRILHEIRO
    // BUSCANDO: Trazendo informações da trilha para exibir
    $sql = "
        SELECT 
            a.id_agendamento, a.data_agendamento, a.horario, a.status,
            t.id_trilha, t.nome AS nome_trilha, t.regiao, t.nivel_dificuldade, t.distancia, t.tipo_trilha
        FROM tb_agendamento a
        JOIN tb_trilhas t ON a.id_trilha = t.id_trilha
        WHERE a.id_trilheiro = ? AND a.status IN ('Agendado', 'Em análise')
        ORDER BY a.data_agendamento, a.horario
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_trilheiro]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // REMOVIDO: O loop para buscar imagens para cada agendamento
    // O array $agendamentos já está populado diretamente com os dados da trilha/agendamento.

} catch (PDOException $e) {
    // Em caso de erro, você pode registrar ou mostrar uma mensagem
    $erro_db = "Erro ao carregar seus agendamentos.";
}

// 3. Verifica se o usuário é um administrador (para links da topbar)
$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;

?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="description" content="Página de gerenciamento de agendamentos do usuário no CAPIADVENTURE."/>
  <title>CAPIADVENTURE - Meus Agendamentos</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:wght@100..900&display=swap" rel="stylesheet">
  <style>
        /* Aplicando a fonte Roboto Flex globalmente */
        body {
            font-family: 'Roboto Flex', sans-serif;
        }
        
        /* Configuração da Logo com Sombra Preta (replicado para consistência) */
        /* Mantido aqui para garantir que o estilo da logo com sombra não seja sobreposto pelo style.css */
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

        /* Removendo sublinhado dos links ao passar o mouse */
        .top-link:hover {
            text-decoration: none; 
            transform: none !important; 
            transition: none !important; 
        }
        
        /* Removendo movimento e transição de TODOS os elementos com a classe .btn ao passar o mouse */
        .btn:hover {
            text-decoration: none;
            transform: none !important; 
            transition: none !important;
        }
        
        /* Estilo da caixa principal */
        .agendamentos-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Título principal: Meus Agendamentos */
        h2#agendamentos-heading {
            color: rgba(0, 0, 0, 0.5) !important; 
            text-align: center; 
            margin-bottom: 25px;
        }
        
        /* 💡 BLOCO DE ESTILOS DE CARD E CARROSSEL COPIADO DE TRILHAS.PHP PARA CONSISTÊNCIA 💡 */
        
        /* --- Layout de Cards de Trilhas/Agendamentos --- */

        /* ATUALIZADO: Remove as propriedades flexbox para que as trilhas fiquem uma abaixo da outra */
        .trail-container {
            display: block; /* Garante que o container use o fluxo normal de bloco */
            gap: 20px;
            padding: 20px;
        }

        /* Ajuste no item da trilha para que ocupe a largura total do container (uma abaixo da outra) */
        .trail-item {
            max-width: 800px; /* Limita a largura do cartão para não esticar demais em telas grandes */
            margin: 0 auto 20px auto; /* Centraliza o cartão e adiciona espaçamento inferior */
            
            /* ESTILOS DE CARD: Fundo, borda, raio e sombra */
            background-color: #fff;
            border: 1px solid #eee; 
            border-radius: 8px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
            padding: 0; 
            overflow: hidden; 
        }

        /* Centraliza todo o conteúdo textual e os elementos em linha/bloco dentro do box de informações */
        .trail-info {
            text-align: center;
            padding: 15px; /* Adiciona padding para o conteúdo */
        }

        /* REMOVIDO: Estilos de Carrossel (carousel-container, carousel-item, carousel-button) */


        /* ESTILO DO RODAPÉ (PRETO SÓLIDO 100% - COPIADO DE TRILHAS.PHP) */
        .footer {
            background-color: black; /* Cor PRETA SÓLIDA (100%) */
            color: white; 
            text-align: center; 
            padding: 15px 0;
            width: 100%;
        }
        /* 💡 FIM DO BLOCO DE ESTILOS DE CARD 💡 */


        /* Estilos específicos de meus_agendamentos.php */
        
        /* NOVO ESTILO: Reduz o padding do bloco cinza 'Ver Fotos' */
        .foto-preview-block {
            background-color: #f0f0f0; 
            padding: 20px; /* Reduz o padding vertical */
            text-align: center;
        }
        
        .trail-info h3 {
             color: rgba(0, 0, 0, 0.5) !important; 
             margin-top: 5px;
             margin-bottom: 10px;
        }
        
        .trail-info p {
            margin-bottom: 5px; /* Reduz espaço entre parágrafos de info */
        }
        
        .agendamento-status {
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            display: inline-block;
            margin-bottom: 5px;
            margin-top: 5px;
        }

        .status-Agendado {
            background-color: #d4edda;
            color: #155724;
        }

        .status-Em-análise {
            background-color: #fff3cd;
            color: #856404;
        }

        /* Estilos dos botões (links de ação) - Base */
        .btn-editar, .btn-cancelar, .btn-ver-fotos { 
            text-decoration: none !important; 
            border-radius: 5px;
            font-weight: bold; 
            font-size: 1.15em; 
            padding: 10px 15px; /* Mantido para Editar/Cancelar */
            margin: 5px;
            transition: background-color 0.3s;
            display: inline-block;
            border: none; 
            transform: none !important;
            box-shadow: none !important;
        }

        .btn-editar { /* Manter Editar na cor original (azul-água) */
             background-color: #34B5CC; 
             color: white;
        }

        .btn-editar:hover {
            background-color: #278DA0; 
        }

        .btn-ver-fotos { 
             /* Cor revertida para Cinza */
             background-color: rgba(0, 0, 0, 0.04); 
             color: rgba(0, 0, 0, 0.7);
             
             /* Tamanho e Formato mantidos do link-pequeno */
             padding: 8px 15px;      /* Tamanho reduzido (menor que Editar/Cancelar) */
             font-size: 0.9em;       /* Tamanho da fonte reduzido */
             font-weight: 500;       /* Peso da fonte igualado */
        }

        .btn-ver-fotos:hover {
            /* Hover Cinza mais escuro */
            background-color: rgba(0, 0, 0, 0.1); 
        }

        .btn-cancelar {
            background-color: #dc3545;
            color: white;
        }

        .btn-cancelar:hover {
            background-color: #c82333;
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

    <nav class="nav-links" aria-label="Opções de usuário">
        <ul>
            <?php if ($is_admin): ?>
            <li><a href="gerenciar_trilhas.php" class="top-link">Gerenciar Minhas Trilhas</a></li>
            <li><a href="gerenciar_agendamentos.php" class="top-link">Gerenciar Agendamentos</a></li>
            <?php endif; ?>
            
            <li><a href="trilhas.php" class="top-link">Trilhas</a></li>
            
            <li><a href="alterar_senha_logado.php" class="top-link">Alterar Senha</a></li>
            
            <li><a href="logout.php" class="top-link">Sair</a></li>
        </ul>
    </nav>
  </header>

  <main class="main">
    <section class="screen" id="tela_meus_agendamentos" aria-labelledby="agendamentos-heading">
      <div class="agendamentos-container">
        
        <h2 id="agendamentos-heading">
            Meus agendamentos
        </h2>
        
        <?php echo $mensagem_status; // Exibe mensagens de status ?>

        <?php if (isset($erro_db)): ?>
            <p style="color: red; text-align: center;"><?php echo $erro_db; ?></p>
        <?php elseif (empty($agendamentos)): ?>
            <div style="text-align: center; padding: 40px; border: 1px solid #eee; border-radius: 8px; background-color: #f9f9f9;">
                <p style="font-size: 1.1em; color: #555;">Você ainda não possui agendamentos ativos.</p>
                <p style="margin-top: 15px;">
                    <a href="trilhas.php" class="btn">Encontrar uma Trilha</a>
                </p>
            </div>
        <?php else: ?>
            <div class="trail-container"> <?php foreach ($agendamentos as $agendamento): ?>
                
                <div class="trail-item"> 
                    
                    <div class="foto-preview-block">
                        <a 
                            href="ver_fotos_trilha.php?id=<?php echo $agendamento['id_trilha']; ?>&origem=trilheiro" 
                            class="btn-ver-fotos" 
                        >
                            Ver fotos
                        </a>
                        </div>
                    <div class="trail-info"> <span class="agendamento-status status-<?php echo str_replace(' ', '-', $agendamento['status']); ?>">
                            <?php echo htmlspecialchars($agendamento['status']); ?>
                        </span>
                        
                        <p style="font-size: 0.85em; margin-bottom: 5px; color: rgba(0, 0, 0, 0.5);">TRILHA | <?php echo htmlspecialchars($agendamento['tipo_trilha']); ?></p>
                        <h3 style="color: rgba(0, 0, 0, 0.5);"><?php echo htmlspecialchars($agendamento['nome_trilha']); ?></h3>
                        
                        <p><strong style="color: rgba(0, 0, 0, 0.5);">Região:</strong> <?php echo htmlspecialchars($agendamento['regiao']); ?></p>
                        <p><strong style="color: rgba(0, 0, 0, 0.5);">Distância:</strong> <?php echo number_format($agendamento['distancia'], 1, ',', '.'); ?> km</p>
                        <p><strong style="color: rgba(0, 0, 0, 0.5);">Dificuldade:</strong> <span style="font-weight: bold; color: <?php echo ($agendamento['nivel_dificuldade'] == 'Alto' ? '#c00' : ($agendamento['nivel_dificuldade'] == 'Médio' ? '#e90' : '#090')); ?>;"><?php echo htmlspecialchars($agendamento['nivel_dificuldade']); ?></span></p>

                        <hr style="border: none; border-top: 1px solid #eee; margin: 15px auto;">
                        
                        <p style="margin: 0; font-size: 1.1em;"><strong style="color: #444;">DATA AGENDADA:</strong> <?php echo (new DateTime($agendamento['data_agendamento']))->format('d/m/Y'); ?></p>
                        <p style="margin: 0; font-size: 1.1em;"><strong style="color: #444;">HORÁRIO:</strong> <?php echo (new DateTime($agendamento['horario']))->format('H:i'); ?></p>

                        <?php if ($agendamento['status'] === 'Agendado'): ?>
                            <p style="font-size: 0.8em; color: #155724; margin-top: 10px;">Seu agendamento está confirmado! Prepare-se para a trilha.</p>
                        <?php elseif ($agendamento['status'] === 'Em análise'): ?>
                            <p style="font-size: 0.8em; color: #856404; margin-top: 10px;">Seu agendamento está em análise. Você pode editar ou cancelar.</p>
                        <?php endif; ?>
                            
                        <div style="margin-top: 15px;">
                            
                            <?php if ($agendamento['status'] === 'Em análise'): ?>
                                <a href="editar_agendamento.php?id=<?php echo $agendamento['id_agendamento']; ?>" class="btn-editar">
                                    Editar
                                </a>
                            <?php endif; ?>

                            <a href="processa_status_agendamento.php?id_agendamento=<?php echo $agendamento['id_agendamento']; ?>&novo_status=Cancelado&origem=meus" class="btn-cancelar" onclick="return confirm('Tem certeza que deseja Cancelar este agendamento?');">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer class="footer">
    <p><?php echo date('Y'); ?> CAPIADVENTURE. Desenvolvido por Leandro Rocha & Gabriel Gonçalves.</p>
  </footer>
  
  <script>
        // Não há mais lógica de carrossel nesta página.
    </script>
</body>

</html>
