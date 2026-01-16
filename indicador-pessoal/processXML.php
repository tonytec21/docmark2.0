<?php

function safeGet($array, $key) {
    return isset($array[$key]) ? htmlspecialchars($array[$key]) : "";
}

function formatDate($date) {
    if (empty($date)) {
        return "";
    }

    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    
    if ($dateTime === false) {
        return "Data inválida";
    }
    
    return $dateTime->format('dmY');
}

if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $startDate = strtotime($_POST['start_date']);
    $endDate = strtotime($_POST['end_date'] . ' 23:59:59');

    $dirPath = 'matriculas/';
    $jsonFiles = glob($dirPath . "*.json");

    if (empty($jsonFiles)) {
        die("Nenhum arquivo JSON encontrado na pasta 'matriculas'.");
    }

    $xmlOutput = '<?xml version="1.0" encoding="iso-8859-1"?>' . "\n";
    $xmlOutput .= "<BANCOLIGHT>\n";

    foreach ($jsonFiles as $file) {
        $fileModificationTime = filemtime($file);

        if ($fileModificationTime >= $startDate && $fileModificationTime <= $endDate) {
            $fileContent = file_get_contents($file);
            $jsonData = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                die("Erro ao decodificar o arquivo JSON '$file': " . json_last_error_msg());
            }

            foreach ($jsonData['entries'] as $entry) {
                $xmlOutput .= "    <INDIVIDUO>\n";
                $xmlOutput .= '        <NOME>' . safeGet($entry, 'nome') . "</NOME>\n";
                $xmlOutput .= '        <CNPJCPF>' . safeGet($entry, 'cpf') . "</CNPJCPF>\n";
                $xmlOutput .= '        <NMATRICULA>' . htmlspecialchars($jsonData['matricula']) . "</NMATRICULA>\n";
                $xmlOutput .= '        <TIPODEATO>' . safeGet($entry, 'tipo_ato') . "</TIPODEATO>\n";
                $xmlOutput .= '        <DTREGAVERB>' . formatDate(safeGet($entry, 'data_avr')) . "</DTREGAVERB>\n";
                $xmlOutput .= '        <DTVENDA>' . formatDate(safeGet($entry, 'data_venda')) . "</DTVENDA>\n";
                $xmlOutput .= "    </INDIVIDUO>\n";
            }
        }
    }

    if ($xmlOutput === '<?xml version="1.0" encoding="iso-8859-1"?>' . "\n<BANCOLIGHT>\n") {
        die("Nenhum arquivo JSON foi modificado no intervalo de datas fornecido.");
    }

    $xmlOutput .= "</BANCOLIGHT>\n";

    $currentDate = date('d-m-Y');
    $filename = "indicador-" . $currentDate . ".xml";
    
    $savePath = '../pdf-para-tiff/historico-indicador/' . $filename;
    $savePath = '../pdf-para-tiff/indicador-pessoal/' . $filename;
    file_put_contents($savePath, $xmlOutput);

    // Definir o cabeçalho Content-Type antes de qualquer saída
    header('Content-type: text/xml');
    // Definir o cabeçalho Content-Disposition para fazer o download do arquivo XML
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // Enviar o conteúdo XML para o navegador
    echo $xmlOutput;
}
?>
