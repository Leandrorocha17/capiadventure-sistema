<?php
require 'conexao.php';
session_start();

// 1. VERIFICAÇÃO DE ACESSO: Deve ser ADMIN logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.html?acesso=negado");
    exit();
}

$nome_display = htmlspecialchars(explode(' ', $_SESSION['nome_usuario'])[0]);
$agendamentos = [];
$historico = [];

// Variáveis para mensagem de status
$mensagem_status = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'agendado') {
        $mensagem_status = 'Agendamento Confirmado com sucesso! Foi movido para o Histórico.';
    } elseif ($_GET['status'] === 'cancelado') {
        $mensagem_status = 'Agendamento Cancelado com sucesso!';
    } elseif ($_GET['status'] === 'erro') {
        $mensagem_status = 'Erro ao atualizar o status do agendamento.';
    } elseif ($_GET['status'] === 'ok_sucesso') {
        $mensagem_status = 'Agendamento movido para o Histórico de Gerenciamento.';
    }
}

// 2. BUSCA TODOS OS AGENDAMENTOS (Em análise)
try {
    // Busca agendamentos com status 'Em análise'
    $sql_ativos = "SELECT a.*, t.nome as nome_trilha, tr.nome as nome_trilheiro, tr.email as email_trilheiro
            FROM tb_agendamento a
            JOIN tb_trilhas t ON a.id_trilha = t.id_trilha
            JOIN tb_trilheiro tr ON a.id_trilheiro = tr.id_trilheiro
            WHERE a.status = 'Em análise'
            ORDER BY a.data_agendamento ASC, a.horario ASC";
            
    $stmt_ativos = $pdo->query($sql_ativos);
    $agendamentos = $stmt_ativos->fetchAll(PDO::FETCH_ASSOC);

    // 3. BUSCA HISTÓRICO (Agendados e Cancelados)
    $sql_historico = "SELECT a.*, t.nome as nome_trilha, tr.nome as nome_trilheiro, tr.email as email_trilheiro
            FROM tb_agendamento a
            JOIN tb_trilhas t ON a.id_trilha = t.id_trilha
            JOIN tb_trilheiro tr ON a.id_trilheiro = tr.id_trilheiro
            WHERE a.status IN ('Agendado', 'Cancelado')
            ORDER BY a.data_agendamento DESC, a.horario DESC";
            
    $stmt_historico = $pdo->query($sql_historico);
    $historico = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // die("Erro ao buscar agendamentos: " . $e->getMessage()); 
}

