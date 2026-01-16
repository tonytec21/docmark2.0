<?php
// funcoes.php

// Inicia sessão sem dar erro caso já esteja ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function nome_escrevente_logado(): string
{
    return trim((string)($_SESSION['user']['nome_completo'] ?? ''));
}


/**
 * Redireciona para o login e encerra.
 */
function redirecionar_para_login(int $error = 0): void
{
    $url = '../login.php' . ($error ? '?error=' . $error : '');
    header('Location: ' . $url);
    exit;
}

/**
 * Verifica se a sessão está ativa e se o usuário está autorizado.
 * - Exige $_SESSION['auth'] === true
 * - Exige status "ativo"
 * - (Opcional) invalida sessão se mudou user agent
 * - (Opcional) timeout por inatividade
 */
function verificar_sessao_ativa(): void
{
    // 1) Precisa estar autenticado
    if (empty($_SESSION['auth']) || $_SESSION['auth'] !== true || empty($_SESSION['user']['id'])) {
        redirecionar_para_login();
    }

    // 2) Status precisa ser ativo (segurança extra caso o usuário seja removido depois)
    $status = strtolower(trim((string)($_SESSION['user']['status'] ?? '')));
    if ($status !== 'ativo') {
        session_destroy();
        redirecionar_para_login(1);
    }

    // 3) (Opcional) trava por User-Agent para reduzir sequestro de sessão
    $uaAtual = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (!isset($_SESSION['ua'])) {
        $_SESSION['ua'] = $uaAtual;
    } elseif ($_SESSION['ua'] !== $uaAtual) {
        session_destroy();
        redirecionar_para_login(1);
    }

    // 4) (Opcional) timeout por inatividade (ex.: 60 min)
    $timeoutSegundos = 60 * 60; // 1 hora
    $agora = time();

    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $agora;
    } elseif (($agora - (int)$_SESSION['last_activity']) > $timeoutSegundos) {
        session_destroy();
        redirecionar_para_login();
    } else {
        $_SESSION['last_activity'] = $agora;
    }
}
