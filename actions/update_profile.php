<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db = getDB();

// Buscar colunas reais da tabela usuario
$cols = [];
$stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario'");
$stmt->execute();
while ($r = $stmt->fetch()) $cols[] = $r['COLUMN_NAME'];

$blacklist = ['id','senha_hash','criado_em','tipo_usuario'];
$toUpdate = [];
$params = [];
foreach ($cols as $col) {
    if (in_array($col, $blacklist)) continue;
    if (array_key_exists($col, $_POST)) {
        $val = trim($_POST[$col]);
        $toUpdate[] = $col . ' = ?';
        $params[] = $val;
    }
}

// Se está tentando mudar e-mail, verificar duplicidade
if (in_array('email', array_map(function($c){return explode(' = ',$c)[0];}, $toUpdate))) {
    $emailIndex = null;
    foreach ($toUpdate as $i => $t) {
        if (strpos($t, 'email =') === 0) { $emailIndex = $i; break; }
    }
    if ($emailIndex !== null) {
        $newEmail = $params[$emailIndex];
        $s = $db->prepare('SELECT id FROM usuario WHERE email = ? AND id <> ?');
        $s->execute([$newEmail, $_SESSION['user_id']]);
        if ($s->fetchColumn()) {
            redirect('../profile.php?update_error=' . urlencode('Já existe uma conta com esse e-mail.'));
        }
    }
}

if (count($toUpdate) > 0) {
    $sql = 'UPDATE usuario SET ' . implode(', ', $toUpdate) . ' WHERE id = ?';
    $params[] = $_SESSION['user_id'];
    $upd = $db->prepare($sql);
    $upd->execute($params);
}

// Se for cuidador, atualizar/inserir perfil de cuidador com prefixo 'cuidador_'
$s = $db->prepare('SELECT tipo_usuario FROM usuario WHERE id = ?');
$s->execute([$_SESSION['user_id']]);
$tipo = $s->fetchColumn();
if ($tipo === 'cuidador') {
    // colunas de cuidador
    $cCols = [];
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cuidador'");
    $stmt->execute();
    while ($r = $stmt->fetch()) $cCols[] = $r['COLUMN_NAME'];
    $cblack = ['id','usuario_id','esta_verificado','criado_em'];
    $cToUpdate = [];
    $cParams = [];
    foreach ($cCols as $col) {
        if (in_array($col, $cblack)) continue;
        $postKey = 'cuidador_' . $col;
        if (array_key_exists($postKey, $_POST)) {
            $cToUpdate[] = $col . ' = ?';
            $cParams[] = trim($_POST[$postKey]);
        }
    }
    // existe perfil?
    $chk = $db->prepare('SELECT id FROM cuidador WHERE usuario_id = ?');
    $chk->execute([$_SESSION['user_id']]);
    $cid = $chk->fetchColumn();
    if ($cid) {
        if (count($cToUpdate) > 0) {
            $sql = 'UPDATE cuidador SET ' . implode(', ', $cToUpdate) . ' WHERE usuario_id = ?';
            $cParams[] = $_SESSION['user_id'];
            $u = $db->prepare($sql);
            $u->execute($cParams);
        }
    } else {
        // inserir
        if (count($cParams) > 0) {
            $colsIns = [];
            $placeholders = [];
            $vals = [];
            foreach ($cCols as $col) {
                if (in_array($col, $cblack)) continue;
                $postKey = 'cuidador_' . $col;
                if (array_key_exists($postKey, $_POST)) {
                    $colsIns[] = $col;
                    $placeholders[] = '?';
                    $vals[] = trim($_POST[$postKey]);
                }
            }
            // sempre incluir usuario_id
            array_unshift($colsIns, 'usuario_id');
            array_unshift($placeholders, '?');
            array_unshift($vals, $_SESSION['user_id']);
            $sql = 'INSERT INTO cuidador (' . implode(', ', $colsIns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $ins = $db->prepare($sql);
            $ins->execute($vals);
        }
    }
}

redirect('../profile.php?updated=1');
