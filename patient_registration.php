<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$form = ['nome' => '', 'data_nascimento' => '', 'condicoes_medicas' => '', 'telefone_emergencia' => '', 'observacoes' => ''];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $v) {
        $form[$k] = trim($_POST[$k] ?? '');
    }
    if ($form['nome'] === '' || $form['data_nascimento'] === '') {
        $erro = 'Nome e data de nascimento são obrigatórios.';
    } else {
        $stmt = getDB()->prepare('INSERT INTO idoso (responsavel_id, nome_completo, data_nascimento, condicoes_medicas, telefone_emergencia, criado_em) VALUES (?,?,?,?,?,NOW())');
        $stmt->execute([$_SESSION['user_id'], $form['nome'], $form['data_nascimento'], $form['condicoes_medicas'], $form['telefone_emergencia']]);
        redirect('home.php');
    }
}

$pageTitle = 'Cadastrar paciente';
$headerTitle = 'Cadastrar paciente';
$headerSubtitle = 'Novo idoso';
$headerBack = 'home.php';
require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/includes/header_component.php';
?>
<div class="flex-1" style="padding:0 20px 32px;overflow-y:auto;">

  <div class="flex justify-center" style="margin-bottom:24px;">
    <div style="width:80px;height:80px;border-radius:999px;background-color:var(--secondary);display:flex;align-items:center;justify-content:center;">
      <svg width="36" height="36" fill="none" viewBox="0 0 24 24">
        <circle cx="12" cy="8" r="4" stroke="var(--primary)" stroke-width="2"/>
        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="var(--primary)" stroke-width="2" stroke-linecap="round"/>
        <path d="M19 3v6M16 6h6" stroke="var(--accent)" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>
  </div>

  <?php if ($erro): ?>
    <div class="error-box"><?= e($erro) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="field">
      <label>Nome completo *</label>
      <input type="text" name="nome" value="<?= e($form['nome']) ?>" placeholder="Ex: Maria da Silva" required>
    </div>
    <div class="field">
      <label>Data de nascimento *</label>
      <input type="date" name="data_nascimento" value="<?= e($form['data_nascimento']) ?>" required>
    </div>
    <div class="field">
      <label>Telefone de emergência *</label>
      <input type="tel" name="telefone_emergencia" value="<?= e($form['telefone_emergencia']) ?>" placeholder="(11) 99999-0000" required>
    </div>
    <div class="field">
      <label>Condições médicas</label>
      <textarea name="condicoes_medicas" rows="3" placeholder="Ex: Alzheimer em estágio inicial, Hipertensão arterial"><?= e($form['condicoes_medicas']) ?></textarea>
    </div>
    <div class="field">
      <label>Observações adicionais</label>
      <textarea name="observacoes" rows="3" placeholder="Alergias, preferências, informações relevantes..."><?= e($form['observacoes']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Cadastrar paciente</button>
  </form>
</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
