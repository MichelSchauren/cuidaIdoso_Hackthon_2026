<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    $stmt = getDB()->prepare('SELECT * FROM usuario WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome));
    $partes = array_slice($partes, 0, 2);
    $temMb = function_exists('mb_substr') && function_exists('mb_strtoupper');
    $letras = array_map(function ($p) use ($temMb) {
        $primeira = $temMb ? mb_substr($p, 0, 1) : substr($p, 0, 1);
        return $temMb ? mb_strtoupper($primeira) : strtoupper($primeira);
    }, $partes);
    return implode('', $letras);
}

function formatarDataBR(string $data): string
{
    $ts = strtotime($data);
    return $ts ? date('d/m/Y', $ts) : $data;
}

function formatarHora(string $dataHora): string
{
    $ts = strtotime($dataHora);
    return $ts ? date('H:i', $ts) : $dataHora;
}

function formatarDataCurta(string $dataHora): string
{
    $ts = strtotime($dataHora);
    if (!$ts) return $dataHora;
    $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    return date('d', $ts) . ' ' . $meses[(int)date('n', $ts) - 1];
}

function calcularIdade(string $dataNascimento): int
{
    $nasc = new DateTime($dataNascimento);
    $hoje = new DateTime('now');
    return $hoje->diff($nasc)->y;
}

function contarNaoLidas(int $usuarioId): int
{
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM notificacao WHERE usuario_id = ? AND lida = 0');
    $stmt->execute([$usuarioId]);
    return (int)$stmt->fetchColumn();
}

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Garante que o paciente pertence ao responsável logado; senão, redireciona. */
function pacienteDoUsuarioOuFalha(int $idosoId): array
{
    $db = getDB();
    // Primeiro, tentar responsável
    $stmt = $db->prepare('SELECT * FROM idoso WHERE id = ? AND responsavel_id = ?');
    $stmt->execute([$idosoId, $_SESSION['user_id']]);
    $idoso = $stmt->fetch();
    if ($idoso) return $idoso;

    // Se não for responsável, verificar se o usuário é cuidador vinculado (cobre legacy e novo schema)
    $stmt = $db->prepare('SELECT i.* FROM idoso i
        JOIN cuidador_idoso ci ON ci.idoso_id = i.id
        LEFT JOIN cuidador c ON c.id = ci.cuidador_id
        WHERE i.id = ? AND (ci.cuidador_id = ? OR c.usuario_id = ?) AND ci.status = ?');
    $stmt->execute([$idosoId, $_SESSION['user_id'], $_SESSION['user_id'], 'ativo']);
    $idoso = $stmt->fetch();
    if (!$idoso) {
        redirect('home.php');
    }
    return $idoso;
}

const AVATAR_CORES = ['#2A7A5B', '#E07B54', '#5B7ABA', '#8B5BAA'];
