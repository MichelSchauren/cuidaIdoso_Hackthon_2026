<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$idosoId = (int)($_GET['id'] ?? 0);
$idoso = pacienteDoUsuarioOuFalha($idosoId);

$sql = "SELECT m.*, 
  (SELECT h.id FROM historico_medicamento h WHERE h.medicamento_id = m.id ORDER BY h.horario_previsto DESC LIMIT 1) AS hist_id,
  (SELECT h.horario_previsto FROM historico_medicamento h WHERE h.medicamento_id = m.id ORDER BY h.horario_previsto DESC LIMIT 1) AS proxima_dose,
  (SELECT h.status FROM historico_medicamento h WHERE h.medicamento_id = m.id ORDER BY h.horario_previsto DESC LIMIT 1) AS status
  FROM medicamento m WHERE m.idoso_id = ? ORDER BY m.id";
$stmt = getDB()->prepare($sql);
$stmt->execute([$idosoId]);
$meds = $stmt->fetchAll();

$statusColor = ['administrado' => '#2A7A5B', 'pendente' => '#E07B54', 'atrasado' => '#c0392b'];
$statusLabel = ['administrado' => 'Administrado', 'pendente' => 'Pendente', 'atrasado' => 'Atrasado'];

$showForm = isset($_GET['novo']);

$pageTitle = 'Medicamentos';
$headerTitle = 'Medicamentos';
$headerSubtitle = $idoso['nome_completo'];
$headerBack = 'patient.php?id=' . $idosoId;
$toggleHref = $showForm ? 'medications.php?id=' . $idosoId : 'medications.php?id=' . $idosoId . '&novo=1#form';
$headerRightHtml = '<a href="' . e($toggleHref) . '" class="btn-icon-round' . ($showForm ? ' active' : '') . '">'
    . '<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="' . ($showForm ? 'white' : 'var(--foreground)') . '" stroke-width="2.5" stroke-linecap="round"/></svg>'
    . '</a>';

require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/includes/header_component.php';
?>
<div class="app-body" style="padding:0 20px 24px;">

  <?php if ($showForm): ?>
    <div class="card" id="form" style="margin-bottom:16px;">
      <h3 class="serif font-semibold text-sm" style="margin-bottom:12px;">Novo medicamento</h3>
      <form method="post" action="actions/add_medication.php">
        <input type="hidden" name="idoso_id" value="<?= $idosoId ?>">
        <div class="field field-compact">
          <label>Nome</label>
          <input type="text" name="nome" placeholder="Ex: Donepezila" required>
        </div>
        <div class="field field-compact">
          <label>Dosagem</label>
          <input type="text" name="dosagem" placeholder="Ex: 5 mg — 1 comprimido">
        </div>
        <div class="field field-compact">
          <label>Intervalo (horas)</label>
          <input type="number" name="intervalo_horas" placeholder="8" value="8">
        </div>
        <div class="field field-compact">
          <label>Instruções</label>
          <input type="text" name="instrucoes" placeholder="Ex: Tomar após o almoço">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Salvar medicamento</button>
      </form>
    </div>
  <?php endif; ?>

  <!-- Resumo -->
  <div class="flex gap-2" style="margin-bottom:16px;">
    <?php foreach (['pendente', 'administrado', 'atrasado'] as $s):
      $qtd = count(array_filter($meds, fn($m) => $m['status'] === $s));
    ?>
      <div class="flex-1 text-center" style="border-radius:12px;padding:8px 0;background-color:<?= $statusColor[$s] ?>18;">
        <p class="serif font-bold" style="font-size:16px;color:<?= $statusColor[$s] ?>;"><?= $qtd ?></p>
        <p style="font-size:10px;color:<?= $statusColor[$s] ?>;"><?= $statusLabel[$s] ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Lista -->
  <div class="flex flex-col gap-3">
    <?php if (empty($meds)): ?>
      <p class="text-xs text-muted text-center" style="padding:24px 0;">Nenhum medicamento cadastrado.</p>
    <?php endif; ?>
    <?php foreach ($meds as $med): ?>
      <div class="card" style="<?= $med['status'] === 'atrasado' ? 'border-color:#c0392b40;' : '' ?>">
        <div class="flex items-center gap-2" style="margin-bottom:4px;">
          <span class="badge" style="background-color:<?= $statusColor[$med['status']] ?>18;color:<?= $statusColor[$med['status']] ?>;"><?= $statusLabel[$med['status']] ?></span>
          <span class="text-xs text-muted">Próxima: <?= e($med['proxima_dose']) ?></span>
        </div>
        <h4 class="font-semibold text-sm"><?= e($med['nome']) ?></h4>
        <p class="text-xs text-muted" style="margin-top:2px;"><?= e($med['dosagem']) ?> · A cada <?= (int)$med['intervalo_horas'] ?>h</p>
        <?php if ($med['instrucoes']): ?>
          <p class="text-xs text-muted" style="margin-top:4px;font-style:italic;"><?= e($med['instrucoes']) ?></p>
        <?php endif; ?>

        <div style="margin-top:12px;">
          <?php if ($med['status'] === 'administrado'): ?>
            <button type="button" class="btn btn-muted btn-sm" onclick="toggleMed(<?= (int)$med['hist_id'] ?>, <?= (int)$med['id'] ?>, <?= $idosoId ?>)">Desmarcar administração</button>
          <?php else: ?>
            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleMed(<?= (int)$med['hist_id'] ?>, <?= (int)$med['id'] ?>, <?= $idosoId ?>)">✓ Marcar como administrado</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
<script>
function toggleMed(histId, medId, idosoId){
  const form = new FormData();
  if(histId) form.append('hist_id', histId);
  if(medId) form.append('med_id', medId);
  form.append('idoso_id', idosoId);
  fetch('actions/toggle_medication_ajax.php', { method:'POST', body: form })
    .then(r => r.json())
    .then(data => {
      if(data && data.success){
        // reload to reflect changes simply
        location.reload();
      } else {
        alert(data.error || 'Erro ao atualizar medicação');
      }
    }).catch(err=>{ alert('Erro de rede'); });
}
</script>
