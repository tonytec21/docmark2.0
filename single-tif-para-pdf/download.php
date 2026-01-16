<?php
if (isset($_GET['arquivo'])) {
    $arquivo = $_GET['arquivo'];
    $arquivo_path = __DIR__.'/arquivos/'.$arquivo;

    if (file_exists($arquivo_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $arquivo . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($arquivo_path));
        readfile($arquivo_path);
        exit;
    } else {
        die("O arquivo solicitado não foi encontrado.");
    }
} else {
    die("O arquivo não foi especificado.");
}
