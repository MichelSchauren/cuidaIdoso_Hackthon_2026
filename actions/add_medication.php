<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$idosoId = (int)($_POST['idoso_id'] ?? 0);
pacienteDoUsuarioOuFalha($idosoId); // valida posse

$nome = trim($_POST['nome'] ?? '');
$dosagem = trim($_POST['dosagem'] ?? '');
$intervalo = (int)($_POST['intervalo_horas'] ?? 8) ?: 8;
$instrucoes = trim($_POST['instrucoes'] ?? '');

if ($nome !== '') {
    $db = getDB();
    // Insere medicamento
    $stmt = $db->prepare('INSERT INTO medicamento (idoso_id, nome, dosagem, intervalo_horas, instrucoes, ativo, criado_em) VALUES (?,?,?,?,?,1,NOW())');
    $stmt->execute([$idosoId, $nome, $dosagem, $intervalo, $instrucoes]);
    $medId = $db->lastInsertId();

    // Cria entrada inicial no histórico com a próxima dose hoje na hora padrão (se fornecida)
    $hora = $_POST['proxima_hora'] ?? null;
    if ($hora) {
        $horarioPrevisto = date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' ' . $hora));
    } else {
        $horarioPrevisto = date('Y-m-d H:i:s');
    }
    $stmt = $db->prepare('INSERT INTO historico_medicamento (medicamento_id, cuidador_id, horario_previsto, horario_administrado, status, criado_em) VALUES (?,?,?,?,?,NOW())');
    $stmt->execute([$medId, null, $horarioPrevisto, null, 'pendente']);
}

redirect('../medications.php?id=' . $idosoId);
