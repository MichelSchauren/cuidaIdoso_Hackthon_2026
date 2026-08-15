<?php
/**
 * Conexão com o banco de dados (MySQL via PDO) e criação/seed automático das tabelas.
 * Configurações padrão usam `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` ou valores
 * locais (127.0.0.1, bd_cuida_idoso, root, '').
 */

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // Configuráveis via variáveis de ambiente
    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbName = getenv('DB_NAME') ?: 'bd_cuida_idoso';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS') ?: '';

    $charset = 'utf8mb4';

    try {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$charset}";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // Se o DB não existir, tentar criar e reconectar
        try {
            $dsnNoDb = "mysql:host={$dbHost};charset={$charset}";
            $tmp = new PDO($dsnNoDb, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $tmp->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
            $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset={$charset}", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e2) {
            throw $e2;
        }
    }

    // Se o schema MySQL não existe, criar a partir do arquivo mysql_schema.sql
    $check = $pdo->query("SHOW TABLES LIKE 'usuario'")->fetch();
    if (!$check) {
        criarEsquema($pdo);
    }

    return $pdo;
}

function criarEsquema(PDO $pdo): void
{
    $schemaFile = __DIR__ . '/../mysql_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new RuntimeException('Arquivo mysql_schema.sql não encontrado em ' . $schemaFile);
    }

    $sql = file_get_contents($schemaFile);
    // Remover comandos CREATE DATABASE / USE para execução via PDO conectado ao DB
    $sql = preg_replace('/CREATE DATABASE[^;]+;?/i', '', $sql);
    $sql = preg_replace('/USE[^;]+;?/i', '', $sql);

    // Desabilitar checagens FK enquanto cria tudo
    $stmts = array_filter(array_map('trim', explode(';', $sql)));
    $pdo->beginTransaction();
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($stmts as $s) {
            if ($s === '') continue;
            $pdo->exec($s);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        // Criar views de compatibilidade para o código existente (que usa nomes/pluralizações antigas)
        $views = [
            "CREATE OR REPLACE VIEW usuarios AS SELECT id, nome_completo AS nome, email, telefone, senha_hash, tipo_usuario AS tipo, criado_em AS criado_em, NULL AS cidade, NULL AS especialidades FROM usuario",
            "CREATE OR REPLACE VIEW idosos AS SELECT id, responsavel_id, nome_completo AS nome, data_nascimento, condicoes_medicas, telefone_emergencia, NULL AS observacoes, NULL AS cuidador_atual FROM idoso",
            "CREATE OR REPLACE VIEW medicamentos AS SELECT id, idoso_id, nome, NULL AS dosagem, intervalo_horas, instrucoes, ativo AS ativo, NULL AS proxima_dose, 'pendente' AS status FROM medicamento",
            "CREATE OR REPLACE VIEW tarefas AS SELECT id, idoso_id, titulo, descricao, horario_previsto, status, NULL AS cuidador FROM tarefa",
            "CREATE OR REPLACE VIEW diario AS SELECT id, idoso_id, cuidador_id AS cuidador, categoria, mensagem, criado_em FROM diario",
            "CREATE OR REPLACE VIEW notificacoes AS SELECT id, usuario_id, titulo, mensagem, lida AS lida, criado_em, NULL AS tipo FROM notificacao",
            "CREATE OR REPLACE VIEW cuidadores AS SELECT c.id, u.nome_completo AS nome, c.valor_hora, c.especialidades, c.esta_verificado AS verificado, NULL AS distancia_km, NULL AS avaliacao, 0 AS total_avaliacoes, c.biografia, 1 AS disponivel FROM cuidador c JOIN usuario u ON c.usuario_id = u.id",
            "CREATE OR REPLACE VIEW contratacoes AS SELECT id, cuidador_id, idoso_id, responsavel_id AS usuario_id, data_inicio, data_fim, status, criado_em FROM chamado",
        ];

        foreach ($views as $v) {
            $pdo->exec($v);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
