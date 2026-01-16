<?php
/**
 * Solicitar Selo - DocMark
 * Arquivo para solicitação de selos eletrônicos
 * Específico para Certidões de Registro de Imóveis (16.24.x) e Arquivamento (16.39)
 */

require_once 'funcoes.php';
verificar_sessao_ativa();

error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('America/Sao_Paulo');

header('Content-Type: application/json; charset=utf-8');

// Função para obter as configurações da API do arquivo JSON
function getApiConfig() {
    $configFile = __DIR__ . '/conexao_selador.json';
    
    if (!file_exists($configFile)) {
        return false;
    }
    
    $config = json_decode(file_get_contents($configFile), true);
    
    if (!$config || !isset($config['url_base']) || !isset($config['porta'])) {
        return false;
    }
    
    return $config;
}

// Função para obter token de acesso usando cURL
function getAccessToken($authUrl, $username, $password, $client_id, $grant_type) {
    $data = [
        'username' => $username,
        'password' => $password,
        'client_id' => $client_id,
        'grant_type' => $grant_type
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $authUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);

    if ($response === false) {
        return false;
    }

    curl_close($ch);

    $responseData = json_decode($response, true);
    return $responseData['access_token'] ?? false;
}

// Função para mapear código de ato para URL e obter código de tabela de custas
function getAtoData($codAto, $anoTabela = '2026') {
    // Atos de Certidão - Endpoint: /selo/imovel/certidao
    // Atos de Arquivamento/Atos em Geral - Endpoint: /selo/imovel/atos-em-geral
    
    // Mapa 2025
    $map2025 = [
        // Certidões (usam endpoint /selo/imovel/certidao)
        '16.24.1'   => ['endpoint' => '/selo/imovel/certidao', 'tabela' => '0220250101', 'tipo' => 'certidao'],
        '16.24.2'   => ['endpoint' => '/selo/imovel/certidao', 'tabela' => '0220250101', 'tipo' => 'certidao'],
        '16.24.4'   => ['endpoint' => '/selo/imovel/certidao', 'tabela' => '0220250101', 'tipo' => 'certidao'],
        '16.24.4.1' => ['endpoint' => '/selo/imovel/certidao', 'tabela' => '0220250101', 'tipo' => 'certidao'],
        // Arquivamento/Atos em Geral (usa endpoint /selo/imovel/atos-em-geral)
        '16.39'     => ['endpoint' => '/selo/imovel/atos-em-geral', 'tabela' => '0220250101', 'tipo' => 'atos-em-geral'],
    ];

    // Mapa 2026
    $map2026 = [
        // Certidões (usam endpoint /selo/imovel/certidao)
        '16.24.1'   => ['endpoint' => '/selo/imovel/certidao', 'tabela' => '0220260101', 'tipo' => 'certidao'],
        '16.24.2'   => ['endpoint' => '/selo/imovel/certidao', 'tabela' => '0220260101', 'tipo' => 'certidao'],
        '16.24.4'   => ['endpoint' => '/selo/imovel/certidao', 'tabela' => '0220260101', 'tipo' => 'certidao'],
        '16.24.4.1' => ['endpoint' => '/selo/imovel/certidao', 'tabela' => '0220260101', 'tipo' => 'certidao'],
        // Arquivamento/Atos em Geral (usa endpoint /selo/imovel/atos-em-geral)
        '16.39'     => ['endpoint' => '/selo/imovel/atos-em-geral', 'tabela' => '0220260101', 'tipo' => 'atos-em-geral'],
    ];

    $anoTabela = ($anoTabela === '2025') ? '2025' : '2026';
    $map = ($anoTabela === '2025') ? $map2025 : $map2026;

    return $map[$codAto] ?? null;
}

// Verificar se é uma requisição POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['error' => 'Método não permitido.']);
    exit;
}

// Obter configurações da API
$apiConfig = getApiConfig();

if (!$apiConfig) {
    echo json_encode(['error' => 'Configurações do selador não encontradas. Verifique o arquivo conexao_selador.json']);
    exit;
}

$authUrl = $apiConfig['url_base'] . ':' . $apiConfig['porta'] . '/auth';
$seloBaseUrl = $apiConfig['url_base'] . ':' . $apiConfig['porta'];
$username = $apiConfig['usuario'];
$password = $apiConfig['senha'];
$client_id = "selador";
$grant_type = "password";

