<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('home.php');
}

$tipo = $_POST['tipo'] ?? 'responsavel';
$nome = '';
$email = '';
$telefone = '';
$especialidades = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $especialidades = trim($_POST['especialidades'] ?? '');

    if ($nome === '' || $email === '' || strlen($senha) < 8) {
        $erro = 'Preencha nome, e-mail e uma senha com pelo menos 8 caracteres.';
    } else {
        $stmt = getDB()->prepare('SELECT id FROM usuario WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = 'Já existe uma conta com esse e-mail.';
        } else {
            print("conta criada");
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = getDB()->prepare('INSERT INTO usuario (nome_completo, email, telefone, senha_hash, tipo_usuario) VALUES (?,?,?,?,?)');
            $stmt->execute([$nome, $email, $telefone, $hash, $tipo]);
            $newId = getDB()->lastInsertId();
            // Se for cuidador, criar perfil na tabela cuidador
            if ($tipo === 'cuidador') {
              $stmt2 = getDB()->prepare('INSERT INTO cuidador (usuario_id, especialidades, esta_verificado, valor_hora, criado_em) VALUES (?,?,?,?,NOW())');
              $stmt2->execute([$newId, $especialidades, 0, NULL]);
            }
            $_SESSION['user_id'] = $newId;
            redirect('home.php');
        }
    }
}

$pageTitle = 'Criar conta — CuidarBem';
$headerTitle = 'Criar conta';
$headerSubtitle = 'Preencha seus dados';
$headerBack = 'login.php';
require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/includes/header_component.php';
?>
<div class="flex-1" style="padding:0 24px 32px;overflow-y:auto;">

  <?php if ($erro): ?>
    <div class="error-box"><?= e($erro) ?></div>
  <?php endif; ?>

  <form method="post">
    <div style="margin-bottom:24px;">
      <p class="text-xs font-semibold text-muted" style="text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Sou um(a)</p>
      <div class="flex" style="background-color:var(--muted);border-radius:16px;padding:4px;">
        <label style="flex:1;text-align:center;padding:10px;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;background-color:<?= $tipo==='responsavel'?'var(--primary)':'transparent' ?>;color:<?= $tipo==='responsavel'?'white':'var(--muted-foreground)' ?>;">
          <input type="radio" name="tipo" value="responsavel" onchange="this.form.submit()" <?= $tipo==='responsavel'?'checked':'' ?> style="display:none;">
          Responsável
        </label>
        <label style="flex:1;text-align:center;padding:10px;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;background-color:<?= $tipo==='cuidador'?'var(--primary)':'transparent' ?>;color:<?= $tipo==='cuidador'?'white':'var(--muted-foreground)' ?>;">
          <input type="radio" name="tipo" value="cuidador" onchange="this.form.submit()" <?= $tipo==='cuidador'?'checked':'' ?> style="display:none;">
          Cuidador(a)
        </label>
      </div>
    </div>

    <div class="field">
      <label>Nome completo</label>
      <input type="text" name="nome" value="<?= e($nome) ?>" placeholder="Maria Souza" required>
    </div>
    <div class="field">
      <label>E-mail</label>
      <input type="email" name="email" value="<?= e($email) ?>" placeholder="maria@email.com" required>
    </div>
    <div class="field">
      <label>Telefone</label>
      <input type="tel" name="telefone" value="<?= e($telefone) ?>" placeholder="(11) 99999-0000">
    </div>
    <div class="field">
      <label>Senha</label>
      <input type="password" name="senha" placeholder="Mínimo 8 caracteres" required minlength="8">
    </div>

    <?php if ($tipo === 'cuidador'): ?>
      <div class="field">
        <label>Especialidades</label>
        <input type="text" name="especialidades" value="<?= e($especialidades) ?>" placeholder="Ex: Alzheimer, Mobilidade Reduzida">
      </div>
    <?php endif; ?>

    <p class="text-xs text-muted" style="line-height:1.6;margin-bottom:24px;">
      Ao criar uma conta, você concorda com nossos <span class="text-primary">Termos de Uso</span> e <span class="text-primary">Política de Privacidade</span>.
    </p>

    <button type="submit" class="btn btn-primary">Criar conta</button>
  </form>
</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
