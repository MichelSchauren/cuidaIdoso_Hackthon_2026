<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$medId = (int)($_POST['med_id'] ?? 0);
$idosoId = (int)($_POST['idoso_id'] ?? 0);
pacienteDoUsuarioOuFalha($idosoId);

// Encontrar entrada pendente mais próxima no histórico e marcar como administrada
$db = getDB();
$stmt = $db->prepare('SELECT id FROM historico_medicamento WHERE medicamento_id = ? AND status = ? ORDER BY horario_previsto ASC LIMIT 1');
$stmt->execute([$medId, 'pendente']);
$histId = $stmt->fetchColumn();
if (!$histId) {
	// se não houver pendente, pega a mais recente
	$stmt = $db->prepare('SELECT id FROM historico_medicamento WHERE medicamento_id = ? ORDER BY horario_previsto DESC LIMIT 1');
	$stmt->execute([$medId]);
	$histId = $stmt->fetchColumn();
}
if ($histId) {
	$stmt = $db->prepare('UPDATE historico_medicamento SET horario_administrado = NOW(), status = ?, cuidador_id = ? WHERE id = ?');
	$stmt->execute(['administrado', $_SESSION['user_id'], $histId]);
}

redirect('../medications.php?id=' . $idosoId);
