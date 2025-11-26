<?php
require 'conexao.php'; // Inclui a conexão com o banco de dados
session_start();

// 1. VERIFICAÇÃO DE ACESSO
// Deve ser ADMIN logado e o ID do administrador deve estar na sessão
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true || !isset($_SESSION['id_administrador'])) {
    header("Location: login.html?acesso=negado");
    exit();
}

// 2. VALIDAÇÃO E COLETA DE DADOS
$id_imagem = $_GET['id_imagem'] ?? null;
$id_trilha = $_GET['id_trilha'] ?? null;
$id_administrador = $_SESSION['id_administrador'];

// Redireciona se faltar algum ID
if (!$id_imagem || !$id_trilha || !is_numeric($id_imagem) || !is_numeric($id_trilha)) {
    header("Location: editar_trilha.php?id=" . $id_trilha . "&status=erro&msg=id_invalido");
    exit();
}

try {
    // Inicia a transação para garantir atomicidade das operações
    $pdo->beginTransaction();

    // 3. VERIFICAÇÃO DE PERMISSÃO E BUSCA DO CAMINHO DA IMAGEM
    // Verifica se a imagem pertence à trilha e se a trilha pertence ao administrador logado.
    $sql_busca = "
        SELECT 
            ti.caminho_imagem
        FROM tb_trilhas_imagens ti
        JOIN tb_trilhas t ON ti.id_trilha = t.id_trilha
        WHERE ti.id_imagem = ? AND ti.id_trilha = ? AND t.id_administrador = ?
    ";
    $stmt_busca = $pdo->prepare($sql_busca);
    $stmt_busca->execute([$id_imagem, $id_trilha, $id_administrador]);
    $imagem = $stmt_busca->fetch(PDO::FETCH_ASSOC);

    // Se a imagem não for encontrada ou não pertencer a este administrador, aborta.
    if (!$imagem) {
        $pdo->rollBack();
        header("Location: editar_trilha.php?id=" . $id_trilha . "&status=erro&msg=permissao_negada_ou_nao_encontrada");
        exit();
    }
    
    $caminho_imagem = $imagem['caminho_imagem'];

    // 4. EXCLUIR ARQUIVO FÍSICO DO SERVIDOR
    // Usa 'unlink' para deletar o arquivo, se ele existir
    if (file_exists($caminho_imagem) && is_file($caminho_imagem)) {
        if (!unlink($caminho_imagem)) {
            // Se falhar a exclusão do arquivo, tenta reverter a operação do banco (embora ainda não tenha feito nada no banco)
            $pdo->rollBack();
            header("Location: editar_trilha.php?id=" . $id_trilha . "&status=erro&msg=erro_exclusao_arquivo");
            exit();
        }
    }
    // OBS: Se o arquivo não existir, apenas continua (para limpar o registro do banco).

    // 5. EXCLUIR REGISTRO DO BANCO DE DADOS
    $sql_delete = "DELETE FROM tb_trilhas_imagens WHERE id_imagem = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$id_imagem]);
    
    // Confirma a transação
    $pdo->commit();

    // SUCESSO: Redireciona de volta para a tela de edição
    header("Location: editar_trilha.php?id=" . $id_trilha . "&status=sucesso&msg=imagem_excluida");
    exit();

} catch (PDOException $e) {
    // Em caso de qualquer erro no banco, desfaz as alterações (rollback)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao excluir imagem da trilha: " . $e->getMessage());
    
    // Redireciona com erro
    header("Location: editar_trilha.php?id=" . $id_trilha . "&status=erro&msg=erro_db");
    exit();
}
?>