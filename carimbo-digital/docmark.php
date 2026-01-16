<!DOCTYPE html>
<html>
<head>
    <title>Processando Arquivo</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header class="header">
        <div class="logo">
        <br>
            <img src="../img/logo.png" alt="Logo">
            <h1>DocMark</h1>
        </div>
        <nav class="menu">
            <ul>
            <li><a href="index.php">Início</a></li>
        <li><a href="../pdf-para-tiff.php">Converter PDF para TIFF</a></li>
        <li><a href="../tiff-para-pdf.php">Converter TIFF para PDF</a></li>
        <li><a href="../contato.php">Contato</a></li>
        
        <li><a href="../sobre.php">Sobre</a></li>
        <li><a href="configuracao.php">Configuração</a></li>
            </ul>
        </nav>
    </header>
    <div class="container">
        <h1>Arquivo processado</h1>
        <div class="result">
            
            

        <?php
// Função para extrair o número de matrícula do nome do arquivo PDF
function extrairNumeroMatricula($nomeArquivoPDF) {
    $padrao = '/(\d+)\.pdf/';
    preg_match($padrao, $nomeArquivoPDF, $matches);

    if (isset($matches[1])) {
        return ltrim($matches[1], '0'); // Remover zeros à esquerda do número
    } else {
        return false;
    }
}

// Verificar se o formulário foi enviado
if (isset($_POST['submit'])) {
    // Verificar se o arquivo foi selecionado
    if (isset($_FILES['arquivoPDF'])) {
        $caminhoTemporario = $_FILES['arquivoPDF']['tmp_name'];
        $nomeArquivo = $_FILES['arquivoPDF']['name'];

        // Extrair o número de matrícula do nome do arquivo
        $numeroMatricula = extrairNumeroMatricula($nomeArquivo);

        // Exibir o número de matrícula
        echo "<!DOCTYPE html>";
        echo "<html>";
        echo "<head>";
        echo "<title>Resultado</title>";
        echo "</head>";
        echo "<body>";
        echo "<h2>Resultado</h2>";

        if ($numeroMatricula !== false) {
            echo "<h2>Matrícula nº: " . $numeroMatricula . "</h2>";

            // Salvar o número de matrícula em um arquivo JSON
            $dados = array('numeroMatricula' => $numeroMatricula, 'nomeArquivo' => $nomeArquivo);
            $json = json_encode($dados);
            file_put_contents('numero_matricula.json', $json);

            // Diretório de destino onde o arquivo será salvo
            $diretorioDestino = 'upload/';

            // Verificar se o diretório de destino existe, se não, criar
            if (!is_dir($diretorioDestino)) {
                mkdir($diretorioDestino, 0755, true);
            }

            // Mover o arquivo para o diretório de destino
            $caminhoArquivoDestino = $diretorioDestino . $nomeArquivo;
            if (move_uploaded_file($caminhoTemporario, $caminhoArquivoDestino)) {
                echo "<form action='gerar_cnm.php' method='POST'>";
                echo "<input type='hidden' name='numeroMatricula' value='" . $numeroMatricula . "'>";
                echo "<input type='submit' name='submit' value='Gerar o CNM e Carimbar'>";
                echo "</form>";
            } else {
                echo "<h2>Erro ao fazer upload do arquivo.</h2>";
            }
        } else {
            echo "<h2>Nenhum número de matrícula foi extraído.</h2>";
        }

        echo "</body>";
        echo "</html>";
    }
}
?>


        </div>
        <br>
        <a href="index.php" class="btn-gradient">Processar novo arquivo</a>
    </div>
    <footer>
    
    <p style="color: #fff;text-decoration: none"  href="https://backupcloud.site/" target="_blank"> <p>&copy; <span id="year"></span> DocMark | By Backup Cloud. Todos os direitos reservados.</p></p>
    
  </footer>

  <script>
    // Obtém o ano atual e insere no elemento de ID "year"
    document.getElementById("year").textContent = new Date().getFullYear();
  </script>
</body>
</html>

