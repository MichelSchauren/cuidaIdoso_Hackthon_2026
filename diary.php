<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$idosoId = (int)($_GET['id'] ?? 0);
$idoso = pacienteDoUsuarioOuFalha($idosoId);

$stmt = getDB()->prepare('SELECT d.*, u.nome_completo AS cuidador_nome FROM diario d LEFT JOIN usuario u ON d.cuidador_id = u.id WHERE d.idoso_id = ? ORDER BY d.criado_em DESC');
$stmt->execute([$idosoId]);
$entries = $stmt->fetchAll();

$categoriaLabel = ['rotina' => 'Rotina', 'incidente' => 'Incidente', 'observacao' => 'Observação', 'sinais_vitais' => 'Sinais vitais'];
$categoriaColor = ['rotina' => '#2A7A5B', 'incidente' => '#c0392b', 'observacao' => '#5B7ABA', 'sinais_vitais' => '#8B5BAA'];

$showForm = isset($_GET['novo']);

$pageTitle = 'Diário de bordo';
$headerTitle = 'Diário de bordo';
$headerSubtitle = $idoso['nome_completo'];
$headerBack = 'patient.php?id=' . $idosoId;
$toggleHref = $showForm ? 'diary.php?id=' . $idosoId : 'diary.php?id=' . $idosoId . '&novo=1#form';
$headerRightHtml = '<a href="' . e($toggleHref) . '" class="btn-icon-round' . ($showForm ? ' active' : '') . '">'
    . '<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="' . ($showForm ? 'white' : 'var(--foreground)') . '" stroke-width="2.5" stroke-linecap="round"/></svg>'
    . '</a>';

require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/includes/header_component.php';
?>
<div class="app-body" style="padding:0 20px 24px;">

  <?php if ($showForm): ?>
    <div class="card" id="form" style="margin-bottom:16px;">
      <h3 class="serif font-semibold text-sm" style="margin-bottom:12px;">Nova entrada</h3>
      <form method="post" action="actions/add_diary.php">
        <input type="hidden" name="idoso_id" value="<?= $idosoId ?>">
        <div class="scroll-x" style="margin-bottom:12px;">
          <?php foreach ($categoriaLabel as $cat => $label): ?>
            <label class="chip" style="background-color:<?= $categoriaColor[$cat] ?>18;color:<?= $categoriaColor[$cat] ?>;cursor:pointer;">
              <input type="radio" name="categoria" value="<?= $cat ?>" <?= $cat === 'rotina' ? 'checked' : '' ?> style="display:none;">
              <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="field field-compact">
          <textarea name="mensagem" rows="4" placeholder="Descreva a situação com detalhes..." required></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Registrar entrada</button>
      </form>
    </div>
  <?php endif; ?>

  <!-- Chips de categorias (apenas visual, como no original) -->
  <div class="scroll-x" style="margin-bottom:16px;">
    <span class="chip" style="background-color:var(--primary);color:white;">Todas</span>
    <?php foreach ($categoriaLabel as $cat => $label): ?>
      <span class="chip" style="background-color:<?= $categoriaColor[$cat] ?>18;color:<?= $categoriaColor[$cat] ?>;"><?= $label ?></span>
    <?php endforeach; ?>
  </div>

  <!-- Entradas -->
  <div class="flex flex-col gap-3">
    <?php if (empty($entries)): ?>
      <p class="text-xs text-muted text-center" style="padding:24px 0;">Nenhuma entrada registrada ainda.</p>
    <?php endif; ?>
    <?php foreach ($entries as $i => $entry):
      $cor = AVATAR_CORES[$i % count(AVATAR_CORES)];
      $cat = $entry['categoria'];
    ?>
      <div class="card">
        <div class="flex items-start gap-3">
          <div class="avatar avatar-round" style="width:36px;height:36px;font-size:12px;background-color:<?= $cor ?>22;color:<?= $cor ?>;">
            <?= e(iniciais($entry['cuidador_nome'] ?? '')) ?>
          </div>
          <div class="flex-1">
            <div class="flex items-center justify-between gap-2" style="margin-bottom:4px;">
              <p class="text-xs font-semibold"><?= e($entry['cuidador_nome'] ?? '') ?></p>
              <span class="text-xs text-muted"><?= formatarHora($entry['criado_em']) ?></span>
            </div>
            <span class="badge" style="background-color:<?= $categoriaColor[$cat] ?>18;color:<?= $categoriaColor[$cat] ?>;margin-bottom:8px;"><?= $categoriaLabel[$cat] ?></span>
            <p class="text-sm" style="line-height:1.5;"><?= nl2br(e($entry['mensagem'])) ?></p>
            <p class="text-xs text-muted" style="margin-top:8px;"><?= formatarDataCurta($entry['criado_em']) ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
