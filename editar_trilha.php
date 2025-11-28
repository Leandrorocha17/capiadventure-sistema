<?php
require 'conexao.php';
session_start();

// 1. VERIFICAÇÃO DE ACESSO
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true || !isset($_SESSION['id_administrador'])) {
    header("Location: login.html?acesso=negado");
    exit();
}

$id_trilha = $_GET['id'] ?? null;
$id_administrador = $_SESSION['id_administrador'];
$trilha = null;

if (!$id_trilha || !is_numeric($id_trilha)) {
    header("Location: gerenciar_trilhas.php?erro=id_invalido");
    exit();
}

// Opções para ENUMs do banco de dados (redefinidas para consistência)
$dificuldades = ['Baixo', 'Médio', 'Alto'];
$tipos_trilha = ['Caminhada', 'Ciclismo', 'Trekking', 'Corrida', 'Passeio', 'Outro'];
$regioes = [
    'Brasília (Plano Piloto)', 'Gama', 'Taguatinga', 'Brazlândia', 'Sobradinho', 'Planaltina', 'Paranoá',
    'Núcleo Bandeirante', 'Ceilândia', 'Guará', 'Cruzeiro', 'Samambaia', 'Santa Maria', 'São Sebastião',
    'Recanto das Emas', 'Lago Sul', 'Riacho Fundo', 'Lago Norte', 'Candangolândia', 'Águas Claras',
    'Riacho Fundo II', 'Sudoeste/Octogonal', 'Varjão', 'Park Way', 'SCIA / Estrutural', 'Sobradinho II',
    'Jardim Botânico', 'Itapoã', 'SIA (Setor de Indústria e Abastecimento)', 'Vicente Pires', 'Fercal', 
    'Sol Nascente / Pôr do Sol'
];
$dias_semana = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
$nomes_dias = [
    'seg' => 'Segunda-feira', 'ter' => 'Terça-feira', 'qua' => 'Quarta-feira', 
    'qui' => 'Quinta-feira', 'sex' => 'Sexta-feira', 'sab' => 'Sábado', 'dom' => 'Domingo'
];

/**
 * Busca todas as imagens associadas a uma trilha.
 * @param PDO $pdo Conexão PDO.
 * @param int $id_trilha ID da trilha.
 * @return array Array de objetos com id_imagem e caminho_imagem.
 */
function buscarImagensTrilha(PDO $pdo, $id_trilha) {
    $sql = "SELECT id_imagem, caminho_imagem FROM tb_trilhas_imagens WHERE id_trilha = :id ORDER BY ordem ASC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id_trilha, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retorna ID para possível exclusão
}


