<?php
require_once __DIR__ . '/../config/database.php';

echo "Aplicando alteração: adicionar coluna 'cidade' em cuidador...\n";
$db = getDB();
try {
    $sql = "ALTER TABLE cuidador ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) DEFAULT NULL";
    $db->exec($sql);
    echo "Coluna 'cidade' adicionada (ou já existente).\n";
} catch (PDOException $e) {
    echo "Falha ao alterar tabela: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Pronto. Agora abra o perfil de um cuidador e você verá o campo Cidade para edição.\n";
