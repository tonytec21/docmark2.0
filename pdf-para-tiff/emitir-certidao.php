<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

// Verificar se a matrícula foi informada
if (!isset($_GET['matricula']) || empty($_GET['matricula'])) {
    echo '<script>alert("Matrícula não informada!"); window.location.href = "historico.php";</script>';
    exit;
}

$matricula = $_GET['matricula'];
$matricula = str_pad(preg_replace('/[^0-9]/', '', $matricula), 8, '0', STR_PAD_LEFT);

// Verificar se o PDF existe
$pdfPath = __DIR__ . '/pdf-viw/' . $matricula . '.pdf';

if (!file_exists($pdfPath)) {
    echo '<script>alert("PDF da matrícula não encontrado!"); window.location.href = "historico.php";</script>';
    exit;
}

// Gerar código único da certidão
$codigoCertidao = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
$dataHora = date('Y-m-d_H-i-s');

// Criar diretório para as imagens da certidão
$certidaoDir = __DIR__ . '/certidao/matricula' . $matricula . '/' . $dataHora . '_' . $codigoCertidao;

if (!is_dir($certidaoDir)) {
    mkdir($certidaoDir, 0777, true);
}

// Converter PDF para JPG usando ImageMagick com configurações para fundo branco
$outputPattern = $certidaoDir . '/pagina';

// Comando otimizado para converter PDF em JPG com fundo branco e boa qualidade
$comando = "magick convert -density 200 -quality 95 -background white -alpha remove -alpha off \"{$pdfPath}\" \"{$outputPattern}_%03d.jpg\" 2>&1";
exec($comando, $output, $returnCode);

// Verificar se a conversão foi bem-sucedida
$imagens = glob($certidaoDir . '/pagina_*.jpg');

if (empty($imagens)) {
    // Tentar sem o prefixo 'magick'
    $comando2 = "convert -density 200 -quality 95 -background white -alpha remove -alpha off \"{$pdfPath}\" \"{$outputPattern}_%03d.jpg\" 2>&1";
    exec($comando2, $output2, $returnCode2);
    $imagens = glob($certidaoDir . '/pagina_*.jpg');
}

if (empty($imagens)) {
    // Tentar usando pdftoppm (poppler-utils)
    $comando3 = "pdftoppm -jpeg -r 200 -jpegopt quality=95 \"{$pdfPath}\" \"{$outputPattern}\" 2>&1";
    exec($comando3, $output3, $returnCode3);
    
    // pdftoppm gera arquivos com sufixo diferente
    $imagens = glob($certidaoDir . '/pagina-*.jpg');
    if (empty($imagens)) {
        $imagens = glob($certidaoDir . '/pagina*.jpg');
    }
}

if (empty($imagens)) {
    echo '<script>alert("Erro ao converter PDF para imagens. Verifique se o ImageMagick está instalado."); window.location.href = "historico.php";</script>';
    exit;
}

// Ordenar as imagens
sort($imagens);

// Renomear para padrão consistente se necessário
$imagensRenomeadas = [];
foreach ($imagens as $index => $imagem) {
    $novoNome = $certidaoDir . '/pagina_' . str_pad($index, 3, '0', STR_PAD_LEFT) . '.jpg';
    if ($imagem !== $novoNome) {
        rename($imagem, $novoNome);
    }
    $imagensRenomeadas[] = basename($novoNome);
}

// Salvar informações da certidão em um arquivo JSON
$certidaoInfo = [
    'matricula' => $matricula,
    'codigo' => $codigoCertidao,
    'data_criacao' => date('Y-m-d H:i:s'),
    'imagens' => $imagensRenomeadas,
    'status' => 'pendente'
];

file_put_contents($certidaoDir . '/certidao_info.json', json_encode($certidaoInfo, JSON_PRETTY_PRINT));

// Redirecionar para a página de organização
$certidaoPath = 'matricula' . $matricula . '/' . $dataHora . '_' . $codigoCertidao;
header('Location: organizar-certidao.php?path=' . urlencode($certidaoPath));
exit;
?>
