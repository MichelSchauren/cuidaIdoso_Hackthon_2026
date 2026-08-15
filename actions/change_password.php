<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$senha_atual = $_POST['senha_atual'] ?? '';
$nova = $_POST['nova_senha'] ?? '';
$conf = $_POST['confirma_senha'] ?? '';

if ($senha_atual === '' || $nova === '' || $conf === '') {
    redirect('../profile.php?pass_error=' . urlencode('Preencha todos os campos.'));
}
if (strlen($nova) < 8) {
    redirect('../profile.php?pass_error=' . urlencode('A nova senha deve ter ao menos 8 caracteres.'));
}
if ($nova !== $conf) {
    redirect('../profile.php?pass_error=' . urlencode('A confirmação da senha não confere.'));
}

$db = getDB();
$stmt = $db->prepare('SELECT senha_hash FROM usuario WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) {
    redirect('../profile.php?pass_error=' . urlencode('Usuário não encontrado.'));
}

if (!password_verify($senha_atual, $user['senha_hash'])) {
    redirect('../profile.php?pass_error=' . urlencode('Senha atual incorreta.'));
}

$newHash = password_hash($nova, PASSWORD_DEFAULT);
$upd = $db->prepare('UPDATE usuario SET senha_hash = ? WHERE id = ?');
$upd->execute([$newHash, $_SESSION['user_id']]);

redirect('../profile.php?pass_changed=1');
