<?php
if (isset($_GET['dataInicial']) && isset($_GET['dataFinal'])) {
    $dataInicial = $_GET['dataInicial'];
    $dataFinal = $_GET['dataFinal'];

    // Pasta onde estão os arquivos JSON
    $diretorio = 'indicador';

    // Lista de arquivos JSON na pasta
    $arquivos = glob($diretorio . '/*.json');

    // Array para armazenar o conteúdo de cada arquivo
    $conteudos = [];

    foreach ($arquivos as $arquivo) {
        // Obter a data do arquivo
        $dataArquivo = date('Y-m-d', filemtime($arquivo));

        // Verificar se a data do arquivo está dentro do intervalo fornecido
        if ($dataArquivo >= $dataInicial && $dataArquivo <= $dataFinal) {
            // Ler o conteúdo do arquivo e adicionar ao array
            $conteudo = file_get_contents($arquivo);
            $conteudoArray = json_decode($conteudo, true);

            // Adicionar o conteúdo do arquivo ao array de conteúdos
            $conteudos[] = $conteudoArray['INDICADOR_REAL']['REAL'];
        }
    }

    // Obter o conteúdo do arquivo CNS
    $cnsArquivo = '../carimbo-digital/cns.json';
    $cnsConteudo = file_get_contents($cnsArquivo);
    $cnsArray = json_decode($cnsConteudo, true);

    // Criar o array unificado com o cabeçalho a partir de TIPOENVIO
    $jsonUnificado = [
        'INDICADOR_REAL' => [
             'CNS' => $cnsArray['cns'],
            'REAL' => array_merge(...$conteudos),
        ],
    ];

    // Converter o array unificado em JSON
    $jsonUnificado = json_encode($jsonUnificado, JSON_PRETTY_PRINT);

    // Definir o cabeçalho para download do arquivo JSON
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="indicador-real-' . $dataInicial . '-' . $dataFinal . '.json"');

    // Enviar o conteúdo do arquivo JSON unificado para download
    echo $jsonUnificado;
} else {
    http_response_code(400);
    echo json_encode(array('message' => 'Datas não fornecidas corretamente.'));
}
?>
