<?php
session_start();
// 1. CONEXÃO COM O BANCO DE DADOS
require 'conexao.php'; // Inclui o arquivo que contém a conexão PDO

// Verifica se o usuário está logado
$logado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;

// VERIFICA SE O USUÁRIO LOGADO É ADMINISTRADOR (NOVA LÓGICA SIMPLIFICADA)
// Usamos a variável de sessão definida no processa_login.php.
$admin_logado = isset($_SESSION['admin']) && $_SESSION['admin'] === true;

// Variável para armazenar a trilha de destaque (a mais recente)
$destaque = null;
// Variável para armazenar todas as trilhas
$trilhas = [];

/**
 * Busca todas as imagens associadas a uma trilha.
 * Assume a existência da tabela 'tb_trilhas_imagens'.
 * @param PDO $pdo Conexão PDO.
 * @param int $id_trilha ID da trilha.
 * @return array Array de caminhos de imagem.
 */
function buscarImagensTrilha(PDO $pdo, $id_trilha) {
    // Busca até 10 imagens (limite do requisito)
    $sql = "SELECT caminho_imagem FROM tb_trilhas_imagens WHERE id_trilha = :id ORDER BY ordem ASC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id_trilha, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Retorna apenas a coluna 'caminho_imagem'
}

try {
    // 2. BUSCA A ÚLTIMA TRILHA CADASTRADA (PARA O DESTAQUE)
    $sql_destaque = "SELECT id_trilha, nome, descricao, regiao, distancia, nivel_dificuldade, tipo_trilha FROM tb_trilhas ORDER BY id_trilha DESC LIMIT 1";
    $stmt_destaque = $pdo->query($sql_destaque);
    $destaque = $stmt_destaque->fetch(PDO::FETCH_ASSOC);

    // Se a trilha de destaque foi encontrada, busca suas imagens
    if ($destaque) {
        $destaque['imagens'] = buscarImagensTrilha($pdo, $destaque['id_trilha']);
    }

    // 3. BUSCA TODAS AS TRILHAS CADASTRADAS
    $sql_todas = "SELECT id_trilha, nome, descricao, regiao, distancia, nivel_dificuldade, tipo_trilha FROM tb_trilhas ORDER BY id_trilha DESC";
    $stmt_todas = $pdo->query($sql_todas);
    $trilhas_raw = $stmt_todas->fetchAll(PDO::FETCH_ASSOC);

    // Itera e busca as imagens para cada trilha
    foreach ($trilhas_raw as $trilha) {
        // Verifica se a trilha atual é a trilha de destaque (para evitar duplicidade na listagem)
        if ($destaque && $trilha['id_trilha'] === $destaque['id_trilha']) {
            continue; // Pula a trilha de destaque, pois ela já foi listada acima
        }
        $trilha['imagens'] = buscarImagensTrilha($pdo, $trilha['id_trilha']);
        $trilhas[] = $trilha;
    }


} catch (PDOException $e) {
    // Em caso de erro, você pode registrar ou mostrar uma mensagem
    // error_log("Erro ao buscar trilhas: " . $e->getMessage());
    // $erro_db = "Erro ao carregar os dados das trilhas.";
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="description" content="Página inicial CAPIADVENTURE com destaque para trilhas."/>
    <title>CAPIADVENTURE - Trilhas</title>
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* Configuração da Logo com Sombra Preta (replicado para consistência) */
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

        /* Removendo sublinhado dos links ao passar o mouse (replicado para consistência) */
        .top-link:hover {
            text-decoration: none;
            transform: none !important; 
            transition: none !important; 
        }
        
        /* NOVO: Remove movimento e transição de TODOS os elementos com a classe .btn ao passar o mouse */
        .btn:hover {
            text-decoration: none;
            transform: none !important; 
            transition: none !important;
        }
        
        /* Centraliza todo o conteúdo textual e os elementos em linha/bloco dentro do box de informações */
        .trail-info {
            text-align: center;
        }
        
        /* NOVO ESTILO: Ajusta o tamanho da fonte e negrito APENAS para os links de agendamento (.btn na index) */
        .trail-info a.btn {
            font-size: 1.15em; /* Tamanho igual ao botão 'Entrar' */
            font-weight: bold; /* Adiciona negrito */
        }
        
        /* ESTILO DO RODAPÉ (PRETO SÓLIDO 100% - CONFORME SOLICITADO) */
        .footer {
            background-color: black; /* Cor PRETA SÓLIDA (100%) */
            color: white; 
            text-align: center; 
            padding: 15px 0;
            width: 100%;
        }

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
        }
        
        /* ** MODIFICAÇÃO CSS: Altura 350px e object-fit: cover (Modelo editar_trilha.php) ** */
        .carousel-container {
            position: relative; 
            overflow: hidden; 
            width: 100%;
            height: 350px; 
            object-fit: cover; 
            border-radius: 0; 
            background-color: #f0f0f0; 
        }
        
        /* NOVO ESTILO: Item do carrossel (a imagem em si) */
        .carousel-item {
            position: absolute; 
            top: 0;
            left: 0;
            opacity: 0; 
            transition: opacity 1s ease-in-out; 
            
            width: 100%;
            height: 100%;
            object-fit: cover; 
            border-radius: 0; 
            
            /* Novo: Garante que os itens fiquem visíveis para a transição de opacidade funcionar */
            display: block !important; 
        }
        
        /* NOVO ESTILO: Item ativo */
        .carousel-item.active {
            opacity: 1; 
        }
        
        /* Os itens inativos (opacity: 0) ainda ocupam espaço, 
           mas como estão em position: absolute, não afetam o layout */


        /* NOVO ESTILO: Setas de navegação */
        .carousel-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.5); 
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 24px;
            cursor: pointer;
            z-index: 10; 
            transition: background-color 0.3s ease;
            user-select: none; 
            opacity: 0; 
        }

        .carousel-button:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        .carousel-button.prev {
            left: 0;
            border-radius: 0 5px 5px 0; 
        }

        .carousel-button.next {
            right: 0;
            border-radius: 5px 0 0 5px; 
        }

        /* Faz as setas aparecerem ao passar o mouse sobre o container do carrossel */
        .carousel-container:hover .carousel-button {
            opacity: 1;
        }

        /* Estilo para as descrições que se expandem */
        .description-content {
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            max-height: 0; /* Começa fechado */
            padding: 0 15px; /* Não mostra padding quando fechado */
        }
        .description-content.expanded {
            max-height: 500px; /* Suficiente para expandir. Ajuste se necessário. */
            padding: 10px 15px 15px;
        }
        .description-content p {
            margin: 0;
            padding-bottom: 10px;
        }
        .description-content p:last-child {
            padding-bottom: 0;
        }
        
        /* Mensagem de Boas-vindas na index.php */
        .welcome-message {
            text-align: center;
            margin: 0 auto 30px auto; 
            max-width: 800px; 
            font-size: 1.1em;
            color: rgba(0, 0, 0, 0.7); 
            line-height: 1.6;
            padding: 0 15px;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">
            <div class="logo">
                <img src="Capivara.png" alt="Logotipo CAPIADVENTURE - Capivara mascote">
            </div>
            <div class="title">CAPIADVENTURE</div>
        </div>

        <nav class="nav-links" aria-label="Opções de usuário">
            <ul>
                <?php if ($logado): ?>
                    <?php if ($admin_logado): ?>
                        <li><a href="gerenciar_trilhas.php" class="top-link">Gerenciar Minhas Trilhas</a></li>
                        <li><a href="gerenciar_agendamentos.php" class="top-link">Gerenciar Agendamentos</a></li>
                    <?php endif; ?>
                    <li><a href="meus_agendamentos.php" class="top-link">Meus Agendamentos</a></li>
                    <li><a href="alterar_senha_logado.php" class="top-link">Alterar Senha</a></li>
                    <li><a href="logout.php" class="top-link">Sair</a></li>
                <?php else: ?>
                    <li><a href="login.html" class="top-link">Entrar</a></li>
                    <li><a href="cadastro.html" class="top-link">Cadastrar</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="main">
        <section class="screen" id="tela_trilhas" aria-labelledby="trilhas-heading">
            <h2 id="trilhas-heading" style="text-align: center; padding: 20px 20px 0 20px; color: rgba(0, 0, 0, 0.5); margin-bottom: 15px;">Bem - vindo ao CAPIADVENTURE</h2>
            
            <p class="welcome-message">
                Seu portal para divulgação e agendamento de trilhas no Distrito Federal e regiões do entorno.
                <br>
                Aqui, você pode escolher o dia e horário ideais para participar de trilhas públicas ou privadas e viver experiências inesquecíveis em meio à natureza.
            </p>

            <?php if ($destaque): ?>
                <?php 
                // Define a URL para o botão de agendamento Destaque
                $destaque_url = $logado ? "agendar_trilha.php?id={$destaque['id_trilha']}" : "login.html?acesso=negado";
                ?>
                <div class="destaque-card">
                    <h3 style="text-align: center; color: rgba(0, 0, 0, 0.5); margin-bottom: 15px;">Nova trilha</h3>
                    <div class="trail-item destaque-item">
                        
                        <div class="carousel-container destaque-img" data-id="<?php echo $destaque['id_trilha']; ?>">
                            <?php if (!empty($destaque['imagens'])): ?>
                                <?php foreach ($destaque['imagens'] as $index => $imagem_path): ?>
                                    <img 
                                        src="<?php echo htmlspecialchars($imagem_path); ?>" 
                                        alt="Foto <?php echo $index + 1; ?> da trilha <?php echo htmlspecialchars($destaque['nome']); ?>" 
                                        class="trail-img carousel-item <?php echo $index === 0 ? 'active' : ''; ?>"
                                    >
                                <?php endforeach; ?>
                                <button type="button" class="carousel-button prev">&lt;</button>
                                <button type="button" class="carousel-button next">&gt;</button>
                            <?php else: ?>
                                <p style="text-align: center;">Nenhuma imagem disponível.</p>
                            <?php endif; ?>
                        </div>
                        <div class="trail-info">
                            <p style="font-size: 0.85em; margin-bottom: 5px; color: rgba(0, 0, 0, 0.5);">TRILHA DESTAQUE | <?php echo htmlspecialchars($destaque['tipo_trilha']); ?></p>
                            <h3 style="color: rgba(0, 0, 0, 0.5);"><?php echo htmlspecialchars($destaque['nome']); ?></h3>
                            
                            <p><strong style="color: rgba(0, 0, 0, 0.5);">Região:</strong> <?php echo htmlspecialchars($destaque['regiao']); ?></p>
                            <p><strong style="color: rgba(0, 0, 0, 0.5);">Distância:</strong> <?php echo number_format($destaque['distancia'], 1, ',', '.'); ?> km</p>
                            <p><strong style="color: rgba(0, 0, 0, 0.5);">Dificuldade:</strong> <span style="font-weight: bold; color: <?php echo ($destaque['nivel_dificuldade'] == 'Alto' ? '#c00' : ($destaque['nivel_dificuldade'] == 'Médio' ? '#e90' : '#090')); ?>;"><?php echo htmlspecialchars($destaque['nivel_dificuldade']); ?></span></p>

                            <button type="button" class="btn" style="background-color: rgba(0, 0, 0, 0.03); color: rgba(0, 0, 0, 0.5); margin-top: 15px; margin-bottom: 5px;" onclick="toggleDescription(<?php echo $destaque['id_trilha']; ?>, this)">
                                Descrição
                            </button>
                            
                            <div id="descricao-<?php echo $destaque['id_trilha']; ?>" class="description-content">
                                <p style="color: rgba(0, 0, 0, 0.7); text-align: left; /* Mantém o alinhamento da descrição à esquerda */"><?php echo nl2br(htmlspecialchars($destaque['descricao'])); ?></p>
                            </div>
                            
                            <a href="<?php echo $destaque_url; ?>" class="btn">
                                Agendar esta trilha
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($trilhas)): ?>
                <div class="trail-container">
                    <?php 
                    foreach ($trilhas as $trilha): 
                    ?>
                        <?php 
                        // Define a URL para o botão de agendamento das outras trilhas
                        $trilha_url = $logado ? "agendar_trilha.php?id={$trilha['id_trilha']}" : "login.html?acesso=negado";
                        ?>
                        <div class="trail-item">
                            <div class="carousel-container trail-img" data-id="<?php echo $trilha['id_trilha']; ?>">
                                <?php if (!empty($trilha['imagens'])): ?>
                                    <?php foreach ($trilha['imagens'] as $index => $imagem_path): ?>
                                        <img 
                                            src="<?php echo htmlspecialchars($imagem_path); ?>" 
                                            alt="Foto <?php echo $index + 1; ?> da trilha <?php echo htmlspecialchars($trilha['nome']); ?>" 
                                            class="trail-img carousel-item <?php echo $index === 0 ? 'active' : ''; ?>"
                                        >
                                    <?php endforeach; ?>
                                    <button type="button" class="carousel-button prev">&lt;</button>
                                    <button type="button" class="carousel-button next">&gt;</button>
                                <?php else: ?>
                                    <p style="text-align: center;">Nenhuma imagem disponível.</p>
                                <?php endif; ?>
                            </div>
                            <div class="trail-info">
                                <p style="font-size: 0.85em; margin-bottom: 5px; color: rgba(0, 0, 0, 0.5);">TRILHA DISPONÍVEL | <?php echo htmlspecialchars($trilha['tipo_trilha']); ?></p>
                                <h3 style="color: rgba(0, 0, 0, 0.5);"><?php echo htmlspecialchars($trilha['nome']); ?></h3>
                                
                                <p><strong style="color: rgba(0, 0, 0, 0.5);">Região:</strong> <?php echo htmlspecialchars($trilha['regiao']); ?></p>
                                <p><strong style="color: rgba(0, 0, 0, 0.5);">Distância:</strong> <?php echo number_format($trilha['distancia'], 1, ',', '.'); ?> km</p>
                                <p><strong style="color: rgba(0, 0, 0, 0.5);">Dificuldade:</strong> <span style="font-weight: bold; color: <?php echo ($trilha['nivel_dificuldade'] == 'Alto' ? '#c00' : ($trilha['nivel_dificuldade'] == 'Médio' ? '#e90' : '#090')); ?>;"><?php echo htmlspecialchars($trilha['nivel_dificuldade']); ?></span></p>
                                
                                <button type="button" class="btn" style="background-color: rgba(0, 0, 0, 0.03); color: rgba(0, 0, 0, 0.5); margin-top: 15px; margin-bottom: 5px;" onclick="toggleDescription(<?php echo $trilha['id_trilha']; ?>, this)">
                                    Descrição
                                </button>
                                
                                <div id="descricao-<?php echo $trilha['id_trilha']; ?>" class="description-content">
                                    <p style="color: rgba(0, 0, 0, 0.7); text-align: left; /* Mantém o alinhamento da descrição à esquerda */"><?php echo nl2br(htmlspecialchars($trilha['descricao'])); ?></p>
                                </div>
                                
                                <a href="<?php echo $trilha_url; ?>" class="btn">
                                  Agendar esta trilha
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

    <script>
        /**
         * Alterna a visibilidade do conteúdo da descrição.
         * @param {number} id - O id da trilha.
         * @param {HTMLElement} button - O elemento botão clicado.
         */
        function toggleDescription(id, button) {
            const content = document.getElementById('descricao-' + id);
            
            // Alterna a classe 'expanded' para controlar a altura e transição via CSS
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                button.innerText = 'Descrição'; // Muda o texto para "Descrição"
            } else {
                content.classList.add('expanded');
                button.innerText = 'Fechar'; // Muda o texto para "Fechar"
            }
        }

        // Lógica do Carrossel Automático e Manual
        document.addEventListener('DOMContentLoaded', function() {
            const containers = document.querySelectorAll('.carousel-container');
            const intervalTime = 2000; // Tempo em milissegundos (2 segundos)

            containers.forEach(container => {
                const items = container.querySelectorAll('.carousel-item');
                const prevButton = container.querySelector('.carousel-button.prev');
                const nextButton = container.querySelector('.carousel-button.next');
                let currentIndex = 0;
                let autoSlideInterval; 
                
                // NOVO: Verifica se é a trilha de destaque (a primeira, que deve ser automática)
                const isDestaque = container.classList.contains('destaque-img');

                // Encontra o item inicialmente ativo ou assume o primeiro
                let initialActive = container.querySelector('.carousel-item.active');
                if (initialActive) {
                    currentIndex = Array.from(items).indexOf(initialActive);
                } else if (items.length > 0) {
                    items[0].classList.add('active');
                    currentIndex = 0;
                }
                
                // Garante que todos os itens tenham display: block no início,
                // e apenas o ativo comece com opacity: 1 (como o CSS já faz)
                items.forEach((item, index) => {
                    // Remove o controle de display via JS no início (fica no CSS com display: block !important)
                    // item.style.display = 'block'; 
                    // item.style.opacity é controlado pela classe .active no CSS/JS
                });


                if (items.length > 1) {
                    
                    function stopAutoSlide() {
                        clearInterval(autoSlideInterval);
                    }

                    function showSlide(index) {
                        const newIndex = (index + items.length) % items.length;
                        
                        // 1. Oculta o slide atual (opacidade 0)
                        items[currentIndex].classList.remove('active');
                        
                        // 2. Define o novo índice
                        currentIndex = newIndex;
                        
                        // 3. Mostra o novo slide (opacidade 1 - o CSS faz a transição suave)
                        items[currentIndex].classList.add('active');

                    }

                    function nextSlide() {
                        showSlide(currentIndex + 1);
                    }

                    function prevSlide() {
                        showSlide(currentIndex - 1);
                    }
                    
                    function startAutoSlide() {
                        stopAutoSlide(); 
                        autoSlideInterval = setInterval(nextSlide, intervalTime);
                    }

                    // APLICA O SLIDE AUTOMÁTICO APENAS À TRILHA DE DESTAQUE
                    if (isDestaque) {
                        startAutoSlide();
                        
                        // Pausa o carrossel ao passar o mouse e retoma ao sair (SÓ PARA O DESTAQUE)
                        container.addEventListener('mouseenter', stopAutoSlide);
                        container.addEventListener('mouseleave', startAutoSlide);
                    }


                    // Adiciona funcionalidade aos botões de navegação
                    if (prevButton) {
                        prevButton.addEventListener('click', function() {
                            // Se for destaque, manipula o timer; caso contrário, só troca o slide
                            if (isDestaque) {
                                stopAutoSlide(); 
                                prevSlide();
                                startAutoSlide(); 
                            } else {
                                prevSlide();
                            }
                        });
                    }

                    if (nextButton) {
                        nextButton.addEventListener('click', function() {
                            // Se for destaque, manipula o timer; caso contrário, só troca o slide
                            if (isDestaque) {
                                stopAutoSlide(); 
                                nextSlide();
                                startAutoSlide(); 
                            } else {
                                nextSlide();
                            }
                        });
                    }

                } else if (items.length === 1) {
                    // Se houver apenas 1 imagem, garante que ela fique visível e estática
                    items[0].classList.add('active');
                    // Esconde as setas se houver apenas uma imagem
                    if (prevButton) prevButton.style.display = 'none';
                    if (nextButton) nextButton.style.display = 'none';
                } else {
                    // Se não houver imagens, esconde as setas
                    if (prevButton) prevButton.style.display = 'none';
                    if (nextButton) nextButton.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>