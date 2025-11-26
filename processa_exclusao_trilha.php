<?php
require 'conexao.php'; // Inclui a conexão com o banco de dados
session_start();

// 1. VERIFICAÇÃO DE ACESSO: Deve ser ADMIN logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.html?acesso=negado"); 
    exit();
}

// 2. RECEBE E VALIDA O ID DA TRILHA
$id_trilha = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_trilha) {
    // Redireciona com erro se o ID for inválido ou não fornecido
    header("Location: gerenciar_trilhas.php?status=erro");
    exit();
}

try {
    $pdo->beginTransaction(); // Inicia a transação para garantir que todas as exclusões ocorram ou nenhuma ocorra.
    
    // =======================================================
    // A. EXCLUSÃO DAS IMAGENS FÍSICAS E REGISTROS EM tb_trilhas_imagens
    // =======================================================

    // 1. Seleciona os caminhos das imagens
    $sql_caminhos = "SELECT caminho_imagem FROM tb_trilhas_imagens WHERE id_trilha = :id_trilha";
    $stmt_caminhos = $pdo->prepare($sql_caminhos);
    $stmt_caminhos->bindValue(':id_trilha', $id_trilha, PDO::PARAM_INT);
    $stmt_caminhos->execute();
    $imagens = $stmt_caminhos->fetchAll(PDO::FETCH_COLUMN);

    // 2. Exclui os arquivos físicos
    foreach ($imagens as $caminho) {
        if (file_exists($caminho)) {
            unlink($caminho); // Remove o arquivo físico
        }
    }

    // 3. Exclui os registros na tabela tb_trilhas_imagens
    $sql_delete_imagens = "DELETE FROM tb_trilhas_imagens WHERE id_trilha = :id_trilha";
    $stmt_delete_imagens = $pdo->prepare($sql_delete_imagens);
    $stmt_delete_imagens->bindValue(':id_trilha', $id_trilha, PDO::PARAM_INT);
    $stmt_delete_imagens->execute();


    // =======================================================
    // B. EXCLUSÃO DOS AGENDAMENTOS RELACIONADOS EM tb_agendamento
    // =======================================================
    // Se existir agendamento com id_trilha, deleta.
    $sql_delete_agendamentos = "DELETE FROM tb_agendamento WHERE id_trilha = :id_trilha";
    $stmt_delete_agendamentos = $pdo->prepare($sql_delete_agendamentos);
    $stmt_delete_agendamentos->bindValue(':id_trilha', $id_trilha, PDO::PARAM_INT);
    $stmt_delete_agendamentos->execute();


    // =======================================================
    // C. EXCLUSÃO DA TRILHA PRINCIPAL EM tb_trilhas
    // =======================================================
    $sql_delete_trilha = "DELETE FROM tb_trilhas WHERE id_trilha = :id_trilha";
    $stmt_delete_trilha = $pdo->prepare($sql_delete_trilha);
    $stmt_delete_trilha->bindValue(':id_trilha', $id_trilha, PDO::PARAM_INT);
    $stmt_delete_trilha->execute();
    
    $pdo->commit(); // Confirma todas as operações
    
    // 3. Redireciona com sucesso
    header("Location: gerenciar_trilhas.php?status=excluida");
    exit();

} catch (Exception $e) {
    $pdo->rollBack(); // Em caso de erro, desfaz todas as operações
    // Log do erro (opcional)
    // error_log("Erro ao excluir trilha: " . $e->getMessage()); 
    
    // 4. Redireciona com erro
    header("Location: gerenciar_trilhas.php?status=erro");
    exit();
}
?>