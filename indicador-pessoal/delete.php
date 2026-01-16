<?php
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['matricula'])) {
    $matricula = $_GET['matricula'];
    $caminho_arquivo = "matriculas/" . $matricula . ".json";

    // Verifica se o arquivo existe antes de tentar deletar
    if (file_exists($caminho_arquivo)) {
        // Deleta o arquivo
        unlink($caminho_arquivo);
        echo "Arquivo deletado com sucesso.";
    } else {
        echo "O arquivo não existe.";
    }
} else {
    echo "Requisição inválida.";
}
?>