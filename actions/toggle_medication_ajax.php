<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$medId = isset($_POST['med_id']) ? (int)$_POST['med_id'] : 0;
$histId = isset($_POST['hist_id']) ? (int)$_POST['hist_id'] : 0;
$idosoId = isset($_POST['idoso_id']) ? (int)$_POST['idoso_id'] : 0;

pacienteDoUsuarioOuFalha($idosoId);

$db = getDB();
if (!$histId && $medId) {
    $stmt = $db->prepare('SELECT id FROM historico_medicamento WHERE medicamento_id = ? ORDER BY horario_previsto DESC LIMIT 1');
    $stmt->execute([$medId]);
    $histId = (int)$stmt->fetchColumn();
}
if (!$histId) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Registro de histórico não encontrado']);
    exit;
}

$stmt = $db->prepare('SELECT status FROM historico_medicamento WHERE id = ?');
$stmt->execute([$histId]);
$status = $stmt->fetchColumn();
if ($status === false) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Registro não encontrado']);
    exit;
}

if ($status === 'administrado') {
    $upd = $db->prepare('UPDATE historico_medicamento SET status = ?, horario_administrado = NULL, cuidador_id = NULL WHERE id = ?');
    $upd->execute(['pendente', $histId]);
    $novo = 'pendente';
} else {
    $upd = $db->prepare('UPDATE historico_medicamento SET status = ?, horario_administrado = NOW(), cuidador_id = ? WHERE id = ?');
    $upd->execute(['administrado', $_SESSION['user_id'], $histId]);
    $novo = 'administrado';
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'newStatus' => $novo, 'hist_id' => $histId]);
