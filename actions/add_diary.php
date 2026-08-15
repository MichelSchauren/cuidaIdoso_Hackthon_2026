<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$idosoId = (int)($_POST['idoso_id'] ?? 0);
pacienteDoUsuarioOuFalha($idosoId);

$categoria = $_POST['categoria'] ?? 'rotina';
$categoriasValidas = ['rotina', 'incidente', 'observacao', 'sinais_vitais'];
if (!in_array($categoria, $categoriasValidas, true)) {
    $categoria = 'rotina';
}
$mensagem = trim($_POST['mensagem'] ?? '');
$user = currentUser();

if ($mensagem !== '') {
    $stmt = getDB()->prepare('INSERT INTO diario (idoso_id, cuidador_id, categoria, mensagem, criado_em) VALUES (?,?,?,?,NOW())');
    $stmt->execute([$idosoId, $user['id'], $categoria, $mensagem]);
}

redirect('../diary.php?id=' . $idosoId);
