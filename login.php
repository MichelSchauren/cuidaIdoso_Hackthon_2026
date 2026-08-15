<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('home.php');
}

$email = 'roberto.almeida@email.com';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = getDB()->prepare('SELECT * FROM usuario WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        redirect('home.php');
    } else {
        $erro = 'E-mail ou senha incorretos.';
    }
}

$pageTitle = 'Entrar — CuidarBem';
require __DIR__ . '/includes/layout_top.php';
?>
<div class="flex flex-col" style="height:100%;background-color:var(--background);">

  <div class="hero">
    <div class="hero-icon">
      <svg width="28" height="28" fill="none" viewBox="0 0 24 24">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" fill="rgba(255,255,255,0.6)" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <h1>CuidaIdoso</h1>
    <p>Gestão de cuidados para idosos,<br>com carinho e segurança.</p>
  </div>

  <div class="flex-1" style="padding:32px 24px 24px;">
    <h2 class="serif font-semibold" style="font-size:24px;margin-bottom:4px;">Entrar</h2>
    <p class="text-sm text-muted" style="margin-bottom:32px;">Bem-vindo de volta</p>

    <?php if ($erro): ?>
      <div class="error-box"><?= e($erro) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="field">
        <label>E-mail</label>
        <input type="email" name="email" value="<?= e($email) ?>" required>
      </div>
      <div class="field">
        <label>Senha</label>
        <input type="password" name="senha" placeholder="Digite sua senha" required>
      </div>

      <p class="text-right text-sm text-primary" style="margin-bottom:32px;">Esqueci a senha</p>

      <button type="submit" class="btn btn-primary" style="margin-bottom:16px;">Entrar</button>
    </form>

    <p class="text-center text-sm text-muted">
      Não tem conta?
      <a href="register.php" class="font-semibold text-primary">Cadastre-se</a>
    </p>

    <p class="text-center text-xs text-muted" style="margin-top:24px;">
      Demo: roberto.almeida@email.com / 12345678
    </p>
  </div>
</div>
<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
