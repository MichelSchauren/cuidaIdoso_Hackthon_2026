<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$search = trim($_GET['q'] ?? '');

$user = currentUser();

// Variáveis de modal (sempre definidas para evitar warnings)
$abrirModalId = (int)($_GET['contratar'] ?? 0);
$enviadoId = (int)($_GET['enviado'] ?? 0);

// Garantir variáveis usadas na view estejam definidas
$cuidadores = [];
$idosos = [];
$contratados = [];

// Se for cuidador, mostrar a lista de chamados pendentes para este cuidador
if ($user && $user['tipo_usuario'] === 'cuidador') {
  $stmt = $db->prepare("SELECT ch.*, u.nome_completo AS responsavel_nome, ido.nome_completo AS idoso_nome
    FROM chamado ch
    JOIN usuario u ON ch.responsavel_id = u.id
    JOIN idoso ido ON ch.idoso_id = ido.id
    WHERE ch.cuidador_id = ? AND ch.status = 'pendente'
    ORDER BY ch.criado_em DESC");
  $stmt->execute([$_SESSION['user_id']]);
  $chamados = $stmt->fetchAll();

  $pageTitle = 'Chamados';
} else {
  if ($search !== '') {
    $stmt = $db->prepare("SELECT c.id, u.nome_completo, c.valor_hora, c.especialidades, c.esta_verificado AS verificado, 0 AS distancia_km, 0 AS avaliacao, 0 AS total_avaliacoes, c.biografia, 1 AS disponivel FROM cuidador c JOIN usuario u ON c.usuario_id = u.id WHERE LOWER(u.nome_completo) LIKE ? OR LOWER(c.especialidades) LIKE ? ORDER BY distancia_km");
    $toLower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $like = '%' . $toLower($search) . '%';
    $stmt->execute([$like, $like]);
  } else {
    $stmt = $db->query('SELECT c.id, u.nome_completo, c.valor_hora, c.especialidades, c.esta_verificado AS verificado, 0 AS distancia_km, 0 AS avaliacao, 0 AS total_avaliacoes, c.biografia, 1 AS disponivel FROM cuidador c JOIN usuario u ON c.usuario_id = u.id ORDER BY distancia_km');
  }
  $cuidadores = $stmt->fetchAll();

  // pacientes do usuário, para o formulário de contratação
  $stmt = $db->prepare('SELECT id, nome_completo FROM idoso WHERE responsavel_id = ? ORDER BY nome_completo');
  $stmt->execute([$_SESSION['user_id']]);
  $idosos = $stmt->fetchAll();

  // cuidadores já contratados (pendente/aceito) pelo usuário
  $stmt = $db->prepare('SELECT DISTINCT cuidador_id FROM chamado WHERE responsavel_id = ?');
  $stmt->execute([$_SESSION['user_id']]);
  $contratados = array_column($stmt->fetchAll(), 'cuidador_id');

  $abrirModalId = (int)($_GET['contratar'] ?? 0);
  $enviadoId = (int)($_GET['enviado'] ?? 0);

  $pageTitle = 'Buscar cuidadores';
}
require __DIR__ . '/includes/layout_top.php';

if ($user && $user['tipo_usuario'] === 'cuidador') {
  $headerTitle = 'Chamados';
  $headerSubtitle = 'Solicitações pendentes';
} else {
  $headerTitle = 'Buscar cuidadores';
  $headerSubtitle = 'Próximos a você';
}
$headerBack = 'home.php';
require __DIR__ . '/includes/header_component.php';
?>
<div style="padding:0 20px 12px;">
  <form method="get" class="flex items-center gap-2 card" style="padding:12px 16px;">
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke="var(--muted-foreground)" stroke-width="2"/><path d="m21 21-4.35-4.35" stroke="var(--muted-foreground)" stroke-width="2" stroke-linecap="round"/></svg>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por nome ou especialidade..." class="flex-1" style="border:none;outline:none;background:transparent;font-size:14px;">
  </form>
</div>

<div class="app-body" style="padding:0 20px 24px;">
  <?php if ($user && $user['tipo_usuario'] === 'cuidador'): ?>
    <p class="text-xs text-muted" style="margin-bottom:12px;"><?= count($chamados) ?> chamado<?= count($chamados) !== 1 ? 's' : '' ?> pendente<?= count($chamados) !== 1 ? 's' : '' ?></p>

    <div class="flex flex-col gap-3">
      <?php foreach ($chamados as $ch): ?>
        <div class="card">
          <div class="flex items-start gap-3">
            <div class="avatar avatar-round" style="width:48px;height:48px;font-size:16px;background-color:#2A7A5B22;color:#2A7A5B;"><?= e(iniciais($ch['responsavel_nome'])) ?></div>
            <div class="flex-1">
              <div class="flex items-start justify-between">
                <div>
                  <p class="font-semibold"><?= e($ch['responsavel_nome']) ?></p>
                  <p class="text-xs text-muted">Paciente: <?= e($ch['idoso_nome']) ?></p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-muted">Início: <?= e($ch['data_inicio']) ?></p>
                  <p class="text-xs text-muted">Fim: <?= e($ch['data_fim']) ?></p>
                </div>
              </div>

              <div style="margin-top:8px;">
                <form method="post" action="actions/respond_chamado.php" style="display:inline-block;margin-right:8px;">
                  <input type="hidden" name="chamado_id" value="<?= (int)$ch['id'] ?>">
                  <input type="hidden" name="acao" value="aceitar">
                  <button type="submit" class="btn btn-primary btn-sm">Aceitar</button>
                </form>
                <form method="post" action="actions/respond_chamado.php" style="display:inline-block;">
                  <input type="hidden" name="chamado_id" value="<?= (int)$ch['id'] ?>">
                  <input type="hidden" name="acao" value="recusar">
                  <button type="submit" class="btn btn-muted btn-sm">Recusar</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($chamados)): ?>
        <p class="text-xs text-muted text-center" style="padding:24px 0;">Nenhum chamado pendente.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <p class="text-xs text-muted" style="margin-bottom:12px;">
      <?= count($cuidadores) ?> cuidador<?= count($cuidadores) !== 1 ? 'es' : '' ?> encontrado<?= count($cuidadores) !== 1 ? 's' : '' ?>
    </p>

    <div class="flex flex-col gap-3">
      <?php foreach ($cuidadores as $i => $c):
        $cor = AVATAR_CORES[$i % count(AVATAR_CORES)];
        $jaContratado = in_array($c['id'], $contratados, true);
        $indisponivel = !$c['disponivel'];
      ?>
        <div class="card" style="opacity:<?= $indisponivel ? '0.6' : '1' ?>;">
          <div class="flex items-start gap-3">
              <div class="avatar avatar-round" style="width:48px;height:48px;font-size:16px;background-color:<?= $cor ?>22;color:<?= $cor ?>;">
              <?= e(iniciais($c['nome_completo'])) ?>
            </div>
            <div class="flex-1">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-2">
                    <p class="font-semibold text-sm"><?= e($c['nome_completo']) ?></p>
                    <?php if ($c['verificado']): ?>
                      <span class="badge" style="font-size:10px;background-color:#2A7A5B18;color:#2A7A5B;">✓ Verificado</span>
                    <?php endif; ?>
                  </div>
                  <div class="flex items-center gap-1" style="margin-top:2px;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="<?= $s <= round($c['avaliacao']) ? '#F59E0B' : 'none' ?>"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php endfor; ?>
                    <span class="text-xs text-muted" style="margin-left:4px;"><?= number_format($c['avaliacao'], 1) ?></span>
                  </div>
                  <p class="text-xs text-muted" style="margin-top:2px;"><?= (int)$c['total_avaliacoes'] ?> avaliações</p>
                </div>
                <div class="text-right shrink-0">
                  <p class="serif font-bold text-sm text-primary">R$ <?= (int)$c['valor_hora'] ?>/h</p>
                  <p class="text-xs text-muted"><?= $c['distancia_km'] ?> km</p>
                </div>
              </div>

              <p class="text-xs text-muted line-clamp-2" style="margin-top:8px;line-height:1.5;"><?= e($c['biografia']) ?></p>

              <div class="flex" style="flex-wrap:wrap;gap:4px;margin-top:8px;">
                <?php foreach (explode(',', $c['especialidades']) as $esp): ?>
                  <span class="chip" style="font-size:10px;padding:2px 8px;background-color:var(--secondary);color:var(--secondary-foreground);"><?= e(trim($esp)) ?></span>
                <?php endforeach; ?>
              </div>

              <?php if ($jaContratado): ?>
                <button disabled class="btn btn-secondary btn-sm" style="margin-top:12px;">✓ Solicitação enviada</button>
              <?php elseif ($indisponivel): ?>
                <button disabled class="btn btn-muted btn-sm" style="margin-top:12px;color:var(--muted-foreground);">Indisponível no momento</button>
              <?php else: ?>
                <a href="search.php?contratar=<?= $c['id'] ?><?= $search ? '&q=' . urlencode($search) : '' ?>#contratar-<?= $c['id'] ?>" class="btn btn-primary btn-sm" style="margin-top:12px;display:block;text-align:center;">Solicitar contratação</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (empty($cuidadores)): ?>
        <p class="text-xs text-muted text-center" style="padding:24px 0;">Nenhum cuidador encontrado.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php $activeTab = 'search'; require __DIR__ . '/includes/bottomnav_component.php'; ?>

