<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user = currentUser();

$pageTitle = 'Meu perfil';
$headerTitle = 'Meu perfil';
$headerBack = 'home.php';
require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/includes/header_component.php';

$db = getDB();
// colunas da tabela usuario
$usuarioCols = [];
$stmtCols = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario'");
$stmtCols->execute();
while ($r = $stmtCols->fetch()) $usuarioCols[] = $r['COLUMN_NAME'];
// se for cuidador, buscar perfil de cuidador e colunas
$cuidador = null;
$cuidadorCols = [];
if (($user['tipo_usuario'] ?? '') === 'cuidador') {
  $s = $db->prepare('SELECT * FROM cuidador WHERE usuario_id = ?');
  $s->execute([$user['id']]);
  $cuidador = $s->fetch();
  $stmtCols = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cuidador'");
  $stmtCols->execute();
  while ($r = $stmtCols->fetch()) $cuidadorCols[] = $r['COLUMN_NAME'];
}
?>
<div class="app-body" style="padding:0 20px 32px;">

  <?php if (isset($_GET['pass_changed'])): ?>
    <div class="success-box" style="margin:12px 0;padding:12px;border-radius:8px;background:#E6FFEF;color:#1B6A3A;">Senha alterada com sucesso.</div>
  <?php endif; ?>
  <?php if (isset($_GET['pass_error'])): ?>
    <div class="error-box" style="margin:12px 0;"><?= e($_GET['pass_error']) ?></div>
  <?php endif; ?>
  <?php if (isset($_GET['updated'])): ?>
    <div class="success-box" style="margin:12px 0;padding:12px;border-radius:8px;background:#E6FFEF;color:#1B6A3A;">Dados atualizados com sucesso.</div>
  <?php endif; ?>
  <?php if (isset($_GET['update_error'])): ?>
    <div class="error-box" style="margin:12px 0;"><?= e($_GET['update_error']) ?></div>
  <?php endif; ?>

  <div class="flex flex-col items-center" style="padding:24px 0 16px;">
    <div class="avatar avatar-round" style="width:80px;height:80px;font-size:30px;background-color:#2A7A5B22;color:#2A7A5B;margin-bottom:12px;">
      <?= e(iniciais($user['nome_completo'])) ?>
    </div>
    <h2 class="serif font-semibold" style="font-size:20px;"><?= e($user['nome_completo']) ?></h2>
    <span class="badge" style="margin-top:4px;background-color:var(--secondary);color:var(--primary);">
      <?= ($user['tipo_usuario'] ?? '') === 'responsavel' ? 'Responsável' : 'Cuidador(a)' ?>
    </span>
  </div>

  <div class="list-panel" style="margin-bottom:16px;">
    <div class="list-row">
      <p class="text-xs font-semibold text-muted" style="text-transform:uppercase;letter-spacing:0.05em;">E-mail</p>
      <p class="text-sm"><?= e($user['email']) ?></p>
    </div>
    <div class="list-row">
      <p class="text-xs font-semibold text-muted" style="text-transform:uppercase;letter-spacing:0.05em;">Telefone</p>
      <p class="text-sm"><?= e($user['telefone'] ?? 'Não informado') ?></p>
    </div>
    <div class="list-row">
      <p class="text-xs font-semibold text-muted" style="text-transform:uppercase;letter-spacing:0.05em;">Cidade</p>
      <p class="text-sm"><?= e($user['cidade'] ?? 'Não informado') ?></p>
    </div>
  </div>

  <div class="list-panel" style="margin-bottom:24px;">
    <?php
    $acoes = [
        ['label' => 'Editar dados pessoais', 'icon' => '✏️'],
        ['label' => 'Segurança e senha', 'icon' => '🔐'],
        ['label' => 'Ajuda e suporte', 'icon' => '💬'],
    ];
    foreach ($acoes as $i => $acao): ?>
      <button type="button" class="list-row" style="width:100%;" <?= $acao['label'] === 'Editar dados pessoais' ? 'onclick="openEditModal()"' : ($acao['label'] === 'Segurança e senha' ? 'onclick="openChangePasswordModal()"' : '') ?> >
        <div class="flex items-center gap-3">
          <span><?= $acao['icon'] ?></span>
          <p class="text-sm"><?= e($acao['label']) ?></p>
        </div>
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" stroke="var(--muted-foreground)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    <?php endforeach; ?>
  </div>

  <!-- Modal de edição -->
  <div class="modal-overlay" id="editModal" style="display:none;position:fixed;inset:0;background-color:rgba(0,0,0,0.45);align-items:center;justify-content:center;z-index:999;">
    <div class="modal-sheet" style="background:white;padding:18px;border-radius:12px;max-width:520px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
      <h3 class="serif font-semibold" style="margin-top:0;">Editar dados pessoais</h3>
      <form method="post" action="actions/update_profile.php" style="margin-top:12px;">
        <?php
        // Renderizar campos da tabela usuario, exceto sensíveis e imutáveis
        $blacklist = ['id','senha_hash','criado_em','tipo_usuario'];
        $preferredOrder = ['nome_completo','email','telefone','cidade','endereco'];
        // mostrar campos preferenciais na ordem
        foreach ($preferredOrder as $col) {
            if (in_array($col, $usuarioCols) && !in_array($col, $blacklist)) {
                $val = $user[$col] ?? '';
                $type = $col === 'email' ? 'email' : 'text';
                $req = $col === 'nome_completo' || $col === 'email' ? 'required' : '';
                echo '<div class="field">';
                echo '<label>' . ucfirst(str_replace('_',' ',$col)) . '</label>';
                echo '<input type="' . $type . '" name="' . $col . '" value="' . e($val) . '" ' . $req . '>';
                echo '</div>';
            }
        }
        // render any remaining columns
        foreach ($usuarioCols as $col) {
            if (in_array($col, $blacklist) || in_array($col, $preferredOrder)) continue;
            $val = $user[$col] ?? '';
            echo '<div class="field">';
            echo '<label>' . ucfirst(str_replace('_',' ',$col)) . '</label>';
            echo '<input type="text" name="' . $col . '" value="' . e($val) . '">';
            echo '</div>';
        }
        ?>
        <?php if (($user['tipo_usuario'] ?? '') === 'cuidador'): ?>
          <hr style="margin:12px 0;">
          <h4 style="margin:4px 0 8px;">Dados do cuidador</h4>
          <?php
          $cblack = ['id','usuario_id','esta_verificado','criado_em'];
          $preferred = ['biografia','especialidades','valor_hora','latitude','longitude'];
          foreach ($preferred as $col) {
              if (in_array($col, $cuidadorCols) && !in_array($col, $cblack)) {
                  $val = $cuidador[$col] ?? '';
                  $ta = in_array($col, ['biografia','especialidades']) ? 'textarea' : 'input';
                  echo '<div class="field">';
                  echo '<label>' . ucfirst(str_replace('_',' ',$col)) . '</label>';
                  if ($ta === 'textarea') {
                      echo '<textarea name="cuidador_' . $col . '" rows="3">' . e($val) . '</textarea>';
                  } else {
                      echo '<input type="text" name="cuidador_' . $col . '" value="' . e($val) . '">';
                  }
                  echo '</div>';
              }
          }
          foreach ($cuidadorCols as $col) {
              if (in_array($col, $cblack) || in_array($col, $preferred)) continue;
              $val = $cuidador[$col] ?? '';
              echo '<div class="field">';
              echo '<label>' . ucfirst(str_replace('_',' ',$col)) . '</label>';
              echo '<input type="text" name="cuidador_' . $col . '" value="' . e($val) . '">';
              echo '</div>';
          }
          ?>
        <?php endif; ?>

        <div style="display:flex;gap:8px;margin-top:12px;justify-content:flex-end;">
          <button type="button" class="btn btn-muted" onclick="closeEditModal()">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de troca de senha -->
  <div class="modal-overlay" id="changePasswordModal" style="display:none;position:fixed;inset:0;background-color:rgba(0,0,0,0.45);align-items:center;justify-content:center;z-index:999;">
    <div class="modal-sheet" style="background:white;padding:18px;border-radius:12px;max-width:520px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
      <h3 class="serif font-semibold" style="margin-top:0;">Segurança — Alterar senha</h3>
      <form method="post" action="actions/change_password.php" style="margin-top:12px;">
        <div class="field">
          <label>Senha atual</label>
          <input type="password" name="senha_atual" required>
        </div>
        <div class="field">
          <label>Nova senha</label>
          <input type="password" name="nova_senha" required minlength="8">
        </div>
        <div class="field">
          <label>Confirme a nova senha</label>
          <input type="password" name="confirma_senha" required minlength="8">
        </div>
        <div style="display:flex;gap:8px;margin-top:12px;justify-content:flex-end;">
          <button type="button" class="btn btn-muted" onclick="closeChangePasswordModal()">Cancelar</button>
          <button type="submit" class="btn btn-primary">Alterar senha</button>
        </div>
      </form>
    </div>
  </div>

  <a href="logout.php" class="btn btn-danger" style="display:block;text-align:center;">Sair da conta</a>
</div>

<?php $activeTab = 'profile'; require __DIR__ . '/includes/bottomnav_component.php'; ?>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
<script>
function openEditModal(){ document.getElementById('editModal').style.display='flex'; }
function closeEditModal(){ document.getElementById('editModal').style.display='none'; }
// fechar ao clicar fora
function openChangePasswordModal(){ document.getElementById('changePasswordModal').style.display='flex'; }
function closeChangePasswordModal(){ document.getElementById('changePasswordModal').style.display='none'; }

// fechar ao clicar fora para ambos os modais
document.addEventListener('click', function(e){
  const editModal = document.getElementById('editModal');
  const changeModal = document.getElementById('changePasswordModal');
  if(editModal && window.getComputedStyle(editModal).display !== 'none' && e.target === editModal) closeEditModal();
  if(changeModal && window.getComputedStyle(changeModal).display !== 'none' && e.target === changeModal) closeChangePasswordModal();
});
</script>
