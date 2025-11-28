<?php
require 'conexao.php'; 
session_start();

// 1. VERIFICAÇÃO DE ACESSO: Deve ser ADMIN logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true || !isset($_SESSION['id_administrador'])) {
    header("Location: login.html?acesso=negado"); 
    exit();
}

$id_administrador = $_SESSION['id_administrador'];
$nome_display = htmlspecialchars(explode(' ', $_SESSION['nome_usuario'])[0]);

// Variáveis para mensagem de status
$mensagem_status = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'cadastrada') {
        $mensagem_status = '<p class="status-message success">Trilha cadastrada com sucesso!</p>';
    } elseif ($_GET['status'] === 'editada') {
        $mensagem_status = '<p class="status-message success">Trilha atualizada com sucesso!</p>';
    } elseif ($_GET['status'] === 'excluida') {
        $mensagem_status = '<p class="status-message success">Trilha excluída com sucesso!</p>';
    } elseif ($_GET['status'] === 'erro') {
         $mensagem_status = '<p class="status-message error">Ocorreu um erro na operação. Tente novamente.</p>';
    }
}

$trilhas = [];
try {
    // Busca todas as trilhas cadastradas POR ESTE ADMINISTRADOR
    $sql = "SELECT id_trilha, nome, descricao, regiao, distancia, nivel_dificuldade, tipo_trilha FROM tb_trilhas WHERE id_administrador = :id_admin ORDER BY id_trilha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_admin', $id_administrador, PDO::PARAM_INT);
    $stmt->execute();
    $trilhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Opcional: error_log("Erro ao buscar trilhas para admin: " . $e->getMessage());
    $mensagem_status .= '<p class="status-message error">Erro ao carregar as trilhas.</p>';
}

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="description" content="Página de gerenciamento de trilhas para administradores."/>
    <title>CAPIADVENTURE - Gerenciar Trilhas</title>
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* Estilos específicos para esta página */
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
            margin-top: 30px;
        }
        /* Container principal */
        .trilhas-container {
            display: flex;
            flex-direction: column; 
            align-items: center; 
            padding: 20px;
            gap: 20px; 
        }
        /* Card base (Contém o botão, info e ações) */
        .card-content-base {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden; 
            background-color: white;
            border: 1px solid #ddd;
        }

        /* Wrapper para o botão "Ver Fotos" */
        .foto-preview-block {
            background-color: #f0f0f0; 
            padding: 20px; 
            text-align: center;
            border-top-left-radius: 8px; 
            border-top-right-radius: 8px;
        }

        /* Estilo do Botão "Ver Fotos" */
        .btn-ver-fotos {
            background-color: rgba(0, 0, 0, 0.04); 
            color: rgba(0, 0, 0, 0.7);
            padding: 8px 15px;      
            font-size: 0.9em;       
            font-weight: 500;       
            text-decoration: none !important; /* Remove sublinhado */
            border-radius: 5px; 
            transition: background-color 0.3s;
            display: inline-block; 
            border: none;
            width: auto; 
            margin: 0;
        }

        .btn-ver-fotos:hover {
            background-color: rgba(0, 0, 0, 0.1); 
            text-decoration: none; /* Remove sublinhado ao passar o mouse */
        }

        /* Área de Info (Centralizada) */
        .trail-info {
            padding: 20px;
            text-align: center; 
            flex-grow: 1; 
        }
        
        /* Título - sem margin-top para encostar no botão */
        .trail-info h3 {
            margin-top: 0; 
            color: #333;
        }
        .trail-info p {
            margin: 5px 0;
            color: #555;
        }
        /* Área de Ações (Botões de Editar/Excluir) - CENTRALIZADA */
        .trail-actions {
            padding: 0 20px 20px;
            display: flex;
            gap: 10px;
            justify-content: center; 
        }

        /* Estilo dos botões de Ação */
        .btn-acao {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none; /* Remove sublinhado */
            font-weight: bold;
            text-align: center;
            transition: background-color 0.3s;
        }

        .btn-editar {
            background-color: #ffc107; 
            color: #212529;
        }
        .btn-editar:hover {
            background-color: #e0a800;
            text-decoration: none; /* Remove sublinhado ao passar o mouse */
        }

        .btn-excluir {
            background-color: #dc3545; 
            color: white;
        }
        .btn-excluir:hover {
            background-color: #c82333;
            text-decoration: none; /* Remove sublinhado ao passar o mouse */
        }

        /* ESTILO ALTERADO: display: inline-block para caber no conteúdo */
        .btn-adicionar {
            margin: 20px auto;
            display: inline-block; 
            /* width: 100%; removido */
            max-width: 800px;
            background-color: #34B5CC;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 1.1em;
            font-weight: bold;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn-adicionar:hover {
            background-color: #2C9CAD;
            text-decoration: none; 
        }

        /* Estilo para mensagens de status */
        .status-message {
            padding: 10px;
            margin: 15px auto;
            border-radius: 4px;
            max-width: 800px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsividade básica */
        @media (max-width: 650px) {
            .trail-actions {
                flex-direction: column;
                gap: 5px;
            }
            /* O botão adicionar agora tem largura automática, mas em telas pequenas pode ser útil forçar 100% */
            /* Removendo a regra de .btn-adicionar em mobile para que o inline-block funcione, mas mantendo o btn-acao */
            .btn-acao {
                width: 100%;
            }
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
                <li><a href="gerenciar_agendamentos.php" class="top-link">Gerenciar Agendamentos</a></li>
                <li><a href="meus_agendamentos.php" class="top-link">Meus Agendamentos</a></li>
                <li><a href="trilhas.php" class="top-link">Trilhas</a></li> 
                <li><a href="alterar_senha_logado.php" class="top-link">Alterar Senha</a></li>
                <li><a href="logout.php" class="top-link">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main class="main">
        <section class="screen" id="tela_gerenciar_trilhas" aria-labelledby="trilhas-heading">
            <h2 id="trilhas-heading" style="text-align: center; padding: 20px 20px 0 20px; color: rgba(0, 0, 0, 0.5); margin-bottom: 15px;">
               Aqui é seu espaço para gerenciar suas trilhas
            </h2>
            
            <?php echo $mensagem_status; // Exibe mensagens de status ?>

            <div style="text-align: center;">
                <a href="cadastro_trilha.php" class="btn-adicionar">
                    Adicionar nova tilha
                </a>
            </div>
            
            <?php if (empty($trilhas)): ?>
                <p style="text-align: center; margin-top: 20px; color: #777;">Você ainda não cadastrou nenhuma trilha.</p>
            <?php else: ?>
                <div class="trilhas-container">
                    <?php foreach ($trilhas as $trilha): ?>
                        <div class="card-content-base">
                            
                            <div class="foto-preview-block">
                                <a 
                                    href="ver_fotos_trilha.php?id=<?php echo $trilha['id_trilha']; ?>&origem=admin" 
                                    class="btn-ver-fotos"
                                >
                                    Ver fotos
                                </a>
                            </div>
                            
                            <div class="trail-info">
                                
                                <h3><?php echo htmlspecialchars($trilha['nome']); ?></h3>
                                <p><strong>Região:</strong> <?php echo htmlspecialchars($trilha['regiao']); ?></p>
                                <p><strong>Distância:</strong> <?php echo number_format($trilha['distancia'], 1, ',', '.'); ?> km</p>
                                <p><strong>Dificuldade:</strong> <?php echo htmlspecialchars($trilha['nivel_dificuldade']); ?></p>
                                <p><strong>Tipo:</strong> <?php echo htmlspecialchars($trilha['tipo_trilha']); ?></p>
                            </div>
                            
                            <div class="trail-actions">
                                                                
                                <a href="editar_trilha.php?id=<?php echo $trilha['id_trilha']; ?>" class="btn-acao btn-editar">
                                    Editar
                                </a>
                                
                                <a href="processa_exclusao_trilha.php?id=<?php echo $trilha['id_trilha']; ?>" class="btn-acao btn-excluir" onclick="return confirm('ATENÇÃO: A exclusão da trilha é irreversível e removerá todos os agendamentos relacionados. Tem certeza que deseja EXCLUIR a trilha <?php echo htmlspecialchars($trilha['nome']); ?>?');">
                                    Excluir
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="footer">
        <p><?php echo date('Y'); ?> CAPIADVENTURE. Desenvolvido por Leandro Rocha & Gabriel Gonçalves.</p>
    </footer>
</body>

</html>
