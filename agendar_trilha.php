<?php
session_start();
require 'conexao.php';

// 1. VERIFICAÇÃO DE ACESSO: Deve ser usuário logado (trilheiro)
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_trilheiro'])) {
    header("Location: index.php?agendamento=logar");
    exit();
}

$id_trilha = $_GET['id'] ?? null;
$trilha = null;

if (!$id_trilha || !is_numeric($id_trilha)) {
    header("Location: index.php?erro=id_invalido");
    exit();
}

// 2. BUSCA DA TRILHA
try {
    $sql = "SELECT * FROM tb_trilhas WHERE id_trilha = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_trilha]);
    $trilha = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trilha) {
        header("Location: index.php?erro=trilha_nao_encontrada");
        exit();
    }

} catch (PDOException $e) {
    header("Location: index.php?erro=db");
    exit();
}

// Prepara variáveis para a tela
$logado = true;
$nome_display = htmlspecialchars(explode(' ', $_SESSION['nome_usuario'])[0]);
$mensagem_status = '';

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'sucesso') {
        // MENSAGEM ATUALIZADA: Status inicial é 'Em análise'
        $mensagem_status = '<p style="padding: 10px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;">Agendamento solicitado com sucesso! O status é Em análise e será revisado pelo administrador.</p>';
    } elseif ($_GET['status'] === 'erro' || $_GET['status'] === 'erro_db' || $_GET['status'] === 'erro_dados') {
        // MENSAGEM ATUALIZADA: Lida com o erro relatado pelo usuário (agora resolvido em processa_agendamento.php)
        $mensagem_status = '<p style="padding: 10px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px;">Erro ao solicitar agendamento. Tente novamente.</p>';
    } elseif ($_GET['status'] === 'invalido') {
        // MENSAGEM ATUALIZADA: Mensagem específica para erro de validação (Novo Requisito)
        $msg_detalhe = $_GET['msg'] ?? 'Data ou horário inválido para agendamento.';
        $mensagem_status = '<p style="padding: 10px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 20px;">Validação: ' . htmlspecialchars($msg_detalhe) . '</p>';
    }
}

