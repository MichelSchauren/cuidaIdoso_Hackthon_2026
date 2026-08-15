<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$taskId = (int)($_POST['task_id'] ?? 0);
$idosoId = (int)($_POST['idoso_id'] ?? 0);

// Verifica permissão
pacienteDoUsuarioOuFalha($idosoId);

$db = getDB();
$stmt = $db->prepare('SELECT status FROM tarefa WHERE id = ? AND idoso_id = ?');
$stmt->execute([$taskId, $idosoId]);
$atual = $stmt->fetchColumn();

if ($atual === false) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Tarefa não encontrada']);
    exit;
}

$novo = $atual === 'pendente' ? 'concluida' : 'pendente';
if ($novo === 'concluida') {
    $stmt = $db->prepare('UPDATE tarefa SET status = ?, concluida_em = NOW() WHERE id = ?');
    $stmt->execute([$novo, $taskId]);
} else {
    $stmt = $db->prepare('UPDATE tarefa SET status = ?, concluida_em = NULL WHERE id = ?');
    $stmt->execute([$novo, $taskId]);
}

// recalcular progresso
$stmt = $db->prepare('SELECT COUNT(*) FROM tarefa WHERE idoso_id = ?');
$stmt->execute([$idosoId]);
$tasksTotal = (int)$stmt->fetchColumn();
$stmt = $db->prepare("SELECT COUNT(*) FROM tarefa WHERE idoso_id = ? AND status = 'concluida'");
$stmt->execute([$idosoId]);
$tasksDone = (int)$stmt->fetchColumn();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'newStatus' => $novo,
    'tasksDone' => $tasksDone,
    'tasksTotal' => $tasksTotal,
]);
