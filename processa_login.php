<?php
require 'conexao.php';

// Inicia a sessão para armazenar o estado de login
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $senha = $_POST['password'];

    // 1. Validação de Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: login.html?erro=credenciais"); // Usa o novo código de erro
        exit();
    }

    try {
        // Busca o usuário na tabela tb_trilheiro (ADICIONADO: 'primeiro_login')
        $sql = "SELECT id_trilheiro, nome, email, senha, primeiro_login FROM tb_trilheiro WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Verifica se o usuário existe e se a senha está correta
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            
            // Login bem-sucedido! Armazena informações na sessão.
            $_SESSION['logado'] = true;
            $_SESSION['nome_usuario'] = $usuario['nome'];
            $_SESSION['email_usuario'] = $usuario['email'];
            $_SESSION['id_trilheiro'] = $usuario['id_trilheiro'];

            // Se for um administrador, armazena o status na sessão
            $sql_admin = "SELECT id_administrador FROM tb_administrador WHERE email = ?";
            $stmt_admin = $pdo->prepare($sql_admin);
            $stmt_admin->execute([$email]);
            $admin_data = $stmt_admin->fetch(PDO::FETCH_ASSOC);

            if ($admin_data) {
                $_SESSION['admin'] = true;
                $_SESSION['id_administrador'] = $admin_data['id_administrador'];
            } else {
                $_SESSION['admin'] = false;
            }

            // ATUALIZAÇÃO: Se for o primeiro login, o status ainda é atualizado
            // no banco, mas o redirecionamento será sempre para a index.
            if ($usuario['primeiro_login'] == 1) {
                // Atualiza o status de primeiro_login para 0 no banco de dados
                $sql_update = "UPDATE tb_trilheiro SET primeiro_login = 0 WHERE id_trilheiro = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$usuario['id_trilheiro']]);
            }
            
            // NOVO: Redireciona SEMPRE para a página inicial (index.php)
            header("Location: trilhas.php");
            exit();

        } else {
            // Login falhou (Usuário não existe ou senha incorreta)
            header("Location: login.html?erro=credenciais"); // Usa o novo código de erro
            exit();
        }

    } catch (PDOException $e) {
        // Erro no banco de dados 
        // Opcional: logar o erro: error_log("Login DB Error: " . $e->getMessage());
        header("Location: login.html?erro=db");
        exit();
    }
} else {
    // Acesso direto ao arquivo sem POST
    header("Location: login.html");
    exit();
}
?>