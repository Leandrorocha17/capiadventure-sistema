<?php
require 'conexao.php';
session_start();

// VERIFICAÇÃO DE ACESSO: Deve ser ADMIN logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true || !isset($_SESSION['id_administrador'])) {
    header("Location: login.html?acesso=negado");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: cadastro_trilha.php");
    exit();
}

// ID do administrador logado
$id_administrador = $_SESSION['id_administrador'];

// 1. Coleta e Limpa Dados do Formulário
$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$distancia = floatval($_POST['distancia'] ?? 0);
$tipo_trilha = $_POST['tipo_trilha'] ?? '';
$nivel_dificuldade = $_POST['nivel_dificuldade'] ?? '';
$regiao = $_POST['regiao'] ?? '';

// Coleta horários de Entrada e Saída
$horarios = [];
$dias_semana = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
foreach ($dias_semana as $dia) {
    $horarios["hr_{$dia}_entrada"] = !empty($_POST["hr_{$dia}_entrada"]) ? $_POST["hr_{$dia}_entrada"] : null;
    $horarios["hr_{$dia}_saida"] = !empty($_POST["hr_{$dia}_saida"]) ? $_POST["hr_{$dia}_saida"] : null;
}

// 2. Validação Básica
if (empty($nome) || empty($descricao) || $distancia <= 0 || empty($tipo_trilha) || empty($nivel_dificuldade) || empty($regiao)) {
    header("Location: cadastro_trilha.php?status=erro_campos");
    exit();
}

// 3. Configuração e Processamento do Upload das Imagens
$imagens_upload = [];
$diretorio_upload = 'uploads/trilhas/';
$max_files = 10;
$max_file_size = 2097152; // 2MB
$allowed_mimes = ['image/jpeg', 'image/png'];

if (!isset($_FILES['imagens']) || empty($_FILES['imagens']['name'][0])) {
    header("Location: cadastro_trilha.php?status=erro_img_obrigatoria");
    exit();
}

// Cria o diretório se não existir
if (!is_dir($diretorio_upload)) {
    if (!mkdir($diretorio_upload, 0777, true)) {
         header("Location: cadastro_trilha.php?status=erro_diretorio");
         exit();
    }
}

// Itera sobre as imagens enviadas
for ($i = 0; $i < count($_FILES['imagens']['name']); $i++) {
    // Verifica se houve um upload bem-sucedido para este arquivo específico
    if ($_FILES['imagens']['error'][$i] == UPLOAD_ERR_OK) {
        
        // Validação de Limite de arquivos
        if (count($imagens_upload) >= $max_files) {
            continue; // Já atingiu o limite de 10 fotos
        }

        // Validação de Tamanho e Tipo (MIME Type)
        if ($_FILES['imagens']['size'][$i] > $max_file_size) {
            header("Location: cadastro_trilha.php?status=erro_img_tamanho");
            exit();
        }
        $mime_type = mime_content_type($_FILES['imagens']['tmp_name'][$i]);
        if (!in_array($mime_type, $allowed_mimes)) {
            header("Location: cadastro_trilha.php?status=erro_img_tipo");
            exit();
        }

        // Gera nome único e move o arquivo
        $extensao = pathinfo($_FILES['imagens']['name'][$i], PATHINFO_EXTENSION);
        $nome_arquivo = uniqid('trilha_') . '.' . $extensao;
        $caminho_imagem = $diretorio_upload . $nome_arquivo;

        if (move_uploaded_file($_FILES['imagens']['tmp_name'][$i], $caminho_imagem)) {
            // Armazena o caminho e a ordem (baseado no índice)
            $imagens_upload[] = ['caminho' => $caminho_imagem, 'ordem' => count($imagens_upload) + 1];
        } else {
            header("Location: cadastro_trilha.php?status=erro_upload");
            exit();
        }
    }
}

// Re-valida se pelo menos 1 imagem válida foi carregada
if (empty($imagens_upload)) {
    header("Location: cadastro_trilha.php?status=erro_img_obrigatoria");
    exit();
}

// 4. Inserção no Banco de Dados (Transação PDO)
try {
    // Inicia a transação
    $pdo->beginTransaction();

    // 4.1. Inserção na tb_trilhas (REMOVENDO COLUNA 'imagem')
    $sql_trilha = "INSERT INTO tb_trilhas (
                nome, descricao, distancia, tipo_trilha, nivel_dificuldade, regiao, 
                hr_seg_entrada, hr_seg_saida, 
                hr_ter_entrada, hr_ter_saida, 
                hr_qua_entrada, hr_qua_saida, 
                hr_qui_entrada, hr_qui_saida, 
                hr_sex_entrada, hr_sex_saida, 
                hr_sab_entrada, hr_sab_saida, 
                hr_dom_entrada, hr_dom_saida, 
                id_administrador
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";
    
    $stmt_trilha = $pdo->prepare($sql_trilha);
    
    // Lista de parâmetros (AGORA SEM O CAMINHO DA IMAGEM PRINCIPAL)
    $parametros = [
        $nome, $descricao, $distancia, $tipo_trilha, $nivel_dificuldade, $regiao,
        // Horários de Entrada/Saída
        $horarios['hr_seg_entrada'], $horarios['hr_seg_saida'],
        $horarios['hr_ter_entrada'], $horarios['hr_ter_saida'],
        $horarios['hr_qua_entrada'], $horarios['hr_qua_saida'],
        $horarios['hr_qui_entrada'], $horarios['hr_qui_saida'],
        $horarios['hr_sex_entrada'], $horarios['hr_sex_saida'],
        $horarios['hr_sab_entrada'], $horarios['hr_sab_saida'],
        $horarios['hr_dom_entrada'], $horarios['hr_dom_saida'],
        $id_administrador
    ];

    $stmt_trilha->execute($parametros);
    
    // Pega o ID da trilha recém-inserida
    $id_trilha = $pdo->lastInsertId();

    // 4.2. Inserção na tb_trilhas_imagens para cada foto
    $sql_imagens = "INSERT INTO tb_trilhas_imagens (id_trilha, caminho_imagem, ordem) VALUES (?, ?, ?)";
    $stmt_imagens = $pdo->prepare($sql_imagens);

    foreach ($imagens_upload as $img_data) {
        $stmt_imagens->execute([$id_trilha, $img_data['caminho'], $img_data['ordem']]);
    }

    // Confirma a transação
    $pdo->commit();

    // 🏆 CORREÇÃO APLICADA AQUI 🏆
    // Redireciona para a tela correta de Gerenciamento, com o status correto.
    header("Location: gerenciar_trilhas.php?status=cadastrada"); 
    exit();

} catch (PDOException $e) {
    // Em caso de erro, desfaz todas as operações no banco de dados e deleta as imagens
    $pdo->rollBack();
    foreach ($imagens_upload as $img_data) {
        if (file_exists($img_data['caminho'])) {
            unlink($img_data['caminho']);
        }
    }
    // Redireciona com ERRO GENÉRICO
    // Para fins de debug: die("Erro PDO: " . $e->getMessage());
    header("Location: cadastro_trilha.php?status=erro");
    exit();
}
?>