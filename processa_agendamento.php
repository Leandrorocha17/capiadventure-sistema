<?php
// processa_agendamento.php
require 'conexao.php';
session_start();

// Redireciona se não estiver logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_trilheiro'])) {
    header("Location: index.php?agendamento=logar");
    exit();
}

$id_trilheiro = $_SESSION['id_trilheiro'];
$id_trilha = $_POST['id_trilha'] ?? null;
$data_agendamento = $_POST['data_agendamento'] ?? null;
$horario = $_POST['hora_agendamento'] ?? null; // Usa 'hora_agendamento' do formulário

if (!$id_trilha || !$data_agendamento || !$horario) {
    header("Location: agendar_trilha.php?id={$id_trilha}&status=erro");
    exit();
}

// 1. BUSCA HORÁRIOS DA TRILHA
try {
    $sql_trilha = "SELECT * FROM tb_trilhas WHERE id_trilha = ?";
    $stmt_trilha = $pdo->prepare($sql_trilha);
    $stmt_trilha->execute([$id_trilha]);
    $trilha = $stmt_trilha->fetch(PDO::FETCH_ASSOC);

    if (!$trilha) {
        header("Location: agendar_trilha.php?id={$id_trilha}&status=erro");
        exit();
    }

} catch (PDOException $e) {
    header("Location: agendar_trilha.php?id={$id_trilha}&status=erro");
    exit();
}

// 2. VALIDAÇÃO DE DATA E HORÁRIO (Requisito: não permitir fora do dia de funcionamento)
$dia_semana_num = date('w', strtotime($data_agendamento));
$mapa_dia = [
    0 => 'dom', 1 => 'seg', 2 => 'ter', 3 => 'qua', 
    4 => 'qui', 5 => 'sex', 6 => 'sab'
];
$dia_coluna = $mapa_dia[$dia_semana_num];

$hr_entrada_col = "hr_{$dia_coluna}_entrada";
$hr_saida_col = "hr_{$dia_coluna}_saida";

$hr_entrada = $trilha[$hr_entrada_col];
$hr_saida = $trilha[$hr_saida_col];

$agendamento_valido = true;
$mensagem_invalido = 'Data ou horário inválido para agendamento.';

// Verifica se a trilha está FECHADA no dia
if (empty($hr_entrada) || empty($hr_saida)) {
    $agendamento_valido = false;
    $mensagem_invalido = 'Trilha fechada no dia da semana selecionado.';
} 
// Verifica se o horário está FORA do intervalo
elseif ($horario < $hr_entrada || $horario > $hr_saida) {
    $agendamento_valido = false;
    $mensagem_invalido = 'Horário fora do intervalo de funcionamento ('.$hr_entrada.' a '.$hr_saida.').';
}

if (!$agendamento_valido) {
    // Redireciona com mensagem detalhada de erro de validação
    header("Location: agendar_trilha.php?id={$id_trilha}&status=invalido&msg=" . urlencode($mensagem_invalido));
    exit();
}


// 3. INSERÇÃO NO BANCO (Status inicial: 'Em análise')
try {
    // CORRIGIDO: Inserindo na tabela 'tb_agendamento' e coluna 'horario'
    $sql_insert = "INSERT INTO tb_agendamento (id_trilheiro, id_trilha, data_agendamento, horario, status) 
                   VALUES (?, ?, ?, ?, 'Em análise')"; 
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([$id_trilheiro, $id_trilha, $data_agendamento, $horario]);

    // Sucesso
    header("Location: agendar_trilha.php?id={$id_trilha}&status=sucesso");
    exit();
    
} catch (PDOException $e) {
    // Erro na inserção (resolve o erro 'Erro ao solicitar agendamento')
    header("Location: agendar_trilha.php?id={$id_trilha}&status=erro");
    exit();
}
?>