// Variáveis para preencher os horários disponíveis
$dias_semana = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
$horarios_disponiveis = [];
foreach ($dias_semana as $dia) {
    $horarios_disponiveis[$dia] = [
        'entrada' => $trilha["hr_{$dia}_entrada"],
        'saida' => $trilha["hr_{$dia}_saida"]
    ];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="description" content="Agende sua aventura na trilha <?php echo htmlspecialchars($trilha['nome']); ?> com CAPIADVENTURE."/>
    <title>CAPIADVENTURE - Agendar <?php echo htmlspecialchars($trilha['nome']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* CSS GERAL PARA REMOVER MARGENS E GARANTIR LARGURA MÁXIMA */
        body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Topbar ocupa 100% e remove centralização */
        .topbar {
            width: 100%;
            display: flex;
            justify-content: space-between;
            max-width: none;
            margin: 0;
            padding: 10px 20px;
        }
        
        /* Configuração da Logo com Sombra Preta */
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
        /* FIM DA CONFIGURAÇÃO DA LOGO */

        /* Main ocupa 100% e remove centralização */
        .main {
            max-width: none;
            width: 100%;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        /* FIM DO AJUSTE DE LARGURA E CENTRALIZAÇÃO */

        /* ESTILO ESPECÍFICO PARA O FORMULÁRIO DE AGENDAMENTO (Card e Botões Padronizados) */
        /* AJUSTE FINAL: Novo max-width para o card central (500px) e CSS de editar_agendamento.php */
        .agendamento-card {
            max-width: 500px; /* Limita a largura do card para melhor visualização */
            margin: 40px auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            background-color: #fff;
            text-align: center;
        }
        
        /* Ajuste de cor e opacidade para o título h2 */
        .agendamento-card h2 { 
            margin-top: 0; 
            color: rgba(0, 0, 0, 0.5); /* Cor preta com 50% de opacidade */
            font-size: 1.8em; 
            margin-bottom: 20px; 
        }

        .agendamento-card h3 {
             color: #555;
             margin-top: 25px;
             margin-bottom: 15px;
             font-size: 1.2em;
        }

        /* Removido o label para usar apenas placeholder, mas mantido o CSS para input */
        /* .agendamento-card label {
            display: block;
            text-align: left;
            margin-top: 15px;
            font-weight: bold;
            color: #666;
        } */

        .agendamento-card input[type="date"],
        .agendamento-card input[type="time"] {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        /* Estilos dos horários disponíveis */
        .horarios-list {
            list-style: none; 
            padding: 0; 
            margin-bottom: 30px; 
            font-size: 0.95em;
            text-align: left;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            background-color: #fafafa;
        }

        .horarios-list li {
            padding: 5px 0;
            border-bottom: 1px dashed #eee;
        }

        .horarios-list li:last-child {
            border-bottom: none;
        }
        
        /* Estilos para o grupo de botões */
        .btn-group { display: flex; justify-content: space-between; gap: 10px; margin-top: 20px; }

        /* Estilos dos links e botões para remover sublinhado e movimento no hover */
        .top-link:hover {
            text-decoration: none; 
            transform: none !important; 
            transition: none !important; 
        }

        /* Estilos dos botões (links de ação) - Base */
        .btn-editar, .btn-cancelar { 
            text-decoration: none !important; 
            border-radius: 5px;
            font-weight: bold; 
            font-size: 1.15em; 
            padding: 12px 15px; /* AJUSTADO PARA CORRESPONDER À ALTURA DO BOTÃO DE LOGIN */
            margin: 0; 
            transition: background-color 0.3s;
            display: inline-block;
            border: none; 
            transform: none !important; 
            box-shadow: none !important;
            width: 50%; 
            text-align: center;
            cursor: pointer;
        }
        
        /* Garante que o hover dos botões também não tenha movimento */
        .btn-editar:hover, .btn-cancelar:hover {
            transform: none !important; 
            transition: background-color 0.3s; 
        }

        /* COR E HOVER DE EDITAR/SOLICITAR AGENDAMENTO (Azul-Água) */
        /* Usando a classe btn-editar para o botão de solicitar agendamento */
        .btn-editar {
             background-color: #34B5CC; 
             color: white;
        }

        .btn-editar:hover {
            background-color: #278DA0; 
        }

        /* COR E HOVER DE CANCELAR (Vermelho) */
        .btn-cancelar {
            background-color: #dc3545;
            color: white;
        }

        .btn-cancelar:hover {
            background-color: #c82333;
        }

        /* ESTILO DO RODAPÉ (Template azul) */
        .footer {
            background-color: black; 
            color: white; 
            text-align: center; 
            padding: 15px 0;
            width: 100%;
            position: fixed; 
            bottom: 0;
            left: 0;
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

        <nav class="nav-links" aria-label="Navegação Principal">
            <ul>
                <?php if ($logado): ?>
                    <li><a href="trilhas.php" class="top-link">Trilhas</a></li>
                    <li><a href="meus_agendamentos.php" class="top-link">Meus Agendamentos</a></li>
                    <li><a href="alterar_senha_logado.php" class="top-link">Alterar senha</a></li>
                    <li><a href="logout.php" class="top-link">Sair</a></li>
                <?php else: ?>
                    <li><a href="login.html" class="top-link">Login</a></li>
                    <li><a href="cadastro.html" class="top-link">Cadastro</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="main">
        <section class="agendamento" aria-labelledby="agendamento-section-title">
            <div class="agendamento-card">
                
                <h2 id="agendamento-section-title">Agendar Trilha: <strong><?php echo htmlspecialchars($trilha['nome']); ?></strong></h2>

                <?php echo $mensagem_status; ?>

                <p style="text-align: center; color: #777;">Preencha a data e hora desejadas para solicitar seu agendamento.</p>

                <h3>Horários de Funcionamento da Trilha</h3>
                <ul class="horarios-list">
                    <?php 
                    $nomes_dias = ['seg' => 'Segunda-feira', 'ter' => 'Terça-feira', 'qua' => 'Quarta-feira', 'qui' => 'Quinta-feira', 'sex' => 'Sexta-feira', 'sab' => 'Sábado', 'dom' => 'Domingo'];
                    foreach ($horarios_disponiveis as $dia => $horario): 
                        $entrada = $horario['entrada'];
                        $saida = $horario['saida'];
                        // Removido o negrito (**) da saída e do status para texto simples
                        $status = ($entrada && $saida) ? "Aberto das {$entrada} às {$saida}" : "Fechado";
                        $estilo = ($entrada && $saida) ? "color: #388e3c; font-weight: 500;" : "color: #d32f2f; font-weight: bold;";
                    ?>
                        <li style="font-size: 1.0em; <?php echo $estilo; ?>">
                            <?php echo $nomes_dias[$dia]; ?>: <?php echo $status; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <form action="processa_agendamento.php" method="POST">
                    <input type="hidden" name="id_trilha" value="<?php echo htmlspecialchars($id_trilha); ?>">
                    
                    <input id="data_agendamento" type="date" name="data_agendamento" placeholder="Data do Agendamento" required>

                    <input id="hora_agendamento" type="time" name="hora_agendamento" placeholder="Hora do Agendamento" required>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn-editar">
                            Agendar
                        </button>
                        <a href="trilhas.php" class="btn-cancelar">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <p><?php echo date('Y'); ?> 2025 CAPIADVENTURE. Desenvolvido por Leandro Rocha & Gabriel Gonçalves.</p>
    </footer>
</body>
</html>