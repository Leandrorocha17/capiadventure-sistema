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

if (!$id_trilha || !is_numeric($id_trilha)) {
    header("Location: gerenciar_trilhas.php?erro=exclusao_invalida");
    exit();
}

try {
    // 2. BUSCA O CAMINHO DA IMAGEM antes de deletar a trilha
    $sql_select = "SELECT imagem FROM tb_trilhas WHERE id_trilha = ? AND id_administrador = ?";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute([$id_trilha, $id_administrador]);
    $trilha = $stmt_select->fetch(PDO::FETCH_ASSOC);

    if (!$trilha) {
        // Trilha não existe ou não pertence ao admin
        header("Location: gerenciar_trilhas.php?erro=nao_autorizado");
        exit();
    }

    // 3. DELETA A TRILHA do banco de dados
    $sql_delete = "DELETE FROM tb_trilhas WHERE id_trilha = ? AND id_administrador = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$id_trilha, $id_administrador]);

    // 4. DELETA A IMAGEM do servidor, se existir
    $caminho_imagem = $trilha['imagem'];
    if ($caminho_imagem && file_exists($caminho_imagem)) {
        unlink($caminho_imagem);
    }

    // Sucesso
    header("Location: gerenciar_trilhas.php?status=excluida");
    exit();

} catch (PDOException $e) {
    error_log("Erro de DB ao excluir trilha: " . $e->getMessage());
    header("Location: gerenciar_trilhas.php?erro=db_exclusao");
    exit();
}
?>