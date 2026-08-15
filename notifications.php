<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$stmt = getDB()->prepare('SELECT * FROM notificacao WHERE usuario_id = ? ORDER BY criado_em DESC');
$stmt->execute([$_SESSION['user_id']]);
$notifs = $stmt->fetchAll();

$unread = count(array_filter($notifs, fn($n) => !$n['lida']));

$tipoColor = ['medicamento' => '#c0392b', 'tarefa' => '#2A7A5B', 'chamado' => '#5B7ABA', 'sistema' => '#E07B54'];

function tipoIconSvg(string $tipo, string $color): string
{
    switch ($tipo) {
        case 'medicamento':
            return '<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/><path d="m8.5 8.5 7 7" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>';
        case 'tarefa':
            return '<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'chamado':
            return '<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="' . $color . '" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>';
        default:
            return '<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="' . $color . '" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>';
    }
}

$pageTitle = 'Notificações';
$headerTitle = 'Notificações';
$headerSubtitle = $unread > 0 ? "$unread não lidas" : 'Tudo em dia';
$headerBack = 'home.php';
$headerRightHtml = $unread > 0
    ? '<form method="post" action="actions/mark_all_notifications.php"><button type="submit" class="btn-sm" style="font-size:12px;font-weight:600;padding:6px 12px;border-radius:12px;background-color:var(--secondary);color:var(--primary);">Marcar todas</button></form>'
    : '';

require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/includes/header_component.php';
?>
<div class="app-body" style="padding:0 20px 24px;">

  <?php if (empty($notifs)): ?>
    <div class="flex flex-col items-center" style="padding:80px 0;">
      <div style="width:64px;height:64px;border-radius:999px;background-color:var(--muted);display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <svg width="28" height="28" fill="none" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="var(--muted-foreground)" stroke-width="2" stroke-linecap="round"/><path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="var(--muted-foreground)" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <p class="text-sm text-muted">Nenhuma notificação</p>
    </div>
  <?php else: ?>
    <div class="flex flex-col gap-2">
      <?php foreach ($notifs as $notif):
        $tipoVal = $notif['tipo'] ?? 'sistema';
        $cor = $tipoColor[$tipoVal] ?? $tipoColor['sistema'];
      ?>
        <form method="post" action="actions/mark_notification.php">
          <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
          <button type="submit" class="card" style="width:100%;text-align:left;background-color:<?= $notif['lida'] ? 'var(--card)' : 'var(--secondary)' ?>;border-color:<?= $notif['lida'] ? 'var(--border)' : 'var(--primary)' ?>;opacity:<?= $notif['lida'] ? '0.75' : '1' ?>;">
            <div class="flex items-start gap-3">
              <div class="shrink-0" style="width:40px;height:40px;border-radius:12px;background-color:<?= $cor ?>18;color:<?= $cor ?>;display:flex;align-items:center;justify-content:center;">
                <?= tipoIconSvg($tipoVal, $cor) ?>
              </div>
              <div class="flex-1">
                <div class="flex items-start justify-between gap-2">
                  <p class="text-sm font-semibold" style="line-height:1.3;"><?= e($notif['titulo']) ?></p>
                  <div class="flex items-center gap-1 shrink-0">
                    <?php if (!$notif['lida']): ?><div style="width:8px;height:8px;border-radius:999px;background-color:var(--primary);"></div><?php endif; ?>
                    <span class="text-xs text-muted"><?= formatarHora($notif['criado_em']) ?></span>
                  </div>
                </div>
                <p class="text-xs text-muted" style="margin-top:4px;line-height:1.5;"><?= e($notif['mensagem']) ?></p>
              </div>
            </div>
          </button>
        </form>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php $activeTab = 'notifications'; require __DIR__ . '/includes/bottomnav_component.php'; ?>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
