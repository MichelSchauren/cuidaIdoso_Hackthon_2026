<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$cuidadorId = (int)($_POST['cuidador_id'] ?? 0); // este é o id da tabela `cuidador`
$idosoId = (int)($_POST['idoso_id'] ?? 0);
$dataInicio = trim($_POST['data_inicio'] ?? '');
$dataFim = trim($_POST['data_fim'] ?? '');

pacienteDoUsuarioOuFalha($idosoId);

$db = getDB();
// Precisamos armazenar o usuario.id do cuidador (cuidador.usuario_id) na coluna chamado.cuidador_id
$stmt = $db->prepare('SELECT usuario_id FROM cuidador WHERE id = ?');
$stmt->execute([$cuidadorId]);
$usuarioCuidadorId = $stmt->fetchColumn();

if (!$usuarioCuidadorId) {
	// fallback: se não encontrar, redireciona sem criar chamado
	redirect('../search.php');
}

// Insere na tabela `chamado` (schema MySQL) usando o usuario.id do cuidador
$ins = $db->prepare('INSERT INTO chamado (responsavel_id, cuidador_id, idoso_id, data_inicio, data_fim, valor_acordado, status, criado_em) VALUES (?,?,?,?,?,?,?,NOW())');
$ins->execute([$_SESSION['user_id'], $usuarioCuidadorId, $idosoId, $dataInicio, $dataFim, null, 'pendente']);

redirect('../search.php?enviado=' . $cuidadorId);
