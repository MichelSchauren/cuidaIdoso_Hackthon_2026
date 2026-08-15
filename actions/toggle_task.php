<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$taskId = (int)($_POST['task_id'] ?? 0);
$idosoId = (int)($_POST['idoso_id'] ?? 0);
pacienteDoUsuarioOuFalha($idosoId);

$db = getDB();
$stmt = $db->prepare('SELECT status FROM tarefa WHERE id = ? AND idoso_id = ?');
$stmt->execute([$taskId, $idosoId]);
$atual = $stmt->fetchColumn();

if ($atual !== false) {
    $novo = $atual === 'pendente' ? 'concluida' : 'pendente';
    if ($novo === 'concluida') {
        $stmt = $db->prepare('UPDATE tarefa SET status = ?, concluida_em = NOW() WHERE id = ?');
        $stmt->execute([$novo, $taskId]);
    } else {
        $stmt = $db->prepare('UPDATE tarefa SET status = ?, concluida_em = NULL WHERE id = ?');
        $stmt->execute([$novo, $taskId]);
    }
}

redirect('../tasks.php?id=' . $idosoId);