?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CAPIADVENTURE - Gerenciar Agendamentos</title>
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

    /* Estilos básicos inline (adapte para o seu style.css) */
    .agendamento-card {
        border: 1px solid #ddd;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        /* Manter fundo branco para o card individual */
        background-color: #fff; 
        /* Alinhar o conteúdo do card individual à esquerda */
        text-align: left; 
        /* Garantir que o card ocupe o espaço máximo definido */
        margin-left: auto; 
        margin-right: auto;
        max-width: 600px; /* Limite de largura para o card */
    }
    .status-em-análise { color: gray; font-weight: bold; }
    .status-agendado { color: #388e3c; font-weight: bold; }
    .status-cancelado { color: #d32f2f; font-weight: bold; }
    
    .acoes button, .acoes a.btn {
        margin-right: 10px;
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        color: white;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
    }
    .btn-agendar { background-color: #4CAF50; }
    .btn-cancelar { background-color: #f44336; }
    .btn-ok { background-color: #6c757d; } /* Cinza para o botão OK */
    
    .historico-card {
        border-left: 5px solid #ccc;
        opacity: 0.8;
    }

    /* ESTILO DO RODAPÉ (PRETO SÓLIDO 100% - COPIADO DO index.html) */
    .footer {
        background-color: black; /* Cor PRETA SÓLIDA (100%) */
        color: white; 
        text-align: center; 
        padding: 15px 0;
        width: 100%;
        position: fixed; /* Fixa o rodapé */
        bottom: 0;
        left: 0;
    }

    /* 🆕 NOVO: Centraliza o texto e cabeçalhos da seção */
    #tela_gerenciar_agendamentos {
        text-align: center;
    }

  </style>
</head>
<body>
  <header class="topbar">
    <div class="brand">
      <div class="logo">
        <img src="capivara.png" alt="Logotipo CAPIADVENTURE">
      </div>
      <div class="title">CAPIADVENTURE</div>
    </div>
    
    <nav class="nav-links" aria-label="Opções de administração">
        <ul>
            <li><a href="gerenciar_trilhas.php" class="top-link">Gerenciar Minhas Trilhas</a></li>
            <li><a href="meus_agendamentos.php" class="top-link">Meus Agendamentos</a></li>
            <li><a href="trilhas.php" class="top-link">Trilhas</a></li>
            <li><a href="alterar_senha_logado.php" class="top-link">Alterar Senha</a></li>
            <li><a href="logout.php" class="top-link">Sair</a></li>
        </ul>
    </nav>
  </header>

  <main class="main">
    <section class="screen" id="tela_gerenciar_agendamentos" aria-labelledby="gerenciamento-heading">
      <h2 id="gerenciamento-heading" style="padding-top: 40px; margin-top: 20px; margin-bottom: 20px;">Gerenciar Agendamentos - Olá, <?php echo $nome_display; ?></h2> 
        
        <?php if ($mensagem_status): ?>
            <p style="padding: 10px; margin: 15px auto; border-radius: 4px; background-color: #ddf; color: #00a; border: 1px solid #aaf; max-width: 90%; width: 600px;">
                <?php echo $mensagem_status; ?>
            </p>
        <?php endif; ?>

        <h3>Agendamentos em Análise (<?php echo count($agendamentos); ?>)</h3>
        
        <?php if (empty($agendamentos)): ?>
            <p class="lead">Não há agendamentos em análise no momento.</p>
        <?php else: ?>
            <div class="agendamentos-container" style="max-width: 900px; width: 90%; margin: 20px auto;">
            <?php foreach ($agendamentos as $agendamento): ?>
                <?php
                $status_class = strtolower(str_replace(' ', '-', $agendamento['status']));
                ?>
                <div class="agendamento-card">
                    <h3>Trilha: <?php echo htmlspecialchars($agendamento['nome_trilha']); ?></h3>
                    <p><strong>Trilheiro:</strong> <?php echo htmlspecialchars($agendamento['nome_trilheiro']); ?> (<?php echo htmlspecialchars($agendamento['email_trilheiro']); ?>)</p>
                    <p><strong>Data/Hora:</strong> <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?> às <?php echo date('H:i', strtotime($agendamento['horario'])); ?></p>
                    <p><strong>Status Atual:</strong> <span class="status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($agendamento['status']); ?></span></p>
                    
                    <div class="acoes" style="margin-top: 15px;">
                        <form action="processa_status_agendamento.php" method="POST" style="display: inline;">
                            <input type="hidden" name="id_agendamento" value="<?php echo $agendamento['id_agendamento']; ?>">
                            <input type="hidden" name="novo_status" value="Agendado">
                            <button type="submit" class="btn-agendar">Agendar (Confirmar)</button>
                        </form>
                        <form action="processa_status_agendamento.php" method="POST" style="display: inline;">
                            <input type="hidden" name="id_agendamento" value="<?php echo $agendamento['id_agendamento']; ?>">
                            <input type="hidden" name="novo_status" value="Cancelado">
                            <button type="submit" class="btn-cancelar" onclick="return confirm('Tem certeza que deseja Cancelar este agendamento?');">Cancelar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <hr style="margin: 40px auto; max-width: 900px;">

        <h3>Histórico (Agendados e Cancelados) (<?php echo count($historico); ?>)</h3>
        
        <?php if (empty($historico)): ?>
            <p class="lead">O histórico de agendamentos está vazio.</p>
        <?php else: ?>
            <div class="agendamentos-container" style="max-width: 900px; width: 90%; margin: 20px auto;">
            <?php foreach ($historico as $agendamento): ?>
                <?php
                $status_class = strtolower(str_replace(' ', '-', $agendamento['status']));
                ?>
                <div class="agendamento-card historico-card">
                    <h3>Trilha: <?php echo htmlspecialchars($agendamento['nome_trilha']); ?></h3>
                    <p><strong>Trilheiro:</strong> <?php echo htmlspecialchars($agendamento['nome_trilheiro']); ?> (<?php echo htmlspecialchars($agendamento['email_trilheiro']); ?>)</p>
                    <p><strong>Data/Hora:</strong> <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?> às <?php echo date('H:i', strtotime($agendamento['horario'])); ?></p>
                    <p><strong>Status Final:</strong> <span class="status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($agendamento['status']); ?></span></p>
                    <p style="font-size: 0.8em; color: #555;">Movido para o histórico em: <?php echo date('d/m/Y H:i', strtotime($agendamento['data_registro'])); ?></p>
                    
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </section>
  </main>
  
    <footer class="footer">
        <p><?php echo date('Y'); ?> 2025 CAPIADVENTURE. Desenvolvido por Leandro Rocha & Gabriel Gonçalves.</p>
    </footer>
</body>
</html>