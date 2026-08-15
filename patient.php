<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$idosoId = (int)($_GET['id'] ?? 0);
$idoso = pacienteDoUsuarioOuFalha($idosoId);
$db = getDB();

$sql = "SELECT m.*, 
  (SELECT h.id FROM historico_medicamento h WHERE h.medicamento_id = m.id ORDER BY h.horario_previsto DESC LIMIT 1) AS hist_id,
  (SELECT h.horario_previsto FROM historico_medicamento h WHERE h.medicamento_id = m.id ORDER BY h.horario_previsto DESC LIMIT 1) AS proxima_dose,
  (SELECT h.status FROM historico_medicamento h WHERE h.medicamento_id = m.id ORDER BY h.horario_previsto DESC LIMIT 1) AS status
  FROM medicamento m WHERE m.idoso_id = ? ORDER BY m.id";
$stmt = $db->prepare($sql);
$stmt->execute([$idosoId]);
$meds = $stmt->fetchAll();

$stmt = $db->prepare('SELECT * FROM tarefa WHERE idoso_id = ? ORDER BY horario_previsto');
$stmt->execute([$idosoId]);
$tasks = $stmt->fetchAll();

// Tarefas do dia (hoje)
$stmt = $db->prepare("SELECT * FROM tarefa WHERE idoso_id = ? AND DATE(horario_previsto) = CURDATE() ORDER BY horario_previsto");
$stmt->execute([$idosoId]);
$tasksHoje = $stmt->fetchAll();

$stmt = $db->prepare('SELECT d.*, u.nome_completo AS cuidador_nome FROM diario d LEFT JOIN usuario u ON d.cuidador_id = u.id WHERE d.idoso_id = ? ORDER BY d.criado_em DESC LIMIT 1');
$stmt->execute([$idosoId]);
$ultimaEntrada = $stmt->fetch();

// Entradas do diário (últimas 20) para chat
$stmt = $db->prepare('SELECT d.*, u.nome_completo AS autor_nome FROM diario d LEFT JOIN usuario u ON d.cuidador_id = u.id WHERE d.idoso_id = ? ORDER BY d.criado_em DESC LIMIT 20');
$stmt->execute([$idosoId]);
$diarioEntries = array_reverse($stmt->fetchAll());

$tasksDone = count(array_filter($tasks, fn($t) => $t['status'] === 'concluida'));
$tasksTotal = count($tasks);
$progress = $tasksTotal > 0 ? ($tasksDone / $tasksTotal) * 100 : 0;

$medsAtrasados = count(array_filter($meds, fn($m) => $m['status'] === 'atrasado'));
$tasksPendentes = count(array_filter($tasks, fn($t) => $t['status'] === 'pendente'));

$categoryColors = ['administrado' => '#2A7A5B', 'pendente' => '#E07B54', 'atrasado' => '#c0392b'];
$categoryLabel = ['administrado' => 'Dado', 'pendente' => 'Pendente', 'atrasado' => 'Atrasado'];
$catDiarioLabel = ['sinais_vitais' => 'Sinais vitais', 'rotina' => 'Rotina', 'incidente' => 'Incidente', 'observacao' => 'Observação'];

$idade = calcularIdade($idoso['data_nascimento']);

