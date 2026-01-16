<?php
$pastaHistorico = __DIR__ . '/historico';
if (isset($_GET['file'])) {
    $filename = $pastaHistorico . '/' . urldecode($_GET['file']);
    if (file_exists($filename)) {
        unlink($filename);
        header("Location: historico.php"); // Substitua 'index.php' pelo nome do seu arquivo original, se necessário
    } else {
        die("O arquivo não existe.");
    }
} else {
    die("Parâmetro de arquivo não fornecido.");
}
?>
