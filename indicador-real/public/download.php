<?php
$arquivo = $_GET['arquivo'];
$caminhoArquivo = 'public/' . $arquivo;

if (file_exists($caminhoArquivo)) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $arquivo . '"');
    readfile($caminhoArquivo);
    exit;
} else {
    echo 'Arquivo não encontrado.';
}
?>