$pageTitle = e($idoso['nome_completo']);
$headerTitle = $idoso['nome_completo'];
$headerSubtitle = $idade . ' anos';
$headerBack = 'home.php';
require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/includes/header_component.php';
?>
<div class="app-body" style="padding:0 20px 24px;">

  <!-- Card de informações -->
  <div class="card" style="background:linear-gradient(135deg, #2A7A5B 0%, #1d5941 100%);border:none;margin-bottom:16px;">
    <div class="flex items-center gap-3" style="margin-bottom:12px;">
      <div class="avatar" style="width:56px;height:56px;font-size:20px;background-color:rgba(255,255,255,0.2);color:white;">
        <?= e(iniciais($idoso['nome_completo'])) ?>
      </div>
      <div>
        <h3 class="font-semibold" style="color:white;font-size:16px;"><?= e($idoso['nome_completo']) ?></h3>
        <p class="text-sm" style="color:rgba(255,255,255,0.75);">Nasc.: <?= formatarDataBR($idoso['data_nascimento']) ?></p>
        <p class="text-sm" style="color:rgba(255,255,255,0.75);">Emerg.: <?= e($idoso['telefone_emergencia']) ?></p>
      </div>
    </div>
    <div style="background-color:rgba(255,255,255,0.12);border-radius:12px;padding:8px 12px;">
      <p class="text-xs" style="color:rgba(255,255,255,0.7);">Condições médicas</p>
      <p class="text-sm font-semibold" style="color:white;margin-top:2px;"><?= e($idoso['condicoes_medicas'] ?: 'Nenhuma registrada') ?></p>
    </div>
  </div>

  <!-- Progresso hoje -->
  <div class="card" style="margin-bottom:16px;">
    <div class="flex items-center justify-between" style="margin-bottom:8px;">
      <p class="text-sm font-semibold">Progresso hoje</p>
      <span id="tasks-counter" class="text-xs text-muted"><?= $tasksDone ?>/<?= $tasksTotal ?> tarefas</span>
    </div>
    <div class="progress-track"><div class="progress-fill" style="width:<?= $progress ?>%;"></div></div>
  </div>

  <!-- Tarefas do dia (inline) -->
  <div class="card" style="margin-bottom:16px;">
    <div class="flex items-center justify-between" style="margin-bottom:8px;">
      <p class="text-sm font-semibold">Tarefas de hoje</p>
      <a href="tasks.php?id=<?= $idosoId ?>" class="text-xs">Ver todas</a>
    </div>
    <div class="flex flex-col gap-2">
      <?php if (!empty($tasksHoje)): ?>
        <?php foreach ($tasksHoje as $t): ?>
          <div class="flex items-center justify-between" style="padding:8px 0;border-bottom:1px solid #f0f0f0;">
            <div>
              <div style="font-weight:600"><?= e($t['titulo']) ?></div>
              <div class="text-xs text-muted"><?= formatarHora($t['horario_previsto']) ?> — <?= e($t['descricao']) ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
              <span class="text-xs" id="task-status-<?= (int)$t['id'] ?>"><?= e($t['status']) ?></span>
              <button class="btn btn-sm" id="task-btn-<?= (int)$t['id'] ?>" data-status="<?= e($t['status']) ?>" onclick="toggleTask(<?= (int)$t['id'] ?>, <?= $idosoId ?>)">
                <span class="btn-label"><?= $t['status'] === 'pendente' ? 'Marcar concluída' : 'Marcar pendente' ?></span>
                <svg class="spinner" width="14" height="14" viewBox="0 0 50 50" style="display:none;margin-left:6px;vertical-align:middle;">
                  <circle cx="25" cy="25" r="20" stroke="#fff" stroke-width="5" fill="none" stroke-linecap="round" stroke-dasharray="31.4 31.4">
                    <animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.9s" repeatCount="indefinite" />
                  </circle>
                </svg>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-xs text-muted">Nenhuma tarefa agendada para hoje.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ações rápidas -->
  <div class="grid grid-cols-2" style="gap:12px;margin-bottom:16px;">
    <a href="medications.php?id=<?= $idosoId ?>" class="card" style="display:block;">
      <div style="width:40px;height:40px;border-radius:12px;background-color:var(--secondary);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z" stroke="var(--primary)" stroke-width="2" stroke-linecap="round"/><path d="m8.5 8.5 7 7" stroke="var(--primary)" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <p class="font-semibold text-sm">Medicamentos</p>
      <?php if ($medsAtrasados > 0): ?><p class="text-xs" style="color:var(--accent);margin-top:2px;"><?= $medsAtrasados ?> atrasados</p><?php endif; ?>
    </a>
    <a href="tasks.php?id=<?= $idosoId ?>" class="card" style="display:block;">
      <div style="width:40px;height:40px;border-radius:12px;background-color:var(--secondary);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <p class="font-semibold text-sm">Tarefas</p>
      <?php if ($tasksPendentes > 0): ?><p class="text-xs" style="color:var(--accent);margin-top:2px;"><?= $tasksPendentes ?> pendentes</p><?php endif; ?>
    </a>
  </div>

  <!-- Medicamentos rápido -->
  <div style="margin-bottom:16px;">
    <div class="flex items-center justify-between" style="margin-bottom:8px;">
      <h3 class="serif font-semibold text-sm">Medicamentos</h3>
      <a href="medications.php?id=<?= $idosoId ?>" class="text-xs text-primary">Ver todos</a>
    </div>
    <div class="flex flex-col gap-2">
      <?php foreach (array_slice($meds, 0, 2) as $med): ?>
        <div class="flex items-center gap-3 card" style="padding:10px 12px;">
          <div style="width:8px;height:8px;border-radius:999px;background-color:<?= $categoryColors[$med['status']] ?>;" class="shrink-0"></div>
          <div class="flex-1">
            <p class="text-sm font-semibold truncate"><?= e($med['nome']) ?></p>
            <p class="text-xs text-muted"><?= e($med['dosagem']) ?></p>
          </div>
          <span class="badge shrink-0" style="background-color:<?= $categoryColors[$med['status']] ?>18;color:<?= $categoryColors[$med['status']] ?>;"><?= $categoryLabel[$med['status']] ?></span>
          <?php if ($med['status'] !== 'administrado'): ?>
            <form method="post" action="actions/mark_medication.php" style="margin-left:8px;">
              <input type="hidden" name="med_id" value="<?= (int)$med['id'] ?>">
              <input type="hidden" name="idoso_id" value="<?= $idosoId ?>">
              <button type="submit" class="btn btn-sm" style="margin-left:8px;">Marcar como dado</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if (empty($meds)): ?><p class="text-xs text-muted">Nenhum medicamento cadastrado.</p><?php endif; ?>
    </div>
  </div>

  <!-- Diário rápido -->
  <div>
    <div class="flex items-center justify-between" style="margin-bottom:8px;">
      <h3 class="serif font-semibold text-sm">Diário de bordo</h3>
      <a href="diary.php?id=<?= $idosoId ?>" class="text-xs text-primary">Ver tudo</a>
    </div>
    <div class="card" style="margin-bottom:12px;padding:12px;">
      <div style="max-height:240px;overflow:auto;">
        <?php if (!empty($diarioEntries)): ?>
          <?php foreach ($diarioEntries as $entry): ?>
            <div style="margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid #f0f0f0;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                <div style="font-weight:600"><?= e($entry['autor_nome'] ?? 'Sistema') ?></div>
                <div class="text-xs text-muted"><?= formatarDataCurta($entry['criado_em']) ?> <?= formatarHora($entry['criado_em']) ?></div>
              </div>
              <div style="font-size:14px;line-height:1.4;"><?= e($entry['mensagem']) ?></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-xs text-muted">Nenhuma entrada registrada ainda.</p>
        <?php endif; ?>
      </div>

      <form method="post" action="actions/add_diary_inline.php" style="margin-top:12px;">
        <input type="hidden" name="idoso_id" value="<?= $idosoId ?>">
        <div class="field">
          <label>Categoria</label>
          <select name="categoria">
            <option value="rotina">Rotina</option>
            <option value="observacao">Observação</option>
            <option value="incidente">Incidente</option>
            <option value="sinais_vitais">Sinais vitais</option>
          </select>
        </div>
        <div class="field">
          <label>Mensagem</label>
          <textarea name="mensagem" rows="3" required></textarea>
        </div>
        <div style="text-align:right;margin-top:8px;">
          <button type="submit" class="btn btn-primary">Enviar</button>
        </div>
      </form>
    </div>
  </div>

