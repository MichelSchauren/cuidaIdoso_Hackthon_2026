<?php
/**
 * Inclua este arquivo no topo de cada página, definindo $pageTitle antes.
 * Feche a página com layout_bottom.php
 */
$pageTitle = $pageTitle ?? 'CuidarBem';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?= e($pageTitle) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-outer">
  <div class="app-shell">