<?php
// Modal de confirmação de contratação
if ($abrirModalId > 0):
    $cuidador = null;
    foreach ($cuidadores as $c) { if ($c['id'] === $abrirModalId) { $cuidador = $c; break; } }
    if (!$cuidador) {
      $stmt = $db->prepare('SELECT c.id, u.nome_completo, c.valor_hora, c.especialidades, c.esta_verificado AS verificado, c.biografia FROM cuidador c JOIN usuario u ON c.usuario_id = u.id WHERE c.id = ?');
      $stmt->execute([$abrirModalId]);
      $cuidador = $stmt->fetch();
    }
?>
  <div class="modal-overlay" id="contratar-<?= $abrirModalId ?>">
    <div class="modal-sheet">
      <?php if ($cuidador): ?>
        <div class="flex items-center gap-3" style="margin-bottom:20px;">
            <div class="avatar avatar-round" style="width:48px;height:48px;font-size:16px;background-color:#2A7A5B22;color:#2A7A5B;">
            <?= e(iniciais($cuidador['nome_completo'])) ?>
          </div>
          <div>
            <h3 class="serif font-semibold" style="font-size:16px;">Contratar <?= e($cuidador['nome_completo']) ?></h3>
            <p class="text-sm text-muted">R$ <?= (int)$cuidador['valor_hora'] ?>/hora</p>
          </div>
        </div>

        <form method="post" action="actions/hire_caregiver.php">
          <input type="hidden" name="cuidador_id" value="<?= $cuidador['id'] ?>">

          <div class="field">
            <label>Paciente</label>
            <select name="idoso_id" required>
              <?php foreach ($idosos as $idoso): ?>
                <option value="<?= $idoso['id'] ?>"><?= e($idoso['nome_completo']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="grid grid-cols-2" style="gap:12px;">
            <div class="field">
              <label>Início</label>
              <input type="date" name="data_inicio" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            </div>
            <div class="field">
              <label>Fim</label>
              <input type="date" name="data_fim" value="<?= date('Y-m-d', strtotime('+5 day')) ?>" required>
            </div>
          </div>

          <div class="flex gap-3" style="margin-top:8px;">
            <a href="search.php<?= $search ? '?q=' . urlencode($search) : '' ?>" class="btn btn-muted" style="flex:1;">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="flex:1;">Enviar solicitação</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php elseif ($enviadoId > 0):
    $stmt = $db->prepare('SELECT u.nome_completo FROM cuidador c JOIN usuario u ON c.usuario_id = u.id WHERE c.id = ?');
    $stmt->execute([$enviadoId]);
    $nomeCuidador = $stmt->fetchColumn();
?>
  <div class="modal-overlay">
    <div class="modal-sheet flex flex-col items-center" style="padding-top:32px;">
      <div style="width:64px;height:64px;border-radius:999px;background-color:var(--secondary);display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <svg width="32" height="32" fill="none" viewBox="0 0 24 24"><path d="m5 12 5 5L20 7" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <h3 class="serif font-semibold" style="font-size:20px;margin-bottom:8px;">Solicitação enviada!</h3>
      <p class="text-sm text-center text-muted" style="margin-bottom:20px;"><?= e($nomeCuidador) ?> receberá sua solicitação e poderá aceitar ou recusar em breve.</p>
      <a href="search.php" class="btn btn-primary">Entendi</a>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
