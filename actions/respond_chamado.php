<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$chamadoId = (int)($_POST['chamado_id'] ?? 0);
$acao = trim($_POST['acao'] ?? '');

if ($chamadoId <= 0 || ($acao !== 'aceitar' && $acao !== 'recusar')) {
    redirect('../search.php');
}

$db = getDB();

// Verificar que o chamado pertence a este cuidador (cobre duas possibilidades: chamado.cuidador_id pode guardar usuario.id ou cuidador.id)

$stmt = $db->prepare('SELECT * FROM chamado WHERE id = ?');
$stmt->execute([$chamadoId]);
$ch = $stmt->fetch();

if (!$ch) {
    redirect('../search.php');
}

$meuUserId = $_SESSION['user_id'];
$pertence = false;
// Caso comum: chamado.cuidador_id guarda usuario.id
if ($ch['cuidador_id'] == $meuUserId) {
    $pertence = true;
} else {
    // Caso legacy: chamado.cuidador_id pode ter armazenado cuidador.id; verificar mapping
    $map = $db->prepare('SELECT usuario_id FROM cuidador WHERE id = ?');
    $map->execute([$ch['cuidador_id']]);
    $mappedUsuario = $map->fetchColumn();
    if ($mappedUsuario && $mappedUsuario == $meuUserId) {
        $pertence = true;
    }
}

if (!$pertence) {
    redirect('../search.php');
}

if ($acao === 'aceitar') {
    $novoStatus = 'aceita';
} else {
    $novoStatus = 'recusada';
}

$upd = $db->prepare('UPDATE chamado SET status = ? WHERE id = ?');
$upd->execute([$novoStatus, $chamadoId]);

// Opcional: notificar o responsável
try {
    $not = $db->prepare('INSERT INTO notificacao (usuario_id, titulo, mensagem, criado_em) VALUES (?,?,?,NOW())');
    $titulo = $acao === 'aceitar' ? 'Solicitação aceita' : 'Solicitação recusada';
    $mensagem = $acao === 'aceitar' ? 'O cuidador aceitou sua solicitação.' : 'O cuidador recusou sua solicitação.';
    $not->execute([$ch['responsavel_id'], $titulo, $mensagem]);
} catch (Exception $e) {
    // não falhar a ação se notificação falhar
}

// Se aceitou, criar/atualizar vínculo em cuidador_idoso (usar usuario.id do cuidador)
try {
    // Normalizar: preferir interpretar ch.cuidador_id como usuario.id quando existir
    $cuidadorUsuarioId = null;
    $checkUser = $db->prepare('SELECT id FROM usuario WHERE id = ?');
    $checkUser->execute([$ch['cuidador_id']]);
    $isUser = $checkUser->fetchColumn();
    if ($isUser) {
        $cuidadorUsuarioId = $ch['cuidador_id'];
    } else {
        // Caso legacy: tentar mapear cuidador.id -> usuario_id
        $map2 = $db->prepare('SELECT usuario_id FROM cuidador WHERE id = ?');
        $map2->execute([$ch['cuidador_id']]);
        $mapped = $map2->fetchColumn();
        if ($mapped) {
            $cuidadorUsuarioId = $mapped;
        }
    }

    if ($novoStatus === 'aceita') {
        $ins = $db->prepare('INSERT INTO cuidador_idoso (idoso_id, cuidador_id, status, criado_em) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status), criado_em = VALUES(criado_em)');
        $ins->execute([$ch['idoso_id'], $cuidadorUsuarioId, 'ativo']);
    }
} catch (Exception $e) {
    // não bloquear a resposta caso falhe a criação do vínculo
}

redirect('../search.php');
