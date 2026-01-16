<?php
header('Content-Type: application/json');

$pastaHistorico = __DIR__ . '/historico';
$arquivos = glob($pastaHistorico . '/*');

// Obtém os números dos nomes dos arquivos
$numerosArquivos = array();
foreach ($arquivos as $arquivo) {
    $numeroArquivo = (int) str_replace('.tiff', '', basename($arquivo));
    $numerosArquivos[] = $numeroArquivo;
}

// Obtém o intervalo de números
$minimo = 1; // agora o mínimo é sempre 1
$maximo = max($numerosArquivos);

// Obtém os números faltantes
$numerosFaltantes = array();
for ($i = $minimo; $i <= $maximo; $i++) {
    if (!in_array($i, $numerosArquivos)) {
        $numerosFaltantes[] = str_pad($i, 8, '0', STR_PAD_LEFT);
    }
}

$data['convertidos'] = count($numerosArquivos);
$data['faltantes'] = count($numerosFaltantes);

echo json_encode($data);
