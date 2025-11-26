<?php
require 'conexao.php';

// --- Funções de Validação de Backend ---

// NOVO: Função robusta para validação de CPF (incluindo dígitos verificadores)
function validarCPF($cpf) {
    // Remove caracteres especiais
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

function validarIdade($dataNascimento) {
    // Verifica se o usuário é de maior de idade (18 anos)
    $hoje = new DateTime();
    $nascimento = new DateTime($dataNascimento);
    $idade = $nascimento->diff($hoje)->y;

    return $idade >= 18;
}

function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) === 11) {
        return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cpf);
    }
    return $cpf;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Coleta e Limpa Dados
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['password'] ?? '';
    $cpf = trim($_POST['cpf'] ?? '');
    $nascimento = $_POST['nascimento'] ?? '';
    $divulgar_trilha = isset($_POST['divulgar']) && $_POST['divulgar'] === 'on';

    // 2. Validação de Campos Obrigatórios
    if (empty($nome) || empty($email) || empty($senha) || empty($cpf) || empty($nascimento)) {
        die("Erro: Todos os campos obrigatórios devem ser preenchidos.");
    }
    
    // 3. Validação de CPF (Formato e Digitos Verificadores)
    if (!validarCPF($cpf)) {
        die("Erro: O CPF é inválido. Por favor, verifique.");
    }

    // 4. Formatação de CPF para inserção no banco
    $cpf_formatado = formatarCPF($cpf);
    if (strlen($cpf_formatado) !== 14) {
        die("Erro: Falha na formatação do CPF após validação."); 
    }

    // 5. Validação de Maioridade (18 anos)
    if (!validarIdade($nascimento)) {
        die("Erro: Você deve ser maior de 18 anos para se cadastrar.");
    }

    // Criptografia de Senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Início da Transação
    $pdo->beginTransaction();

    try {
        // Verificação de duplicidade de CPF e Email (melhorado para ser parte da transação)
        $sql_check = "SELECT cpf, email FROM tb_trilheiro WHERE cpf = ? OR email = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$cpf_formatado, $email]);
        
        if ($stmt_check->rowCount() > 0) {
            $pdo->rollBack();
            die("Erro: Este CPF ou Email já está cadastrado.");
        }
        
        // Inserção na Tabela de Trilheiros (tb_trilheiro)
        $sql_trilheiro = "INSERT INTO tb_trilheiro (cpf, nome, email, senha, data_nascimento) VALUES (?, ?, ?, ?, ?)";
        $stmt_trilheiro = $pdo->prepare($sql_trilheiro);
        $stmt_trilheiro->execute([$cpf_formatado, $nome, $email, $senha_hash, $nascimento]);

        // Se "Quero divulgar trilha" marcado, insere em tb_administrador
        if ($divulgar_trilha) {
            // A tabela tb_administrador usa as mesmas credenciais, incluindo a senha hash
            $sql_admin = "INSERT INTO tb_administrador (cpf, nome, email, senha) VALUES (?, ?, ?, ?)";
            $stmt_admin = $pdo->prepare($sql_admin);
            $stmt_admin->execute([$cpf_formatado, $nome, $email, $senha_hash]);
        }

        $pdo->commit();
        
        // Redirecionamento de Sucesso (USANDO O NOVO PARAMETRO)
        header("Location: login.html?cadastro=sucesso");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        //die("Erro PDO: " . $e->getMessage()); // Para debug
        die("Erro: Não foi possível realizar o cadastro no momento. Tente mais tarde.");
    }

} else {
    // Acesso direto ao script sem POST
    header("Location: cadastro.html");
    exit();
}
?>