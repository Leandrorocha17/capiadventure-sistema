<?php
require 'conexao.php';
session_start();

$id_agendamento = $_REQUEST['id_agendamento'] ?? null;
$novo_status = $_REQUEST['novo_status'] ?? null;
$origem = $_REQUEST['origem'] ?? 'gerenciar'; // 'meus' ou 'gerenciar'

if (!$id_agendamento || !$novo_status) {
    // Redireciona com erro se faltar parâmetros
    if ($origem === 'meus') {
        header("Location: meus_agendamentos.php?status=erro");
    } else {
        header("Location: gerenciar_agendamentos.php?status=erro");
    }
    exit();
}

// 1. VERIFICAÇÃO DE ACESSO E PERMISSÃO
// Trilheiro só pode CANCELAR e apenas seus próprios agendamentos em 'Em análise'
if ($origem === 'meus') {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_trilheiro'])) {
        header("Location: login.html?acesso=negado");
        exit();
    }
    
    // Trilheiro só pode cancelar
    if ($novo_status !== 'Cancelado') {
        header("Location: meus_agendamentos.php?status=erro");
        exit();
    }
    
    // Verifica se o agendamento pertence ao trilheiro e está 'Em análise'
    try {
        $sql_check = "SELECT id_trilheiro, status FROM tb_agendamento WHERE id_agendamento = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_agendamento]);
        $agendamento = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$agendamento || $agendamento['id_trilheiro'] != $_SESSION['id_trilheiro'] || $agendamento['status'] !== 'Em análise') {
            header("Location: meus_agendamentos.php?status=erro_permissao"); // Status específico para erro de permissão/status
            exit();
        }
        
    } catch (PDOException $e) {
        header("Location: meus_agendamentos.php?status=erro");
        exit();
    }
    
} 
// Administrador pode AGENDAR ou CANCELAR
elseif ($origem === 'gerenciar') {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        header("Location: login.html?acesso=negado");
        exit();
    }
    
    // Administrador só pode alterar para 'Agendado' ou 'Cancelado'
    if (!in_array($novo_status, ['Agendado', 'Cancelado'])) {
        header("Location: gerenciar_agendamentos.php?status=erro");
        exit();
    }
} else {
    // Origem inválida
    header("Location: index.php"); 
    exit();
}

// 2. ATUALIZAÇÃO DO STATUS
try {
    $sql_update = "UPDATE tb_agendamento SET status = ? WHERE id_agendamento = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$novo_status, $id_agendamento]);

    // 3. REDIRECIONAMENTO COM BASE NA ORIGEM E NOVO STATUS
    if ($origem === 'meus') {
        // Redireciona o Trilheiro após Cancelamento
        header("Location: meus_agendamentos.php?status=cancelado_sucesso");
    } else {
        // Redireciona o Administrador
        if ($novo_status === 'Agendado') {
            header("Location: gerenciar_agendamentos.php?status=agendado");
        } elseif ($novo_status === 'Cancelado') {
            header("Location: gerenciar_agendamentos.php?status=cancelado");
        }
    }
    
} catch (PDOException $e) {
    if ($origem === 'meus') {
        header("Location: meus_agendamentos.php?status=erro");
    } else {
        header("Location: gerenciar_agendamentos.php?status=erro");
    }
}
exit();