// 2. BUSCA DA TRILHA
try {
    $sql = "SELECT * FROM tb_trilhas WHERE id_trilha = ? AND id_administrador = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_trilha, $id_administrador]);
    $trilha = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trilha) {
        header("Location: gerenciar_trilhas.php?erro=nao_encontrada");
        exit();
    }
    
    // Busca as imagens da trilha
    $trilha['imagens'] = buscarImagensTrilha($pdo, $id_trilha);

} catch (PDOException $e) {
    error_log("Erro ao buscar trilha para edição: " . $e->getMessage());
    header("Location: gerenciar_trilhas.php?erro=db");
    exit();
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Editar Trilha - <?php echo htmlspecialchars($trilha['nome']); ?></title>
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* ---------------------------------- */
        /* ESTILOS DA LOGO (COPIADO DE trilhas.php) */
        /* ---------------------------------- */
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
        
        /* ---------------------------------- */
        /* ESTILOS GLOBAIS DE LINKS/BOTÕES */
        /* ---------------------------------- */
        /* Remove movimento e transição de TODOS os elementos com a classe .btn ao passar o mouse */
        .btn:hover, .btn-acao:hover { 
            text-decoration: none;
            transform: none !important; 
            transition: none !important;
        }

        /* Remove o sublinhado e movimento dos links da barra superior (.top-link) */
        .top-link:hover {
            text-decoration: none;
            transform: none !important; 
            transition: none !important; 
        }

        /* ---------------------------------- */
        /* ESTILOS DO RODAPÉ */
        /* ---------------------------------- */
        .footer {
            background-color: black; 
            color: white; 
            text-align: center; 
            padding: 15px 0;
            width: 100%;
        }
        
        /* ---------------------------------- */
        /* ESTILOS DE ORGANIZAÇÃO (CENTRALIZAÇÃO) */
        /* ---------------------------------- */
        .form-container {
            max-width: 700px;
            margin: 0 auto; /* Centraliza o bloco do formulário na tela */
            padding: 20px;
            padding-bottom: 80px; /* Adiciona espaço extra para o rodapé não cobrir o conteúdo */
        }
        
        /* Centraliza os botões de submissão no formulário (ATUALIZADO) */
        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px; /* Espaçamento entre os botões */
            margin-top: 25px;
        }
        
        /* Estilos dos botões de Ação (Para garantir tamanho igual) */
        .btn-acao {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none; /* Remove sublinhado */
            font-weight: bold;
            text-align: center;
            transition: background-color 0.3s;
            min-width: 150px; /* Garante tamanho mínimo e igual para os botões */
            display: inline-block; /* Necessário para links/botões no flex container */
        }

        .btn-editar {
            background-color: #ffc107; /* Amarelo para Cancelar */
            color: #212529;
        }
        .btn-editar:hover {
            background-color: #e0a800;
        }
        
        /* Estilo para o botão Salvar */
        .btn-salvar {
            background-color: #34B5CC; /* Cor de Salvar Padrão (azul) */
            color: white;
        }
        .btn-salvar:hover {
            background-color: #2C9CAD;
        }


        /* Corrigindo a cor do título principal para admins */
        h2 {
            color: rgba(0, 0, 0, 0.5) !important;
            text-align: center;
            margin-bottom: 25px;
        }

        /* Espaçamento para cabeçalhos de seção (Horários, Fotos) */
        .section-header {
            margin-top: 30px;
        }

        /* Espaçamento padrão para labels */
        .form-container label {
            display: block; 
            margin-top: 15px; 
        }

        /* Margem extra para a label de upload de fotos */
        .label-top-margin {
            margin-top: 20px;
        }
        
        /* Mensagens de Status (Sucesso/Erro) (NOVO) */
        .status-alerta {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .status-alerta.sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-alerta.erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Mensagem de "Nenhuma foto" (NOVO) */
        .no-photos-message {
            text-align: center;
            color: #888;
            padding: 10px 0;
        }

        /* ---------------------------------- */
        /* ESTILOS DE HORÁRIO */
        /* ---------------------------------- */
        .form-group-horario {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-between;
        }

        .horario-item {
            flex: 1 1 calc(50% - 10px); 
            margin-bottom: 10px;
            min-width: 280px;
        }

        .horario-item h4 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 1em;
            color: #555;
        }

        .horario-input-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .horario-input-group input[type="time"] {
            flex: 1;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .horario-input-group span {
            font-weight: bold;
            color: #555;
        }

        /* ---------------------------------- */
        /* ESTILOS DO CARROSSEL DE EDIÇÃO */
        /* ---------------------------------- */
        .carousel-container {
            position: relative; 
            overflow: hidden; 
            width: 100%;
            height: 350px; 
            object-fit: cover; 
            border-radius: 0; 
            margin-bottom: 20px;
        }

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
            display: none; /* Garante que itens não visíveis não ocupem espaço */
        }
        
        .carousel-item.active {
            display: block !important; 
            opacity: 1; 
        }

        /* Estilo para a imagem de dentro do item do carrossel */
        .current-image-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Setas de navegação */
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
        
        /* Botão de Excluir Imagem (dentro do carousel-item) */
        .delete-image-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            line-height: 28px; 
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            opacity: 0.9;
            z-index: 20; 
        }
        
        .delete-image-btn:hover {
            background-color: #c82333;
            text-decoration: none;
            transform: none !important;
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
                <li><a href="trilhas.php" class="top-link">Trilhas</a></li>
                <li><a href="gerenciar_trilhas.php" class="top-link">Gerenciar Minhas Trilhas</a></li>
                <li><a href="gerenciar_agendamentos.php" class="top-link">Gerenciar Agendamentos</a></li>
                <li><a href="alterar_senha_logado.php" class="top-link">Alterar Senha</a></li>
                <li><a href="logout.php" class="top-link">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main class="main">
        <section class="screen" id="tela_editar_trilha" aria-labelledby="editar-heading">
            <div class="form-container">
                <h2 id="editar-heading">Editar Trilha: <?php echo htmlspecialchars($trilha['nome']); ?></h2>
                
                <?php if (isset($_GET['status']) && $_GET['status'] == 'sucesso'): ?>
                    <p class="status-alerta sucesso">Trilha atualizada com sucesso!</p>
                <?php elseif (isset($_GET['status']) && $_GET['status'] == 'erro'): ?>
                    <p class="status-alerta erro">Erro ao salvar alterações. Verifique os dados ou logs.</p>
                <?php endif; ?>

                <form action="processa_editar_trilha.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_trilha" value="<?php echo $trilha['id_trilha']; ?>">
                    
                    <label for="nome">Nome:</label>
                    <input id="nome" type="text" name="nome" value="<?php echo htmlspecialchars($trilha['nome']); ?>" required>

                    <label for="descricao">Descrição:</label>
                    <textarea id="descricao" name="descricao" rows="5" required><?php echo htmlspecialchars($trilha['descricao']); ?></textarea>

                    <label for="distancia">Distância (km):</label>
                    <input id="distancia" type="number" step="0.1" name="distancia" value="<?php echo htmlspecialchars($trilha['distancia']); ?>" required>

                    <label for="tipo_trilha">Tipo de Trilha:</label>
                    <select id="tipo_trilha" name="tipo_trilha" required>
                        <?php foreach ($tipos_trilha as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo $trilha['tipo_trilha'] == $t ? 'selected' : ''; ?>>
                                <?php echo $t; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="nivel_dificuldade">Nível de Dificuldade:</label>
                    <select id="nivel_dificuldade" name="nivel_dificuldade" required>
                        <?php foreach ($dificuldades as $d): ?>
                            <option value="<?php echo $d; ?>" <?php echo $trilha['nivel_dificuldade'] == $d ? 'selected' : ''; ?>>
                                <?php echo $d; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="regiao">Região:</label>
                    <select id="regiao" name="regiao" required>
                        <?php foreach ($regioes as $r): ?>
                            <option value="<?php echo $r; ?>" <?php echo $trilha['regiao'] == $r ? 'selected' : ''; ?>>
                                <?php echo $r; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <h3 class="section-header">Horários de Funcionamento</h3>
                    <div class="form-group-horario">
                        <?php foreach ($dias_semana as $dia): ?>
                        <div class="horario-item">
                            <label for="hr_<?php echo $dia; ?>_entrada" style="margin-top: 0;">
                                <?php echo $nomes_dias[$dia]; ?>:
                            </label>
                            <div class="horario-input-group">
                                <input type="time" name="hr_<?php echo $dia; ?>_entrada" value="<?php echo htmlspecialchars($trilha["hr_{$dia}_entrada"] ?? ''); ?>" title="Entrada <?php echo $nomes_dias[$dia]; ?>">
                                <span> - </span>
                                <input type="time" name="hr_<?php echo $dia; ?>_saida" value="<?php echo htmlspecialchars($trilha["hr_{$dia}_saida"] ?? ''); ?>" title="Saída <?php echo $nomes_dias[$dia]; ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <h3 class="section-header">Fotos Atuais (Clique nas setas ou no [X] para Excluir)</h3>
                    
                    <?php if (!empty($trilha['imagens'])): ?>
                        <div class="carousel-container image-gallery">
                            <?php foreach ($trilha['imagens'] as $index => $img_data): ?>
                                <div class="carousel-item" style="<?php echo $index === 0 ? 'display: block;' : 'display: none;'; ?>">
                                    <img 
                                        src="<?php echo htmlspecialchars($img_data['caminho_imagem']); ?>" 
                                        alt="Foto <?php echo $index + 1; ?> da trilha" 
                                        class="current-image-preview"
                                    >
                                    <a href="processa_excluir_imagem.php?id_imagem=<?php echo $img_data['id_imagem']; ?>&id_trilha=<?php echo $trilha['id_trilha']; ?>" 
                                       onclick="return confirm('Tem certeza que deseja EXCLUIR esta foto?')" 
                                       class="delete-image-btn"
                                       title="Excluir foto">
                                       &#10006;
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            
                            <button type="button" class="carousel-button prev">&lt;</button>
                            <button type="button" class="carousel-button next">&gt;</button>
                        </div>
                    <?php else: ?>
                        <p class="no-photos-message">Esta trilha não possui fotos cadastradas.</p>
                    <?php endif; ?>

                    <label for="imagens" class="label-top-margin">Adicionar Novas Fotos (Opcional, Máx. 10):</label>
                    <input id="imagens" type="file" name="imagens[]" accept="image/jpeg, image/png" multiple onchange="validarFotos(this)">
                    
                    <div class="button-group">
                        <button type="submit" class="btn-acao btn-salvar">Salvar</button>
                        <a href="gerenciar_trilhas.php" class="btn-acao btn-editar">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <p><?php echo date('Y'); ?> CAPIADVENTURE. Desenvolvido por Leandro Rocha & Gabriel Gonçalves.</p>
    </footer>

    <script>
        /**
         * Valida se o número de fotos selecionadas não excede o limite (10).
         */
        function validarFotos(input) {
            const maxFotos = 10;
            if (input.files.length > maxFotos) {
                alert(`Você pode selecionar no máximo ${maxFotos} fotos. Por favor, remova ${input.files.length - maxFotos} fotos.`);
                input.value = ''; 
            }
        }
        
        // Lógica do Carrossel Automático e Manual (Intervalo de 2 segundos)
        document.addEventListener('DOMContentLoaded', function() {
            // Target apenas o carrossel de gerenciamento de fotos.
            const container = document.querySelector('.image-gallery'); 
            const intervalTime = 2000; // Tempo em milissegundos (2 segundos)

            if (container) {
                const items = container.querySelectorAll('.carousel-item');
                const prevButton = container.querySelector('.carousel-button.prev');
                const nextButton = container.querySelector('.carousel-button.next');
                let currentIndex = 0;
                let autoSlideInterval; 

                if (items.length > 1) {
                    // Inicializa: Garante que o primeiro item esteja ativo
                    items[0].classList.add('active');
                    
                    // Exibe os botões de navegação manual
                    if (prevButton) prevButton.style.display = 'block';
                    if (nextButton) nextButton.style.display = 'block';

                    function showSlide(index) {
                        // Esconde o item anterior antes de mudar o índice
                        items[currentIndex].classList.remove('active');
                        items[currentIndex].style.display = 'none'; 

                        currentIndex = (index + items.length) % items.length; // Garante que o índice esteja dentro dos limites
                        
                        // Mostra o novo item
                        items[currentIndex].classList.add('active');
                        items[currentIndex].style.display = 'block'; 
                    }

                    function nextSlide() {
                        showSlide(currentIndex + 1);
                    }

                    function prevSlide() {
                        showSlide(currentIndex - 1);
                    }

                    // Inicia a transição automática
                    function startAutoSlide() {
                        stopAutoSlide(); 
                        autoSlideInterval = setInterval(nextSlide, intervalTime);
                    }

                    // Para a transição automática
                    function stopAutoSlide() {
                        clearInterval(autoSlideInterval);
                    }

                    // Inicia o carrossel automático
                    startAutoSlide();

                    // Adiciona funcionalidade aos botões de navegação
                    if (prevButton) {
                        prevButton.addEventListener('click', function() {
                            stopAutoSlide(); 
                            prevSlide();
                            startAutoSlide(); 
                        });
                    }

                    if (nextButton) {
                        nextButton.addEventListener('click', function() {
                            stopAutoSlide(); 
                            nextSlide();
                            startAutoSlide(); 
                        });
                    }

                    // Pausar carrossel ao passar o mouse
                    container.addEventListener('mouseenter', stopAutoSlide);
                    container.addEventListener('mouseleave', startAutoSlide);

                } else if (items.length === 1) {
                    // Se houver apenas 1 imagem, garante que ela fique visível e estática
                    items[0].style.display = 'block';
                    items[0].classList.add('active');
                    // Esconde as setas se houver apenas uma imagem
                    if (prevButton) prevButton.style.display = 'none';
                    if (nextButton) nextButton.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>
