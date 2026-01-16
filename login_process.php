<?php
// login_process.php
session_start();

require_once __DIR__ . '/db_connection.php';

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Captura e valida inputs
$usuario   = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$senhaRaw  = isset($_POST['senha']) ? (string)$_POST['senha'] : '';
$empresaId = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : 0;

if ($usuario === '' || $senhaRaw === '' || $empresaId <= 0) {
    header('Location: login.php?error=1');
    exit;
}

// Converte senha digitada para base64 (conforme seu padrão atual)
$senhaBase64 = base64_encode($senhaRaw);

// Busca usuário no banco
$sql = "SELECT id, usuario, senha, nome_completo, cargo, nivel_de_acesso, status, acesso_adicional, e_mail
        FROM funcionarios
        WHERE usuario = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('Prepare failed (funcionarios): ' . $conn->error);
    header('Location: login.php?error=1');
    exit;
}

$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    header('Location: login.php?error=1');
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Verifica status do usuário
$status = strtolower(trim((string)$user['status']));
if ($status !== 'ativo') {
    header('Location: login.php?error=1');
    exit;
}

// Verifica senha (base64)
if (!hash_equals((string)$user['senha'], $senhaBase64)) {
    header('Location: login.php?error=1');
    exit;
}

// ======= Buscar cidade/UF da serventia (OBRIGATÓRIO vir do banco) =======
$stmtS = $conn->prepare("
    SELECT cidade
    FROM cadastro_serventia
    WHERE id = ?
      AND status = '1'
    LIMIT 1
");

if (!$stmtS) {
    error_log('Prepare failed (cadastro_serventia): ' . $conn->error);
    header('Location: login.php?error=1');
    exit;
}

$stmtS->bind_param("i", $empresaId);
$stmtS->execute();
$resS = $stmtS->get_result();

if (!$resS || $resS->num_rows === 0) {
    // Serventia não encontrada ou não ativa
    $stmtS->close();
    header('Location: login.php?error=1');
    exit;
}

$rowS = $resS->fetch_assoc();
$stmtS->close();

$cidadeUF = trim((string)($rowS['cidade'] ?? '')); // Ex: "Zé Doca-MA"
$partes = array_map('trim', explode('-', $cidadeUF, 2));
$cidade = $partes[0] ?? '';
$estado = $partes[1] ?? '';

if ($cidade === '' || $estado === '') {
    // Informação inválida no banco (sem fallback, bloqueia)
    header('Location: login.php?error=1');
    exit;
}

// ✅ Login OK — cria sessão
session_regenerate_id(true);

$_SESSION['auth'] = true;
$_SESSION['user'] = [
    'id'              => (int)$user['id'],
    'usuario'         => (string)$user['usuario'],
    'nome_completo'   => (string)$user['nome_completo'],
    'cargo'           => (string)$user['cargo'],
    'nivel_de_acesso' => (string)$user['nivel_de_acesso'],
    'status'          => (string)$user['status'],
    'acesso_adicional'=> (string)$user['acesso_adicional'],
    'e_mail'          => (string)$user['e_mail'],
    'empresa_id'      => $empresaId,
];

// Grava cidade/UF na sessão (sem fallback)
$_SESSION['cidade'] = $cidade;
$_SESSION['estado'] = $estado;

// (Opcional) se seu verificar_sessao_ativa usa isso
$_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
$_SESSION['last_activity'] = time();

// Redirecione para a área logada
header('Location: index.php');
exit;
