<?php
session_start();
require 'conexao.php';

// 1. VERIFICAÇÃO DE ACESSO
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_trilheiro'])) {
    header("Location: login.html?acesso=negado");
    exit();
}

$id_agendamento = $_GET['id'] ?? null;
$id_trilheiro = $_SESSION['id_trilheiro'];
$trilha = null;
$agendamento = null;

if (!$id_agendamento || !is_numeric($id_agendamento)) {
    header("Location: meus_agendamentos.php?status=erro_edicao");
    exit();
}

// 2. BUSCA DO AGENDAMENTO E DA TRILHA ASSOCIADA (Verifica o status 'Em análise' no banco)
try {
    $sql = "
        SELECT 
            a.id_agendamento, a.data_agendamento, a.horario, a.status,
            t.*
        FROM tb_agendamento a
        JOIN tb_trilhas t ON a.id_trilha = t.id_trilha
        WHERE a.id_agendamento = ? AND a.id_trilheiro = ? AND a.status = 'Em análise'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_agendamento, $id_trilheiro]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agendamento) {
        // Redireciona se não encontrar ou se o status não for 'Em análise' (segurança de backend)
        header("Location: meus_agendamentos.php?status=erro_edicao&msg=NaoEditavel");
        exit();
    }
    
    // Armazena os dados da trilha para exibir os horários de funcionamento
    $trilha = $agendamento; 

} catch (PDOException $e) {
    header("Location: meus_agendamentos.php?status=erro_edicao&msg=db");
    exit();
}

// Prepara variáveis para a tela
$logado = true;
$nome_display = htmlspecialchars(explode(' ', $_SESSION['nome_usuario'])[0]);
$mensagem_status = '';

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'invalido') {
        // Mensagem específica para erro de validação (Data, Horário, etc.)
        $msg_detalhe = $_GET['msg'] ?? 'Data ou horário inválido para agendamento.';
        $mensagem_status = '<p style="padding: 10px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 20px;">⚠️ Validação: ' . htmlspecialchars($msg_detalhe) . '</p>';
    }
}

// Variável para preencher os horários disponíveis (mantido do arquivo original)
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
    <meta name="description" content="Edite seu agendamento na trilha <?php echo htmlspecialchars($trilha['nome']); ?>."/>
    <title>CAPIADVENTURE - Editar Agendamento</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* [ ... Estilos existentes ... ] */
        body { margin: 0; padding: 0; box-sizing: border-box; }
        /* CORREÇÃO MANTIDA: Garante largura 100% para o template */
        .topbar { width: 100%; display: flex; justify-content: space-between; padding: 10px 20px; }
        .brand .logo { width: 80px; height: 80px; background-color: transparent; border-radius: 50%; box-shadow: 0 0 15px rgba(0, 0, 0, 0.7); }
        .brand .logo img { width: 100%; height: 100%; object-fit: contain; }
        /* CORREÇÃO MANTIDA: Garante largura 100% para o template */
        .main { width: 100%; padding: 20px; box-sizing: border-box; }
        
        /* AJUSTE FINAL: Novo max-width para o card central (500px) */
        .agendamento-card { max-width: 500px; margin: 40px auto; padding: 30px; border: 1px solid #ddd; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.15); background-color: #fff; text-align: center; }
        
        /* Ajuste de cor e opacidade para o título h2 */
        .agendamento-card h2 { 
            margin-top: 0; 
            color: rgba(0, 0, 0, 0.5); /* Cor preta com 50% de opacidade */
            font-size: 1.8em; 
            margin-bottom: 20px; 
        }
        
        .agendamento-card h3 { color: #555; margin-top: 25px; margin-bottom: 15px; font-size: 1.2em; }
        .agendamento-card input[type="date"],
        .agendamento-card input[type="time"] { width: 100%; padding: 12px; margin-top: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .horarios-list { list-style: none; padding: 0; margin-bottom: 30px; font-size: 0.95em; text-align: left; border: 1px solid #eee; border-radius: 8px; padding: 15px; background-color: #fafafa; }
        .horarios-list li { padding: 5px 0; border-bottom: 1px dashed #eee; }
        .horarios-list li:last-child { border-bottom: none; }
        
        /* Estilos para o grupo de botões (LADO A LADO) */
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
            font-size: 1.15em; /* FONTE PADRONIZADA (Mesma de 'Entrar') */
            padding: 12px 15px; 
            margin: 0; 
            transition: background-color 0.3s;
            display: inline-block; /* Garante que sejam flex-items */
            border: none; 
            transform: none !important; 
            box-shadow: none !important;
            width: 50%; /* Faz com que cada um ocupe metade (lado a lado) */
            text-align: center;
            cursor: pointer;
            margin-bottom: 0;
        }
        
        /* Garante que o hover dos botões também não tenha movimento */
        .btn-editar:hover, .btn-cancelar:hover {
            transform: none !important; 
            transition: background-color 0.3s; 
        }

        /* COR E HOVER DE EDITAR/SALVAR EDIÇÃO (Azul-Água) */
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
                
                <h2 id="agendamento-section-title">
                    Editar agendamento: <strong><?php echo htmlspecialchars($trilha['nome']); ?></strong>
                </h2>
                <p style="text-align: center; color: #777; font-weight: bold;">Agendamento atual: <?php echo (new DateTime($agendamento['data_agendamento']))->format('d/m/Y'); ?> às <?php echo (new DateTime($agendamento['horario']))->format('H:i'); ?></p>

                <?php echo $mensagem_status; // Exibe mensagens de status (erro de validação, etc.) ?>

                <p style="text-align: center; color: #777;">Altere a data e hora desejadas. O novo agendamento permanecerá em Em análise.</p>

                <h3>Horários de Funcionamento da Trilha</h3>
                <ul class="horarios-list">
                    <?php 
                    $nomes_dias = ['seg' => 'Segunda-feira', 'ter' => 'Terça-feira', 'qua' => 'Quarta-feira', 'qui' => 'Quinta-feira', 'sex' => 'Sexta-feira', 'sab' => 'Sábado', 'dom' => 'Domingo'];
                    foreach ($horarios_disponiveis as $dia => $horario): 
                        $entrada = $horario['entrada'];
                        $saida = $horario['saida'];
                        $status = ($entrada && $saida) ? "Aberto das {$entrada} às {$saida}" : "Fechado";
                        $estilo = ($entrada && $saida) ? "color: #388e3c; font-weight: 500;" : "color: #d32f2f; font-weight: bold;";
                    ?>
                        <li style="font-size: 1.0em; <?php echo $estilo; ?>">
                            <?php echo $nomes_dias[$dia]; ?>: <?php echo $status; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <form action="processa_edicao_agendamento.php" method="POST">
                    <input type="hidden" name="id_agendamento" value="<?php echo htmlspecialchars($id_agendamento); ?>">
                    
                    <input id="data_agendamento" type="date" name="data_agendamento" 
                           value="<?php echo htmlspecialchars($agendamento['data_agendamento']); ?>" required>

                    <input id="hora_agendamento" type="time" name="hora_agendamento" 
                           value="<?php echo htmlspecialchars((new DateTime($agendamento['horario']))->format('H:i')); ?>" required>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn-editar">
                            Salvar
                        </button>
                        <a href="meus_agendamentos.php" class="btn-cancelar">
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