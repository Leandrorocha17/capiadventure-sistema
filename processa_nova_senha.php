<?php
require 'conexao.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    $senha_atual = $_POST['senha_atual'] ?? null; // Necessário apenas se logado
    
    $email_alvo = null; // O email que será atualizado no banco
    $redirecionamento_erro = 'Alterar_senha.html?erro=invalida';
    $redirecionamento_sucesso = 'login.html?status=sucesso_senha';

    // --- LÓGICA: IDENTIFICAÇÃO DO USUÁRIO ---
    
    // 1. Cenário: Usuário LOGADO (Alterando Senha)
    if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
        if (!isset($_SESSION['email_usuario'])) {
            header("Location: logout.php"); exit();
        }
        $email_alvo = $_SESSION['email_usuario'];
        $redirecionamento_erro = 'alterar_senha_logado.php?erro=invalida'; // Redireciona para o formulário logado
        $redirecionamento_sucesso = 'alterar_senha_logado.php?status=sucesso'; // NOVO: Redireciona para sucesso logado
    } 
    // 2. Cenário: Usuário NÃO LOGADO (Recuperação de Senha)
    elseif (isset($_SESSION['validacao_email'])) {
        $email_alvo = $_SESSION['validacao_email'];
        // Remove as variáveis de validação temporárias
        unset($_SESSION['validacao_cpf']); 
        unset($_SESSION['validacao_email']);
    } else {
        // Sem contexto de quem está alterando
        header("Location: login.html"); exit();
    }
    
    // --- LÓGICA: VALIDAÇÃO DA NOVA SENHA ---

    if (empty($nova_senha) || $nova_senha !== $confirma_senha) {
        header("Location: " . $redirecionamento_erro);
        exit();
    }

    // Criptografa a nova senha
    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    
    // --- LÓGICA: VERIFICAÇÃO DE SENHA ATUAL (SOMENTE SE LOGADO) ---
    
    if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
        try {
            $sql = "SELECT senha FROM tb_trilheiro WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email_alvo]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario || !password_verify($senha_atual, $usuario['senha'])) {
                // Senha atual incorreta
                header("Location: alterar_senha_logado.php?erro=atual");
                exit();
            }
        } catch (PDOException $e) {
             header("Location: alterar_senha_logado.php?erro=db"); exit();
        }
    }

    // --- LÓGICA: ATUALIZAÇÃO NO BANCO DE DADOS ---

    try {
        // Atualiza a senha na tabela tb_trilheiro
        $sql_update = "UPDATE tb_trilheiro SET senha = ? WHERE email = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$nova_senha_hash, $email_alvo]);

        // Atualiza a senha na tabela tb_administrador (se existir, sem erro se não)
        $sql_update_admin = "UPDATE tb_administrador SET senha = ? WHERE email = ?";
        $stmt_update_admin = $pdo->prepare($sql_update_admin);
        $stmt_update_admin->execute([$nova_senha_hash, $email_alvo]);
        
        // SUCESSO! Redireciona de acordo com o cenário
        header("Location: " . $redirecionamento_sucesso);
        exit();

    } catch (PDOException $e) {
        // Se for erro logado, volta para a tela logada com erro
        if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
            header("Location: alterar_senha_logado.php?erro=db");
        } else {
            // Se for erro não logado, volta para o login
            header("Location: login.html?erro=db_senha");
        }
        exit();
    }

} else {
    // Acesso direto ao script sem POST
    header("Location: login.html");
    exit();
}
?>