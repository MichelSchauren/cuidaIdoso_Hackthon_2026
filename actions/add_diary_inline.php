<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$idosoId = (int)($_POST['idoso_id'] ?? 0);
$categoriasValidas = ['rotina', 'incidente', 'observacao', 'sinais_vitais'];
$categoria = $_POST['categoria'] ?? 'rotina';
if (!in_array($categoria, $categoriasValidas, true)) $categoria = 'rotina';
$mensagem = trim($_POST['mensagem'] ?? '');

pacienteDoUsuarioOuFalha($idosoId);
$user = currentUser();
if ($mensagem !== '') {
    $stmt = getDB()->prepare('INSERT INTO diario (idoso_id, cuidador_id, categoria, mensagem, criado_em) VALUES (?,?,?,?,NOW())');
    $stmt->execute([$idosoId, $user['id'], $categoria, $mensagem]);
}

redirect('../patient.php?id=' . $idosoId);
