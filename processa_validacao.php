<?php
// Inclui o arquivo de conexão
require 'conexao.php';
// Inicia a sessão para armazenar o estado de validação
session_start();

// --- Funções Auxiliares (do processa_cadastro.php) ---

/**
 * Valida o CPF. Deve ser chamada com uma string contendo apenas números.
 */
function validarCPF($cpf_somente_numeros) {
    $cpf = $cpf_somente_numeros;
    
    // Remove caracteres especiais (Garantia de segurança, mesmo que o CPF já venha limpo)
    $cpf = preg_replace('/[^0-9]/', '', (string)$cpf);
    
    // Verifica se o número de dígitos é 11
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Evita CPFs inválidos conhecidos (todos os dígitos iguais)
    if (preg_match('/(\\d)\\1{10}/', $cpf)) {
        return false;
    }
    
    // Calcula e confere o primeiro e o segundo dígito verificador
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

/**
 * Formata o CPF para o padrão 000.000.000-00.
 */
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) === 11) {
        return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cpf);
    }
    return $cpf;
}

// ----------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Coleta Dados
    $cpf_input = trim($_POST['cpf'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // 1. Validação de Campos Vazios
    if (empty($cpf_input) || empty($email)) {
        header("Location: Alterar_senha.html?erro=incompleto");
        exit();
    }
    
    // 2. Limpa o CPF de qualquer formatação para validação (CPF deve ter 11 dígitos numéricos)
    $cpf_limpo = preg_replace('/[^0-9]/', '', (string)$cpf_input);
    
    // 3. Validação de CPF (Formato e Digitos Verificadores)
    // Se o CPF limpo não for válido, não é encontrado.
    if (!validarCPF($cpf_limpo)) { 
        header("Location: Alterar_senha.html?erro=nao_encontrado"); 
        exit();
    }

    // 4. Formatação de CPF para a consulta no DB (000.000.000-00)
    // Garante que o formato usado na query seja o mesmo formato de armazenamento do banco.
    $cpf_formatado = formatarCPF($cpf_limpo);
    
    // Validação de segurança se o formatarCPF não retornar o tamanho esperado
    if (strlen($cpf_formatado) !== 14) {
        header("Location: Alterar_senha.html?erro=nao_encontrado");
        exit();
    }
    
    try {
        // 5. Busca o usuário na tabela tb_trilheiro
        // Verifica se o CPF FORMATADO E o EMAIL correspondem a um registro
        $sql = "SELECT nome FROM tb_trilheiro WHERE cpf = ? AND email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cpf_formatado, $email]); // Usa o CPF FORMATADO
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Validação bem-sucedida. Armazena os dados na sessão temporariamente.
            $_SESSION['validacao_cpf'] = $cpf_formatado;
            $_SESSION['validacao_email'] = $email;
            
            // Redireciona para a tela de definição da nova senha
            header("Location: nova_senha.php"); 
            exit();

        } else {
            // Falha na validação (CPF e Email não combinam ou não existem)
            header("Location: Alterar_senha.html?erro=nao_encontrado");
            exit();
        }

    } catch (PDOException $e) {
        // Erro no banco de dados (retorna erro genérico)
        // Para debug: die("Erro PDO: " . $e->getMessage()); 
        header("Location: Alterar_senha.html?erro=nao_encontrado");
        exit();
    }

} else {
    // Acesso direto ao script sem POST
    header("Location: Alterar_senha.html");
    exit();
}
?>