<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();

if ($user['tipo_usuario'] === 'responsavel') {
  $stmt = $db->prepare('SELECT * FROM idoso WHERE responsavel_id = ? ORDER BY id');
  $stmt->execute([$user['id']]);
  $idosos = $stmt->fetchAll();
} else {
  // Usuário é cuidador: listar idosos vinculados a este cuidador via cuidador_idoso
  // A query aceita dois formatos: quando ci.cuidador_id guarda direto o usuario.id,
  // ou quando guarda o cuidador.id (legacy). Fazemos LEFT JOIN em cuidador para mapear.
  $stmt = $db->prepare('SELECT i.*, u.nome_completo AS responsavel_nome
  FROM idoso i
  JOIN cuidador_idoso ci ON ci.idoso_id = i.id
  JOIN usuario u ON i.responsavel_id = u.id
  LEFT JOIN cuidador c ON c.id = ci.cuidador_id
  WHERE (ci.cuidador_id = ? OR c.usuario_id = ?) AND ci.status = ? ORDER BY i.id');
  $stmt->execute([$user['id'], $user['id'], 'ativo']);
  $idosos = $stmt->fetchAll();
}

$idosoIds = array_column($idosos, 'id');
$placeholders = $idosoIds ? implode(',', array_fill(0, count($idosoIds), '?')) : "''";

$tarefasHoje = 0;
$medsAtrasados = 0;
if ($idosoIds) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM tarefa WHERE idoso_id IN ($placeholders)");
    $stmt->execute($idosoIds);
    $tarefasHoje = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM historico_medicamento hm JOIN medicamento m ON hm.medicamento_id = m.id WHERE m.idoso_id IN ($placeholders) AND hm.status = 'atrasado'");
    $stmt->execute($idosoIds);
    $medsAtrasados = (int)$stmt->fetchColumn();
}

$unread = contarNaoLidas($user['id']);
$primeiroNome = explode(' ', $user['nome_completo'])[0];

$pageTitle = 'CuidarBem';
require __DIR__ . '/includes/layout_top.php';
?>
<div class="app-body">
  <div style="padding:48px 20px 8px;">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-sm text-muted">Bom dia,</p>
        <h1 class="serif font-semibold" style="font-size:24px;"><?= e($primeiroNome) ?> 👋</h1>
      </div>
      <?php if ($unread > 0): ?>
        <span class="badge" style="background-color:var(--accent);color:var(--accent-foreground);"><?= $unread ?> alertas</span>
      <?php endif; ?>
    </div>
  </div>

  <div style="padding:12px 20px;">
    <div class="grid grid-cols-3" style="gap:12px;">
      <div class="card" style="padding:12px;">
        <p class="serif font-bold" style="font-size:24px;color:var(--primary);"><?= count($idosos) ?></p>
        <p style="font-size:11px;color:var(--muted-foreground);margin-top:2px;">Pacientes</p>
      </div>
      <div class="card" style="padding:12px;">
        <p class="serif font-bold" style="font-size:24px;color:var(--accent);"><?= $tarefasHoje ?></p>
        <p style="font-size:11px;color:var(--muted-foreground);margin-top:2px;">Tarefas hoje</p>
      </div>
      <div class="card" style="padding:12px;">
        <p class="serif font-bold" style="font-size:16px;color:#c0392b;"><?= $medsAtrasados ?> atrasado<?= $medsAtrasados !== 1 ? 's' : '' ?></p>
        <p style="font-size:11px;color:var(--muted-foreground);margin-top:2px;">Medicamentos</p>
      </div>
    </div>
  </div>

  <div class="flex-1" style="padding:0 20px;">
    <div class="flex items-center justify-between" style="margin:8px 0 12px;">
      <h2 class="serif font-semibold" style="font-size:16px;">Seus pacientes</h2>
      <?php if ($user['tipo_usuario'] === 'responsavel'): ?>
        <a href="patient_registration.php" class="flex items-center gap-1" style="padding:6px 12px;border-radius:12px;font-size:12px;font-weight:600;background-color:var(--secondary);color:var(--primary);">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round"/></svg>
          Adicionar
        </a>
      <?php endif; ?>
    </div>

    <div class="flex flex-col gap-3" style="padding-bottom:16px;">
      <?php if (empty($idosos)): ?>
        <div class="card text-center" style="padding:32px 16px;color:var(--muted-foreground);">
          Nenhum paciente cadastrado ainda.<br>Toque em "Adicionar" para começar.
        </div>
      <?php endif; ?>

      <?php foreach ($idosos as $i => $idoso):
        $cor = AVATAR_CORES[$i % count(AVATAR_CORES)];
        $idade = calcularIdade($idoso['data_nascimento']);
      ?>
        <a href="patient.php?id=<?= (int)$idoso['id'] ?>" class="card card-shadow" style="display:block;">
          <div class="flex items-start gap-3">
            <div class="avatar" style="width:48px;height:48px;font-size:18px;background-color:<?= $cor ?>18;color:<?= $cor ?>;">
              <?= e(iniciais($idoso['nome_completo'])) ?>
            </div>
            <div class="flex-1">
              <div class="flex items-center justify-between gap-2">
                <p class="font-semibold text-sm truncate"><?= e($idoso['nome_completo']) ?></p>
                <span class="text-xs text-muted shrink-0"><?= $idade ?> anos</span>
              </div>
              <p class="text-xs text-muted line-clamp-2" style="margin-top:2px;"><?= e($idoso['condicoes_medicas']) ?></p>
              <?php if ($user['tipo_usuario'] === 'responsavel' && !empty($idoso['cuidador_atual'])): ?>
                <div class="flex items-center gap-1" style="margin-top:6px;">
                  <div style="width:16px;height:16px;border-radius:999px;background-color:var(--secondary);display:flex;align-items:center;justify-content:center;">
                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke="var(--primary)" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="var(--primary)" stroke-width="2" stroke-linecap="round"/></svg>
                  </div>
                  <span style="font-size:11px;color:var(--primary);"><?= e($idoso['cuidador_atual']) ?></span>
                </div>
              <?php elseif ($user['tipo_usuario'] === 'cuidador' && !empty($idoso['responsavel_nome'])): ?>
                <div class="flex items-center gap-1" style="margin-top:6px;">
                  <div style="width:16px;height:16px;border-radius:999px;background-color:var(--secondary);display:flex;align-items:center;justify-content:center;">
                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke="var(--primary)" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="var(--primary)" stroke-width="2" stroke-linecap="round"/></svg>
                  </div>
                  <span style="font-size:11px;color:var(--primary);">Responsável: <?= e($idoso['responsavel_nome']) ?></span>
                </div>
              <?php endif; ?>
            </div>
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" class="shrink-0"><path d="m9 18 6-6-6-6" stroke="var(--muted-foreground)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php $activeTab = 'home'; require __DIR__ . '/includes/bottomnav_component.php'; ?>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
