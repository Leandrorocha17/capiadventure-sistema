<?php
require 'conexao.php';
session_start();

// 1. VERIFICAÇÃO DE ACESSO: Deve ser ADMIN logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true || !isset($_SESSION['id_administrador'])) {
    header("Location: login.html?acesso=negado"); 
    exit();
}

$id_administrador = $_SESSION['id_administrador'];
$nome_display = htmlspecialchars(explode(' ', $_SESSION['nome_usuario'])[0]);
$agendamentos = [];

// Opções de status (usadas no ENUM do banco)
$status_opcoes = ['Pendente', 'Confirmado', 'Cancelado', 'Realizado'];

try {
    // 2. BUSCA OS AGENDAMENTOS DAS TRILHAS DESTE ADMINISTRADOR
    $sql = "SELECT 
                a.id_agendamento, 
                t.nome AS nome_trilha, 
                tr.nome AS nome_trilheiro, 
                tr.email AS email_trilheiro, 
                a.data_agendamento, 
                a.hora_agendamento, 
                a.status_agendamento
            FROM tb_agendamento a 
            JOIN tb_trilhas t ON a.id_trilha = t.id_trilha 
            JOIN tb_trilheiro tr ON a.id_trilheiro = tr.id_trilheiro
            WHERE t.id_administrador = ? 
            ORDER BY a.data_agendamento ASC, a.hora_agendamento ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_administrador]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erro_db = "Falha ao carregar agendamentos: " . $e->getMessage();
}

// Reusa a função de estilo
function getStatusClass($status) {
    switch ($status) {
        case 'Confirmado': return 'status-confirmado';
        case 'Cancelado': return 'status-cancelado';
        case 'Realizado': return 'status-realizado';
        default: return 'status-pendente';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>CAPIADVENTURE - Acompanhar Agendamentos</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos reusados do lado do trilheiro */
        .agendamento-container { display: flex; flex-direction: column; gap: 20px; }
        .agendamento-card { 
            padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            background-color: #fff; border-left: 5px solid #007bff;
        }
        .agendamento-card h3 { margin-top: 0; }
        .agendamento-card p { margin: 5px 0; font-size: 0.95em; }
        .status-tag