// Obter token de acesso
$token = getAccessToken($authUrl, $username, $password, $client_id, $grant_type);

if (!$token) {
    echo json_encode(['error' => 'Erro ao obter token de acesso. Verifique as credenciais do selador.']);
    exit;
}

// Processar dados do formulário
$ato = $_POST["ato"] ?? '';
$escrevente = nome_escrevente_logado();
if ($escrevente === '') {
    echo json_encode(['error' => 'Nome do usuário não encontrado na sessão. Faça login novamente.']);
    exit;
}
$quantidade = $_POST["quantidade"] ?? 1;
$certidaoPath = $_POST["certidao_path"] ?? '';
$matricula = $_POST["matricula"] ?? '';

// Processar partes (pode ser array ou string)
$partesRaw = $_POST["partes"] ?? [];
if (is_array($partesRaw)) {
    // Filtrar valores vazios e limpar espaços
    $partesArray = array_filter(array_map('trim', $partesRaw), function($v) { return $v !== ''; });
} else {
    $partesArray = !empty(trim($partesRaw)) ? [trim($partesRaw)] : [];
}

// String de partes para exibição
$partesString = implode('; ', $partesArray);

// Se não houver partes, usar valor padrão
if (empty($partesArray)) {
    $matriculaNumero = ltrim($matricula, '0');
    if (empty($matriculaNumero)) $matriculaNumero = '0';
    $partesArray = ['Certidão de Matrícula nº ' . $matriculaNumero];
    $partesString = $partesArray[0];
}

// Tabela de custas (padrão 2026)
$tabelaCustas = $_POST["tabela_custas"] ?? '2026';

// Isenção
$isentoValue = !empty($_POST['isento']);
$motivoIsencao = trim($_POST['motivo_isencao'] ?? '');

// Validações
if (empty($ato)) {
    echo json_encode(['error' => 'Código do ato é obrigatório.']);
    exit;
}

if (empty($quantidade) || $quantidade < 1) {
    echo json_encode(['error' => 'Quantidade inválida.']);
    exit;
}

if (empty($certidaoPath)) {
    echo json_encode(['error' => 'Caminho da certidão é obrigatório.']);
    exit;
}

// Verificar se o diretório da certidão existe
$certidaoDir = __DIR__ . '/certidao/' . $certidaoPath;
if (!is_dir($certidaoDir)) {
    echo json_encode(['error' => 'Diretório da certidão não encontrado.']);
    exit;
}

// Obter URL específica do ato e código de tabela de custas
$atoData = getAtoData($ato, $tabelaCustas);
if ($atoData === null) {
    echo json_encode(['error' => 'Código de ato inválido. Use: 16.24.1, 16.24.2, 16.24.4, 16.24.4.1 ou 16.39']);
    exit;
}

$fullUrl = $seloBaseUrl . $atoData['endpoint'];
$codigoTabelaCusta = $atoData['tabela'];
$tipoAto = $atoData['tipo'];

// Montagem do payload conforme o tipo de ato
if ($tipoAto === 'certidao') {
    // ============================================
    // CERTIDÃO - Endpoint: /selo/imovel/certidao
    // Schema: DadosSeloCertidaoRI
    // ============================================
    $data = [
        'codigoTipoAto' => $ato,
        'escrevente' => $escrevente,
        'versaoTabelaDeCustas' => $codigoTabelaCusta,
        'quantidade' => (int)$quantidade,
        'nomePartes' => $partesArray,  // Array de strings
        'isento' => (bool)$isentoValue
    ];
    
    // Adicionar matrícula se informada
    if (!empty($matricula)) {
        $data['matricula'] = $matricula;
    }
    
    // Adicionar motivo de isenção se informado
    if ($isentoValue && !empty($motivoIsencao)) {
        $data['motivoIsentoGratuito'] = $motivoIsencao;
    }
    
} else {
    // ============================================
    // ATOS EM GERAL (Arquivamento) - Endpoint: /selo/imovel/atos-em-geral
    // Schema: AtosEmGeral
    // ============================================
    
    // Montar array de partes para a API (ParteAto)
    $partesAto = [];
    foreach ($partesArray as $nomeParte) {
        $partesAto[] = [
            'nome' => trim($nomeParte),
            'documento' => ''  // CPF/CNPJ opcional
        ];
    }
    
    $data = [
        'ato' => [
            'codigo' => $ato,
            'codigoTabelaCusta' => $codigoTabelaCusta
        ],
        'escrevente' => $escrevente,
        'partes' => [
            'parteAto' => $partesAto
        ],
        'quantidade' => (int)$quantidade
    ];
    
    // Adicionar matrícula se informada
    if (!empty($matricula)) {
        $data['matricula'] = $matricula;
    }
    
    // Adicionar isenção
    if ($isentoValue) {
        $data['isento'] = [
            'value' => true,
            'motivo' => !empty($motivoIsencao) ? $motivoIsencao : null
        ];
    }
}

