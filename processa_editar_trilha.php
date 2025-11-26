<?php
require 'conexao.php';
session_start();

// 1. VERIFICAÇÃO DE ACESSO
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true || !isset($_SESSION['id_administrador'])) {
    header("Location: login.html?acesso=negado"); 
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: gerenciar_trilhas.php");
    exit();
}

// ID do administrador e da trilha
$id_administrador = $_SESSION['id_administrador'];
$id_trilha = $_POST['id_trilha'] ?? null;
$imagem_antiga = $_POST['imagem_antiga'] ?? null;

// 2. Coleta e Limpa Dados do Formulário
$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$distancia = floatval($_POST['distancia'] ?? 0);
$tipo_trilha = $_POST['tipo_trilha'] ?? '';
$nivel_dificuldade = $_POST['nivel_dificuldade'] ?? '';
$regiao = $_POST['regiao'] ?? '';

// Coleta horários de Entrada e Saída (14 campos)
$horarios = [];
$dias_semana = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
foreach ($dias_semana as $dia) {
    $horarios["hr_{$dia}_entrada"] = !empty($_POST["hr_{$dia}_entrada"]) ? $_POST["hr_{$dia}_entrada"] : null;
    $horarios["hr_{$dia}_saida"] = !empty($_POST["hr_{$dia}_saida"]) ? $_POST["hr_{$dia}_saida"] : null;
}

// 3. Validação Básica
if (empty($id_trilha) || empty($nome) || empty($descricao) || $distancia <= 0 || empty($tipo_trilha) || empty($nivel_dificuldade) || empty($regiao)) {
    header("Location: editar_trilha.php?id=" . urlencode($id_trilha) . "&erro=campos_invalidos");
    exit();
}

// 4. Processamento do Upload da Nova Imagem (Opcional)
$caminho_imagem = $imagem_antiga; // Mantém a imagem antiga por padrão

if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
    $imagem = $_FILES['imagem'];
    $extensao = pathinfo($imagem['name'], PATHINFO_EXTENSION);
    $nome_arquivo = uniqid('trilha_') . '.' . $extensao;
    $diretorio_upload = 'uploads/trilhas/';
    $novo_caminho_imagem = $diretorio_upload . $nome_arquivo;

    if (!is_dir($diretorio_upload)) {
        mkdir($diretorio_upload, 0777, true);
    }
    
    if (move_uploaded_file($imagem['tmp_name'], $novo_caminho_imagem)) {
        // Sucesso no upload da nova imagem, atualiza o caminho e remove a antiga, se houver
        $caminho_imagem = $novo_caminho_imagem;
        if ($imagem_antiga && file_exists($imagem_antiga)) {
            unlink($imagem_antiga);
        }
    } else {
        header("Location: editar_trilha.php?id=" . urlencode($id_trilha) . "&erro=upload");
        exit();
    }
}

// 5. Atualização no Banco de Dados
try {
    // Colunas de horário ATUALIZADAS (14 colunas) e outros campos
    $sql = "UPDATE tb_trilhas SET 
                nome = ?, descricao = ?, imagem = ?, distancia = ?, tipo_trilha = ?, nivel_dificuldade = ?, regiao = ?, 
                hr_seg_entrada = ?, hr_seg_saida = ?, 
                hr_ter_entrada = ?, hr_ter_saida = ?, 
                hr_qua_entrada = ?, hr_qua_saida = ?, 
                hr_qui_entrada = ?, hr_qui_saida = ?, 
                hr_sex_entrada = ?, hr_sex_saida = ?, 
                hr_sab_entrada = ?, hr_sab_saida = ?, 
                hr_dom_entrada = ?, hr_dom_saida = ?
            WHERE id_trilha = ? AND id_administrador = ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Lista de parâmetros ATUALIZADA
    $parametros = [
        $nome, $descricao, $caminho_imagem, $distancia, $tipo_trilha, $nivel_dificuldade, $regiao,
        // Horários de Entrada/Saída
        $horarios['hr_seg_entrada'], $horarios['hr_seg_saida'],
        $horarios['hr_ter_entrada'], $horarios['hr_ter_saida'],
        $horarios['hr_qua_entrada'], $horarios['hr_qua_saida'],
        $horarios['hr_qui_entrada'], $horarios['hr_qui_saida'],
        $horarios['hr_sex_entrada'], $horarios['hr_sex_saida'],
        $horarios['hr_sab_entrada'], $horarios['hr_sab_saida'],
        $horarios['hr_dom_entrada'], $horarios['hr_dom_saida'],
        $id_trilha,
        $id_administrador
    ];

    $stmt->execute($parametros);

    // Sucesso
    header("Location: gerenciar_trilhas.php?status=editada");
    exit();

} catch (PDOException $e) {
    // Em caso de erro, redireciona de volta para o formulário de edição
    error_log("Erro de DB ao editar trilha: " . $e->getMessage());
    header("Location: editar_trilha.php?id=" . urlencode($id_trilha) . "&erro=db");
    exit();
}
?>