<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$idosoId = (int)($_POST['idoso_id'] ?? 0);
pacienteDoUsuarioOuFalha($idosoId);

$titulo = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$horario = trim($_POST['horario'] ?? '') ?: '12:00';

if ($titulo !== '') {
    $db = getDB();
    $horarioPrevisto = $horario ? date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' ' . $horario)) : null;
    $stmt = $db->prepare('INSERT INTO tarefa (idoso_id, cuidador_id, titulo, descricao, horario_previsto, status, criado_em) VALUES (?,?,?,?,?,?,NOW())');
    $stmt->execute([$idosoId, $_SESSION['user_id'], $titulo, $descricao, $horarioPrevisto, 'pendente']);
}

redirect('../tasks.php?id=' . $idosoId);
