<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$stmt = getDB()->prepare('UPDATE notificacao SET lida = 1 WHERE usuario_id = ?');
$stmt->execute([$_SESSION['user_id']]);

redirect('../notifications.php');
