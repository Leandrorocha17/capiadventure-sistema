<?php
require 'conexao.php'; // Assumindo que este arquivo contém a conexão $pdo

// Inicia a sessão para acessar o email do usuário
session_start();

// 1. VERIFICAÇÃO DE ACESSO: Deve estar logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['email_usuario'])) {
    header("Location: login.html?acesso=negado");
    exit();
}

// 2. VERIFICAÇÃO DE MÉTODO
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: alterar_senha_logado.php");
    exit();
}

$email_usuario = $_SESSION['email_usuario'];
$senha_antiga = $_POST['senha_antiga'] ?? '';
$nova_senha = $_POST['nova_senha'] ?? '';
$confirma_nova_senha = $_POST['confirma_nova_senha'] ?? '';

// 3. VALIDAÇÃO DE CAMPOS
if (empty($senha_antiga) || empty($nova_senha) || empty($confirma_nova_senha)) {
    // Redireciona de volta com erro, se necessário
    header("Location: alterar_senha_logado.php?status=erro_campos");
    exit();
}

// 4. VALIDAÇÃO DE NOVA SENHA
if ($nova_senha !== $confirma_nova_senha) {
    header("Location: alterar_senha_logado.php?status=mismatch");
    exit();
}

try {
    // 5. BUSCAR SENHA ANTIGA (HASH) NO BANCO DE DADOS
    $sql_busca = "SELECT senha FROM tb_trilheiro WHERE email = ?";
    $stmt_busca = $pdo->prepare($sql_busca);
    $stmt_busca->execute([$email_usuario]);
    $usuario = $stmt_busca->fetch(PDO::FETCH_ASSOC);

    // Se o usuário não for encontrado (nunca deveria acontecer se estiver logado, mas por segurança)
    if (!$usuario) {
        // Encerra a sessão e redireciona
        session_unset();
        session_destroy();
        header("Location: login.html?erro=sessao_invalida");
        exit();
    }

    // 6. VERIFICAR SENHA ANTIGA
    if (!password_verify($senha_antiga, $usuario['senha'])) {
        header("Location: alterar_senha_logado.php?status=invalido");
        exit();
    }

    // 7. GERAR NOVO HASH PARA A NOVA SENHA
    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    // 8. ATUALIZAR SENHA (USANDO TRANSAÇÃO PARA GARANTIR CONSISTÊNCIA)
    $pdo->beginTransaction();

    // Query para atualizar tb_trilheiro
    $sql_trilheiro = "UPDATE tb_trilheiro SET senha = ? WHERE email = ?";
    $stmt_trilheiro = $pdo->prepare($sql_trilheiro);
    $stmt_trilheiro->execute([$nova_senha_hash, $email_usuario]);

    // Query para atualizar tb_administrador (Se o usuário for admin, será atualizado. Se não for, 0 linhas afetadas, mas sem erro.)
    $sql_admin = "UPDATE tb_administrador SET senha = ? WHERE email = ?";
    $stmt_admin = $pdo->prepare($sql_admin);
    $stmt_admin->execute([$nova_senha_hash, $email_usuario]);

    // Confirma as alterações no banco de dados
    $pdo->commit();

    // 9. SUCESSO
    header("Location: alterar_senha_logado.php?status=sucesso");
    exit();

} catch (PDOException $e) {
    // 10. ERRO NO BANCO DE DADOS
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack(); // Desfaz qualquer alteração
    }
    // Para fins de debug: die("Erro PDO: " . $e->getMessage());
    header("Location: alterar_senha_logado.php?status=erro");
    exit();
} catch (Exception $e) {
    // 10. ERRO GENÉRICO
    header("Location: alterar_senha_logado.php?status=erro");
    exit();
}
?>