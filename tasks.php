<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$idosoId = (int)($_GET['id'] ?? 0);
$idoso = pacienteDoUsuarioOuFalha($idosoId);

$stmt = getDB()->prepare('SELECT * FROM tarefa WHERE idoso_id = ? ORDER BY horario_previsto');
$stmt->execute([$idosoId]);
$tasks = $stmt->fetchAll();

$statusColor = ['concluida' => '#2A7A5B', 'pendente' => '#E07B54', 'cancelada' => '#888'];
$done = count(array_filter($tasks, fn($t) => $t['status'] === 'concluida'));
$total = count($tasks);
$progress = $total > 0 ? ($done / $total) * 100 : 0;

$showForm = isset($_GET['novo']);

$pageTitle = 'Tarefas';
$headerTitle = 'Tarefas';
$headerSubtitle = $idoso['nome_completo'];
$headerBack = 'patient.php?id=' . $idosoId;
$toggleHref = $showForm ? 'tasks.php?id=' . $idosoId : 'tasks.php?id=' . $idosoId . '&novo=1#form';
$headerRightHtml = '<a href="' . e($toggleHref) . '" class="btn-icon-round' . ($showForm ? ' active' : '') . '">'
    . '<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="' . ($showForm ? 'white' : 'var(--foreground)') . '" stroke-width="2.5" stroke-linecap="round"/></svg>'
    . '</a>';

require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/includes/header_component.php';
?>
<div class="app-body" style="padding:0 20px 24px;">

  <?php if ($showForm): ?>
    <div class="card" id="form" style="margin-bottom:16px;">
      <h3 class="serif font-semibold text-sm" style="margin-bottom:12px;">Nova tarefa</h3>
      <form method="post" action="actions/add_task.php">
        <input type="hidden" name="idoso_id" value="<?= $idosoId ?>">
        <div class="field field-compact">
          <input type="text" name="titulo" placeholder="Título da tarefa" required>
        </div>
        <div class="field field-compact">
          <input type="text" name="descricao" placeholder="Descrição (opcional)">
        </div>
        <div class="field field-compact">
          <input type="time" name="horario">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Salvar tarefa</button>
      </form>
    </div>
  <?php endif; ?>

  <!-- Progresso -->
  <div class="card" style="margin-bottom:16px;">
    <div class="flex items-center justify-between" style="margin-bottom:8px;">
      <p class="text-sm font-semibold">Progresso do dia</p>
      <p class="serif font-bold text-sm text-primary"><?= $done ?>/<?= $total ?></p>
    </div>
    <div class="progress-track"><div class="progress-fill" style="width:<?= $progress ?>%;"></div></div>
  </div>

  <!-- Lista de tarefas -->
  <div class="flex flex-col gap-2">
    <?php if (empty($tasks)): ?>
      <p class="text-xs text-muted text-center" style="padding:24px 0;">Nenhuma tarefa cadastrada.</p>
    <?php endif; ?>
    <?php foreach ($tasks as $task):
      $concluida = $task['status'] === 'concluida';
    ?>
      <div class="flex items-start gap-3 card" style="opacity:<?= $task['status'] === 'cancelada' ? '0.5' : '1' ?>;">
        <form method="post" action="actions/toggle_task.php">
          <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
          <input type="hidden" name="idoso_id" value="<?= $idosoId ?>">
          <button type="submit" class="shrink-0" style="width:24px;height:24px;border-radius:999px;border:2px solid <?= $concluida ? 'var(--primary)' : 'var(--border)' ?>;background-color:<?= $concluida ? 'var(--primary)' : 'transparent' ?>;display:flex;align-items:center;justify-content:center;margin-top:2px;">
            <?php if ($concluida): ?>
              <svg width="12" height="12" fill="none" viewBox="0 0 24 24"><path d="m5 12 5 5L20 7" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?php endif; ?>
          </button>
        </form>
        <div class="flex-1">
          <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-semibold" style="color:<?= $concluida ? 'var(--muted-foreground)' : 'var(--foreground)' ?>;text-decoration:<?= $concluida ? 'line-through' : 'none' ?>;">
              <?= e($task['titulo']) ?>
            </p>
            <span class="text-xs font-semibold shrink-0" style="color:<?= $statusColor[$task['status']] ?>;"><?= e($task['horario_previsto']) ?></span>
          </div>
          <?php if ($task['descricao']): ?>
            <p class="text-xs text-muted line-clamp-2" style="margin-top:2px;"><?= e($task['descricao']) ?></p>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
