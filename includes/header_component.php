<?php
/**
 * Renderiza o cabeçalho padrão das telas internas.
 * Parâmetros esperados nas variáveis:
 *   $headerTitle (string)
 *   $headerSubtitle (string|null)
 *   $headerBack (string|null) - URL do botão voltar (se null, não mostra botão)
 *   $headerRightHtml (string|null) - HTML customizado do lado direito
 */
$headerSubtitle = $headerSubtitle ?? null;
$headerBack = $headerBack ?? null;
$headerRightHtml = $headerRightHtml ?? '';
?>
<div class="screen-header">
  <?php if ($headerBack): ?>
    <a href="<?= e($headerBack) ?>" class="btn-back">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24">
        <path d="m15 18-6-6 6-6" stroke="var(--foreground)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </a>
  <?php endif; ?>
  <div class="flex-1">
    <h2 class="truncate"><?= e($headerTitle) ?></h2>
    <?php if ($headerSubtitle): ?>
      <p class="subtitle truncate"><?= e($headerSubtitle) ?></p>
    <?php endif; ?>
  </div>
  <?php if ($headerRightHtml): ?>
    <div class="header-right"><?= $headerRightHtml ?></div>
  <?php endif; ?>
</div>
