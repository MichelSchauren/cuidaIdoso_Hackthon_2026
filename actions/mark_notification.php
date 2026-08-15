<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$notifId = (int)($_POST['notif_id'] ?? 0);
$stmt = getDB()->prepare('UPDATE notificacao SET lida = 1 WHERE id = ? AND usuario_id = ?');
$stmt->execute([$notifId, $_SESSION['user_id']]);

redirect('../notifications.php');
