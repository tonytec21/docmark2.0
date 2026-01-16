<?php
// Verificar se o arquivo cns.json existe
if (file_exists('cns.json')) {
    // Ler o arquivo cns.json
    $cnsData = json_decode(file_get_contents('cns.json'), true);

    // Verificar se o campo 'CNS' foi encontrado
    if (isset($cnsData['cns'])) {
        $cns = $cnsData['cns'];

        // Verificar se o arquivo numero_matricula.json existe
        if (file_exists('numero_matricula.json')) {
            // Ler o arquivo numero_matricula.json
            $numeroMatriculaData = json_decode(file_get_contents('numero_matricula.json'), true);

            // Array para guardar dados de todos os arquivos
            $todosDados = array();

            foreach ($numeroMatriculaData as $item) {
                // Verificar se o campo 'numeroMatricula' foi encontrado
                if (isset($item['numeroMatricula'])) {
                    $numeroMatricula = $item['numeroMatricula'];

                    // Realizar os cálculos para obter o dígito verificador
                    $x1 = preg_replace('/[^0-9]/', '', $cns);
                    $y = 97;
                    $valor_mod1 = fmod($x1, $y);
                    $y1 = 2;
                    $x2 = $x1 . $y1;
                    $valor_mod2 = fmod($x2, $y);

                    $y2 = str_pad($numeroMatricula, 7, '0', STR_PAD_LEFT);

                    $x3 = $valor_mod2 . $y2;
                    $valor_mod3 = fmod($x3, $y);

                    $y3 = '00';
                    $x4 = $valor_mod3 . $y3;

                    $valor_mod4 = fmod($x4, $y);
                    $valor = 98 - $valor_mod4;

                    // Gerar o CNM com base nos cálculos
                    $cnm = sprintf('%s.%d.%07d-%02d', $cns, 2, $numeroMatricula, $valor);

                    // Salvar o CNM e o nome do arquivo em um array
                    $dados = array('cnm' => $cnm, 'nomeArquivo' => $item['nomeArquivo']);
                    array_push($todosDados, $dados);
                } else {
                    echo "Campo 'numeroMatricula' não encontrado no item do arquivo 'numero_matricula.json'.";
                }
            }

            // Salvar os CNMs em um arquivo JSON
            $cnmJson = json_encode($todosDados);
            file_put_contents('cnm.json', $cnmJson);

            // Redirecionar para a página de carimbo
            header('Location: carimbo.php');
            exit();
        } else {
            echo "Arquivo 'numero_matricula.json' não encontrado.";
        }
    } else {
        echo "Campo 'CNS' não encontrado no arquivo 'cns.json'.";
    }
} else {
    echo "Arquivo 'cns.json' não encontrado.";
}
?>