</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>

<script>
function toggleTask(taskId, idosoId) {
  const form = new FormData();
  form.append('task_id', taskId);
  form.append('idoso_id', idosoId);

  const btn = document.getElementById('task-btn-' + taskId);
  const label = btn ? btn.querySelector('.btn-label') : null;
  const spinner = btn ? btn.querySelector('.spinner') : null;
  if (btn) { btn.disabled = true; if (spinner) spinner.style.display = 'inline-block'; }

  fetch('actions/toggle_task_ajax.php', { method: 'POST', body: form })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        alert('Erro ao atualizar tarefa');
        return;
      }
      // atualizar status texto
      const statusEl = document.getElementById('task-status-' + taskId);
      if (statusEl) statusEl.textContent = data.newStatus;
      // atualizar rótulo do botão
      if (label) label.textContent = data.newStatus === 'concluida' ? 'Marcar pendente' : 'Marcar concluída';
      // atualizar progresso visual e contador
      const fill = document.querySelector('.progress-fill');
      const percent = data.tasksTotal > 0 ? (data.tasksDone / data.tasksTotal) * 100 : 0;
      if (fill) fill.style.width = percent + '%';
      const counter = document.getElementById('tasks-counter');
      if (counter) counter.textContent = data.tasksDone + '/' + data.tasksTotal + ' tarefas';
    }).catch(err => {
      alert('Erro de rede');
    }).finally(() => {
      if (btn) { btn.disabled = false; if (spinner) spinner.style.display = 'none'; }
    });
}
</script>