// Fazer requisição para a API do selador
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $fullUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode(['error' => 'Erro ao gerar selo: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$responseData = json_decode($response, true);

// Verificar a resposta para garantir que estamos recebendo os selos corretos
if (isset($responseData['resumos'][0]['numeroSelo'])) {
    $selosGerados = [];
    
    foreach ($responseData['resumos'] as $selo) {
        $seloData = [
            'numero_selo' => $selo['numeroSelo'],
            'texto_selo' => $selo['textoSelo'],
            'qr_code' => $selo['qrCode'] ?? null,
            'data_geracao' => $selo['dataGeracao'],
            'valor_qr_code' => $selo['valorQrCode'] ?? '',
            'ato' => $ato,
            'tipo_ato' => $tipoAto,
            'escrevente' => $escrevente,
            'partes' => $partesString,
            'matricula' => $matricula,
            'quantidade' => $quantidade,
            'isento' => $isentoValue,
            'motivo_isencao' => $motivoIsencao,
            'retorno_completo' => $selo
        ];
        
        $selosGerados[] = $seloData;
    }
    
    // Salvar os selos no arquivo JSON dentro do diretório da certidão
    $selosFile = $certidaoDir . '/selos.json';
    
    // Verificar se já existem selos salvos
    $selosExistentes = [];
    if (file_exists($selosFile)) {
        $selosExistentes = json_decode(file_get_contents($selosFile), true) ?? [];
    }
    
    // Adicionar novos selos aos existentes
    $selosExistentes = array_merge($selosExistentes, $selosGerados);
    
    // Salvar arquivo JSON com todos os selos
    file_put_contents($selosFile, json_encode($selosExistentes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Preparar HTML para exibição
    $selosHtml = '';
    foreach ($selosGerados as $selo) {
        $selosHtml .= '<div class="selo-card">';
        $selosHtml .= '<div class="selo-header">';
        $selosHtml .= '<span class="selo-title">Poder Judiciário – TJMA</span>';
        $selosHtml .= '<span class="selo-badge"><i class="fa-solid fa-check-circle"></i> Selo gerado</span>';
        $selosHtml .= '</div>';
        $selosHtml .= '<div class="selo-body">';
        if (!empty($selo['qr_code'])) {
            $selosHtml .= '<div class="selo-qr"><img src="data:image/png;base64,' . $selo['qr_code'] . '" alt="QR Code"></div>';
        }
        $selosHtml .= '<div class="selo-info">';
        $selosHtml .= '<div class="selo-numero">Selo: <strong>' . htmlspecialchars($selo['numero_selo']) . '</strong></div>';
        $selosHtml .= '<p class="selo-texto">' . nl2br(htmlspecialchars($selo['texto_selo'])) . '</p>';
        $selosHtml .= '</div>';
        $selosHtml .= '</div>';
        $selosHtml .= '</div>';
    }
    
    echo json_encode([
        'success' => 'Selo(s) solicitado(s) com sucesso!',
        'selos' => $selosGerados,
        'html' => $selosHtml,
        'total' => count($selosGerados)
    ]);
    
} else {
    // Erro na resposta da API
    $errorMsg = 'Não foi possível gerar o selo.';
    
    if (isset($responseData['message'])) {
        $errorMsg .= ' ' . $responseData['message'];
    } elseif (isset($responseData['error'])) {
        $errorMsg .= ' ' . $responseData['error'];
    }
    
    echo json_encode([
        'error' => $errorMsg,
        'response' => $responseData,
        'http_code' => $httpCode,
        'request_data' => $data,
        'endpoint' => $fullUrl
    ]);
}
?>
