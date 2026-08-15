<?php
// Script para migrar referências legacy de cuidador.id para usuario.id
// Local: tools/migrate_legacy_caregiver_ids.php
// Backup o banco antes de executar.

require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "Iniciando migração de IDs legacy...\n";
$stats = [
    'cuidador_idoso_updated' => 0,
    'cuidador_idoso_deleted' => 0,
    'chamado_updated' => 0,
    'historico_updated' => 0,
    'tarefa_updated' => 0,
    'diario_updated' => 0,
];

try {
    $db->beginTransaction();

    // 1) Migrar entradas em cuidador_idoso
    $rows = $db->query("SELECT ci.id, ci.idoso_id, ci.cuidador_id FROM cuidador_idoso ci JOIN cuidador c ON c.id = ci.cuidador_id")->fetchAll();
    foreach ($rows as $r) {
        $novo = (int)$db->query('SELECT usuario_id FROM cuidador WHERE id = ' . (int)$r['cuidador_id'])->fetchColumn();
        if (!$novo) continue;
        // Checar se já existe registro com idoso_id + novo cuidador
        $exists = $db->prepare('SELECT id FROM cuidador_idoso WHERE idoso_id = ? AND cuidador_id = ?');
        $exists->execute([$r['idoso_id'], $novo]);
        $found = $exists->fetchColumn();
        if ($found) {
            // já existe -> removemos a linha legacy
            $del = $db->prepare('DELETE FROM cuidador_idoso WHERE id = ?');
            $del->execute([$r['id']]);
            $stats['cuidador_idoso_deleted']++;
        } else {
            $upd = $db->prepare('UPDATE cuidador_idoso SET cuidador_id = ? WHERE id = ?');
            $upd->execute([$novo, $r['id']]);
            $stats['cuidador_idoso_updated']++;
        }
    }

    // 2) Migrar chamado.cuidador_id
    $rows = $db->query("SELECT ch.id, ch.cuidador_id FROM chamado ch JOIN cuidador c ON c.id = ch.cuidador_id")->fetchAll();
    foreach ($rows as $r) {
        $novo = (int)$db->query('SELECT usuario_id FROM cuidador WHERE id = ' . (int)$r['cuidador_id'])->fetchColumn();
        if (!$novo) continue;
        $upd = $db->prepare('UPDATE chamado SET cuidador_id = ? WHERE id = ?');
        $upd->execute([$novo, $r['id']]);
        $stats['chamado_updated']++;
    }

    // 3) historico_medicamento.cuidador_id
    $rows = $db->query("SELECT h.id, h.cuidador_id FROM historico_medicamento h JOIN cuidador c ON c.id = h.cuidador_id")->fetchAll();
    foreach ($rows as $r) {
        $novo = (int)$db->query('SELECT usuario_id FROM cuidador WHERE id = ' . (int)$r['cuidador_id'])->fetchColumn();
        if (!$novo) continue;
        $upd = $db->prepare('UPDATE historico_medicamento SET cuidador_id = ? WHERE id = ?');
        $upd->execute([$novo, $r['id']]);
        $stats['historico_updated']++;
    }

    // 4) tarefa.cuidador_id
    $rows = $db->query("SELECT t.id, t.cuidador_id FROM tarefa t JOIN cuidador c ON c.id = t.cuidador_id")->fetchAll();
    foreach ($rows as $r) {
        $novo = (int)$db->query('SELECT usuario_id FROM cuidador WHERE id = ' . (int)$r['cuidador_id'])->fetchColumn();
        if (!$novo) continue;
        $upd = $db->prepare('UPDATE tarefa SET cuidador_id = ? WHERE id = ?');
        $upd->execute([$novo, $r['id']]);
        $stats['tarefa_updated']++;
    }

    // 5) diario.cuidador_id
    $rows = $db->query("SELECT d.id, d.cuidador_id FROM diario d JOIN cuidador c ON c.id = d.cuidador_id")->fetchAll();
    foreach ($rows as $r) {
        $novo = (int)$db->query('SELECT usuario_id FROM cuidador WHERE id = ' . (int)$r['cuidador_id'])->fetchColumn();
        if (!$novo) continue;
        $upd = $db->prepare('UPDATE diario SET cuidador_id = ? WHERE id = ?');
        $upd->execute([$novo, $r['id']]);
        $stats['diario_updated']++;
    }

    $db->commit();

    echo "Migração concluída. Resumo:\n";
    foreach ($stats as $k => $v) {
        echo " - {$k}: {$v}\n";
    }
    echo "\nRecomendo verificar os dados no banco e fazer backup após validação.\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "Erro durante migração: " . $e->getMessage() . "\n";
    exit(1);
}
