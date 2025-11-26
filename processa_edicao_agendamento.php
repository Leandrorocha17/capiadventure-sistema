<?php
session_start();
require 'conexao.php';

// 1. VERIFICAÇÃO DE ACESSO
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['id_trilheiro'])) {
    header("Location: login.html?acesso=negado");
    exit();
}

// 2. RECEBE E VALIDA DADOS
$id_agendamento = $_POST['id_agendamento'] ?? null;
$nova_data = $_POST['data_agendamento'] ?? null;
$novo_horario = $_POST['hora_agendamento'] ?? null;
$id_trilheiro = $_SESSION['id_trilheiro'];

if (!$id_agendamento || !$nova_data || !$novo_horario || !is_numeric($id_agendamento)) {
    header("Location: meus_agendamentos.php?status=erro_edicao&msg=dados_incompletos");
    exit();
}

// 3. BUSCA DETALHES DA TRILHA e VALIDAÇÃO INICIAL
try {
    $sql = "
        SELECT 
            t.*, a.status 
        FROM tb_agendamento a
        JOIN tb_trilhas t ON a.id_trilha = t.id_trilha
        WHERE a.id_agendamento = ? AND a.id_trilheiro = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_agendamento, $id_trilheiro]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados || $dados['status'] !== 'Em análise') {
        // Impede a edição se o agendamento não existir ou não estiver 'Em análise'
        header("Location: meus_agendamentos.php?status=erro_edicao&msg=status_invalido");
        exit();
    }
    
    $trilha = $dados;

} catch (PDOException $e) {
    header("Location: meus_agendamentos.php?status=erro_edicao&msg=db_select");
    exit();
}

// 4. VALIDAÇÃO DE REGRA DE NEGÓCIO (DATA FUTURA e HORÁRIO DE FUNCIONAMENTO)
$data_hora_agendamento = new DateTime("{$nova_data} {$novo_horario}");
$agora = new DateTime();

// a) Data e hora futura
if ($data_hora_agendamento < $agora) {
    header("Location: editar_agendamento.php?id={$id_agendamento}&status=invalido&msg=Data e hora devem ser futuras.");
    exit();
}

// b) Horário de funcionamento da trilha
$dia_semana_num = $data_hora_agendamento->format('w'); // 0 (Domingo) a 6 (Sábado)
$dias_map = [
    0 => 'dom', 1 => 'seg', 2 => 'ter', 3 => 'qua', 4 => 'qui', 5 => 'sex', 6 => 'sab'
];
$dia_sigla = $dias_map[$dia_semana_num];

$hr_entrada = $trilha["hr_{$dia_sigla}_entrada"];
$hr_saida = $trilha["hr_{$dia_sigla}_saida"];

if (!$hr_entrada || !$hr_saida) {
    header("Location: editar_agendamento.php?id={$id_agendamento}&status=invalido&msg=Trilha fechada neste dia da semana.");
    exit();
}

// Verifica se o novo horário está dentro do intervalo permitido
if ($novo_horario < $hr_entrada || $novo_horario > $hr_saida) {
    header("Location: editar_agendamento.php?id={$id_agendamento}&status=invalido&msg=Horário fora do funcionamento: {$hr_entrada} às {$hr_saida}.");
    exit();
}


// 5. ATUALIZA O AGENDAMENTO NO BANCO
try {
    // Apenas a data e o horário são atualizados. O status permanece 'Em análise'.
    $sql_update = "
        UPDATE tb_agendamento 
        SET data_agendamento = ?, horario = ?
        WHERE id_agendamento = ? AND id_trilheiro = ? AND status = 'Em análise'
    ";
    $stmt = $pdo->prepare($sql_update);
    $sucesso = $stmt->execute([$nova_data, $novo_horario, $id_agendamento, $id_trilheiro]);

    if ($sucesso) {
        header("Location: meus_agendamentos.php?status=sucesso_edicao");
        exit();
    } else {
        header("Location: meus_agendamentos.php?status=erro_edicao&msg=falha_update");
        exit();
    }

} catch (PDOException $e) {
    header("Location: meus_agendamentos.php?status=erro_edicao&msg=db_update");
    exit();
}