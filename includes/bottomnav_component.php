<?php
/**
 * Renderiza a navegação inferior.
 * Espera $activeTab definido: 'home' | 'search' | 'notifications' | 'profile'
 */
$unread = contarNaoLidas($_SESSION['user_id']);

$tabs = [
    'home' => ['label' => 'Pacientes', 'href' => 'home.php'],
    'search' => ['label' => 'Buscar', 'href' => 'search.php'],
    'notifications' => ['label' => 'Alertas', 'href' => 'notifications.php'],
    'profile' => ['label' => 'Perfil', 'href' => 'profile.php'],
];

function navIcon(string $id, bool $active): string
{
    $color = $active ? 'var(--primary)' : 'var(--muted-foreground)';
    $w = $active ? '2.2' : '1.8';
    switch ($id) {
        case 'home':
            return '<svg width="22" height="22" fill="none" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="' . $color . '" stroke-width="' . $w . '" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="' . $color . '" stroke-width="' . $w . '"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="' . $color . '" stroke-width="' . $w . '" stroke-linecap="round"/></svg>';
        case 'search':
            return '<svg width="22" height="22" fill="none" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke="' . $color . '" stroke-width="' . $w . '"/><path d="m21 21-4.35-4.35" stroke="' . $color . '" stroke-width="' . $w . '" stroke-linecap="round"/></svg>';
        case 'notifications':
            return '<svg width="22" height="22" fill="none" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="' . $color . '" stroke-width="' . $w . '" stroke-linecap="round"/><path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="' . $color . '" stroke-width="' . $w . '" stroke-linecap="round"/></svg>';
        case 'profile':
            return '<svg width="22" height="22" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke="' . $color . '" stroke-width="' . $w . '"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="' . $color . '" stroke-width="' . $w . '" stroke-linecap="round"/></svg>';
    }
    return '';
}
?>
<nav class="bottom-nav">
  <?php foreach ($tabs as $id => $tab): $isActive = ($activeTab === $id); ?>
    <a href="<?= e($tab['href']) ?>" class="nav-item<?= $isActive ? ' active' : '' ?>">
      <div class="nav-icon-wrap">
        <?= navIcon($id, $isActive) ?>
        <?php if ($id === 'notifications' && $unread > 0): ?>
          <span class="nav-badge"><?= $unread ?></span>
        <?php endif; ?>
      </div>
      <span><?= e($tab['label']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
