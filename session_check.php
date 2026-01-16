<?php
// session_check.php
session_start();

if (empty($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    header('Location: login.php');
    exit;
}

// Opcional: bloquear se status não estiver ativo (caso alguém seja removido depois)
if (!empty($_SESSION['user']['status']) && strtolower(trim($_SESSION['user']['status'])) !== 'ativo') {
    session_destroy();
    header('Location: login.php?error=1');
    exit;
}
