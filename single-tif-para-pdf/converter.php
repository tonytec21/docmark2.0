<?php
// Aumentar o limite de tempo de execução
set_time_limit(300); // 300 segundos = 5 minutos

// Ativar a exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_FILES['files']['name'])) {
    // Definir diretório para salvar as imagens TIFF e PDFs
    $tiff_dir = __DIR__.'/tiff/';
    $pdf_dir = __DIR__.'/pdf/';

    // Criar diretórios se não existirem
    if (!file_exists($tiff_dir)) mkdir($tiff_dir, 0777, true);
    if (!file_exists($pdf_dir)) mkdir($pdf_dir, 0777, true);

    // Salvar arquivos TIFF em diretório específico
    $file_names = $_FILES['files']['name'];
    $temp_names = $_FILES['files']['tmp_name'];
    for ($i = 0; $i < count($temp_names); $i++) {
        if (!move_uploaded_file($temp_names[$i], $tiff_dir.$file_names[$i])) {
            die("Erro ao mover o arquivo para o diretório 'tiff'.");
        }
    }

    // Converter arquivos TIFF para PDF usando o comando convert do ImageMagick
    foreach ($file_names as $file_name) {
        $tiff_file_path = $tiff_dir.$file_name;
        $pdf_file_name = pathinfo($file_name, PATHINFO_FILENAME).'.pdf';
        $pdf_file_path = $pdf_dir.$pdf_file_name;

        // Executar o comando e capturar a saída e o código de retorno
        $output = [];
        $return_var = null;
        exec("convert $tiff_file_path $pdf_file_path 2>&1", $output, $return_var);

        // Verificar a saída e o código de retorno
        if ($return_var != 0) {
            die("Erro ao converter o arquivo $file_name. Código de retorno: $return_var. Erro: " . implode("\n", $output));
        }
    }

    // Criar arquivo ZIP com todos os PDFs
    $zip = new ZipArchive();
    $zip_file = $pdf_dir.'pdfs.zip';
    if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
        $pdf_files = glob($pdf_dir.'*.pdf');
        foreach ($pdf_files as $pdf_file) {
            if (!$zip->addFile($pdf_file, basename($pdf_file))) {
                die("Erro ao adicionar o arquivo $pdf_file ao ZIP.");
            }
        }
        $zip->close();
    } else {
        die("Erro ao criar o arquivo ZIP.");
    }

    // Download do arquivo ZIP
    if (file_exists($zip_file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.basename($zip_file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: '.filesize($zip_file));
        readfile($zip_file);
        exit;
    } else {
        die("Erro ao fazer o download do arquivo ZIP.");
    }
}
?>