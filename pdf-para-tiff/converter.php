<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

function limparPastaUpload($pastaUpload)
{
    $arquivosTIFF = glob($pastaUpload . '/*.tiff');
    foreach ($arquivosTIFF as $arquivoTIFF) {
        unlink($arquivoTIFF);
    }
}

function normalizarNomeArquivo($nomeArquivo)
{
    $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT', $nomeArquivo);
    $normalizado = preg_replace('/[^a-zA-Z0-9.-]/', '_', $normalizado);
    return $normalizado;
}

function copiarParaHistorico($arquivo, $pastaHistorico)
{
    $nomeArquivo = basename($arquivo);
    $destino = $pastaHistorico . '/' . $nomeArquivo;

    if (!copy($arquivo, $destino)) {
        throw new Exception("Falha ao copiar {$arquivo} para {$pastaHistorico}");
    }
}

// Variáveis para controle
$erros = [];
$sucesso = false;
$arquivosProcessados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdfs'])) {
    $pastaPDF = __DIR__ . '/pdfs';
    $pastaUpload = __DIR__ . '/upload';
    $pastaHistorico = __DIR__ . '/historico';
    $pastaHistoricoPDF = __DIR__ . '/pdf-viw';
    $pastaArquivos = __DIR__ . '/arquivos';

    // Criar diretórios se não existirem
    if (!is_dir($pastaPDF)) mkdir($pastaPDF, 0777, true);
    if (!is_dir($pastaUpload)) mkdir($pastaUpload, 0777, true);
    if (!is_dir($pastaHistorico)) mkdir($pastaHistorico, 0777, true);
    if (!is_dir($pastaHistoricoPDF)) mkdir($pastaHistoricoPDF, 0777, true);
    if (!is_dir($pastaArquivos)) mkdir($pastaArquivos, 0777, true);

    // Limpar pasta de upload
    limparPastaUpload($pastaUpload);

    // Mover arquivos enviados para a pasta pdfs
    foreach ($_FILES['pdfs']['tmp_name'] as $key => $tmpName) {
        if (empty($tmpName)) continue;
        
        $nomeOriginal = $_FILES['pdfs']['name'][$key];
        $nomeArquivoPDF = normalizarNomeArquivo($nomeOriginal);
        $arquivoPDF = $pastaPDF . '/' . $nomeArquivoPDF;
        move_uploaded_file($tmpName, $arquivoPDF);
    }

    // Processar arquivos PDF
    $arquivosPDF = glob($pastaPDF . '/*.pdf');
    
    foreach ($arquivosPDF as $arquivoPDF) {
        $nomeOriginal = basename($arquivoPDF);
        
        // Extrair números do nome do arquivo
        preg_match_all('!\d+!', basename($arquivoPDF, '.pdf'), $matches);
        $numeroArquivo = implode("", $matches[0]);
        
        if (empty($numeroArquivo)) {
            $erros[] = "Nome do arquivo '$nomeOriginal' precisa conter ao menos um número.";
            continue;
        }
        
        $nomeArquivoTIFF = str_pad($numeroArquivo, 8, '0', STR_PAD_LEFT) . '.tiff';
        $nomeArquivoTIFF = normalizarNomeArquivo($nomeArquivoTIFF);
        $arquivoTIFF = $pastaUpload . '/' . $nomeArquivoTIFF;

        try {
            $comandoImageMagick = "magick convert -density 200 -monochrome -compress Group4 \"{$arquivoPDF}\" \"{$arquivoTIFF}\"";
            exec($comandoImageMagick, $output, $returnCode);

            if ($returnCode === 0 && file_exists($arquivoTIFF)) {
                // Copiar para histórico
                copiarParaHistorico($arquivoTIFF, $pastaHistorico);
                
                // Copiar PDF para pdf-viw
                $nomeArquivoPDFNovo = str_pad($numeroArquivo, 8, '0', STR_PAD_LEFT) . '.pdf';
                $arquivoPDFNovo = $pastaHistoricoPDF . '/' . $nomeArquivoPDFNovo;
                copy($arquivoPDF, $arquivoPDFNovo);
                
                // Adicionar à lista de processados
                $arquivosProcessados[] = [
                    'nome_original' => $nomeOriginal,
                    'matricula' => ltrim(str_pad($numeroArquivo, 8, '0', STR_PAD_LEFT), '0'),
                    'arquivo_tiff' => $nomeArquivoTIFF
                ];
            } else {
                $erros[] = "Erro ao converter o arquivo: $nomeOriginal";
            }
        } catch (Exception $e) {
            $erros[] = "Erro ao converter '$nomeOriginal': " . $e->getMessage();
        }
    }

    // Criar arquivo ZIP se houver arquivos convertidos
    if (!empty($arquivosProcessados)) {
        $arquivoZip = $pastaArquivos . '/arquivos_' . date('YmdHis') . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($arquivoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $arquivosTIFF = glob($pastaUpload . '/*.tiff');
            foreach ($arquivosTIFF as $arquivoTIFF) {
                $zip->addFile($arquivoTIFF, basename($arquivoTIFF));
            }
            $zip->close();
            $sucesso = true;
        } else {
            $erros[] = "Erro ao criar o arquivo ZIP.";
        }
    }

    // Limpar pasta pdfs
    $arquivosParaLimpar = glob($pastaPDF . '/*.pdf');
    foreach ($arquivosParaLimpar as $arquivo) {
        unlink($arquivo);
    }
} else {
    $erros[] = "Nenhum arquivo enviado.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Resultado da Conversão</title>
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/docmark-modern.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .result-container {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .result-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-xl);
            padding: 40px;
            text-align: center;
        }
        
        .result-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 3rem;
        }
        .result-icon.success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--color-accent);
            border: 3px solid var(--color-accent);
        }
        .result-icon.warning {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
            border: 3px solid #fbbf24;
        }
        .result-icon.error {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 3px solid #ef4444;
        }
        
        .result-title {
            font-size: 1.75rem;
            color: var(--color-white);
            margin-bottom: 12px;
        }
        
        .result-description {
            color: var(--color-gray-400);
            margin-bottom: 32px;
            font-size: 1.1rem;
        }
        .result-description strong {
            color: var(--color-accent-light);
        }
        
        .files-processed {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 24px;
            text-align: left;
        }
        .files-processed h4 {
            color: var(--color-accent-light);
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: var(--radius-md);
            margin-bottom: 8px;
        }
        .file-item:last-child {
            margin-bottom: 0;
        }
        .file-item .icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--color-accent) 0%, #059669 100%);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .file-item .info {
            flex: 1;
        }
        .file-item .nome {
            color: var(--color-white);
            font-weight: 600;
            word-break: break-all;
        }
        .file-item .detalhes {
            color: var(--color-gray-400);
            font-size: 0.85rem;
        }
        .file-item .status {
            color: var(--color-accent);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .error-list {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: var(--radius-lg);
            padding: 16px;
            margin-bottom: 24px;
            text-align: left;
        }
        .error-list h4 {
            color: #fca5a5;
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        .error-list ul {
            margin: 0;
            padding-left: 20px;
            color: #fca5a5;
            font-size: 0.9rem;
        }
        .error-list li {
            margin-bottom: 4px;
        }
        
        .result-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .result-actions .btn {
            padding: 14px 28px;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include_once("../header-modern.php"); ?>
        <?php include_once("../menu-modern.php"); ?>
        
        <main class="main-content">
            <div class="result-container">
                <div class="result-card animate-fade-in">
                    <?php if ($sucesso && empty($erros)): ?>
                    <!-- Sucesso Total -->
                    <div class="result-icon success">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2 class="result-title">Conversão Concluída!</h2>
                    <p class="result-description">
                        <strong><?= count($arquivosProcessados) ?></strong> arquivo(s) convertido(s) com sucesso.<br>
                        Os arquivos TIFF foram salvos no histórico.
                    </p>
                    
                    <?php elseif ($sucesso && !empty($erros)): ?>
                    <!-- Sucesso com Avisos -->
                    <div class="result-icon warning">
                        <i class="fa-solid fa-exclamation"></i>
                    </div>
                    <h2 class="result-title">Concluído com Avisos</h2>
                    <p class="result-description">
                        <strong><?= count($arquivosProcessados) ?></strong> arquivo(s) convertido(s).<br>
                        Alguns erros ocorreram durante a conversão.
                    </p>
                    
                    <?php else: ?>
                    <!-- Erro -->
                    <div class="result-icon error">
                        <i class="fa-solid fa-times"></i>
                    </div>
                    <h2 class="result-title">Erro na Conversão</h2>
                    <p class="result-description">
                        Ocorreram erros durante a conversão dos arquivos.
                    </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($arquivosProcessados)): ?>
                    <!-- Arquivos Processados -->
                    <div class="files-processed">
                        <h4><i class="fa-solid fa-folder-open"></i> Arquivos Convertidos</h4>
                        <?php foreach ($arquivosProcessados as $item): ?>
                        <div class="file-item">
                            <div class="icon">
                                <i class="fa-solid fa-file-image"></i>
                            </div>
                            <div class="info">
                                <div class="nome"><?= htmlspecialchars($item['nome_original']) ?></div>
                                <div class="detalhes">Matrícula: <?= htmlspecialchars($item['matricula']) ?> • <?= htmlspecialchars($item['arquivo_tiff']) ?></div>
                            </div>
                            <div class="status">
                                <i class="fa-solid fa-check-circle"></i> Convertido
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($erros)): ?>
                    <!-- Lista de Erros -->
                    <div class="error-list">
                        <h4><i class="fa-solid fa-triangle-exclamation"></i> Erros encontrados</h4>
                        <ul>
                            <?php foreach ($erros as $erro): ?>
                            <li><?= htmlspecialchars($erro) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Botões de Ação -->
                    <div class="result-actions">
                        <?php if ($sucesso): ?>
                        <a href="historico.php" class="btn btn-primary">
                            <i class="fa-solid fa-clock-rotate-left"></i> Ir para o Histórico
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fa-solid fa-plus"></i> Nova Conversão
                        </a>
                        <?php else: ?>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fa-solid fa-arrow-left"></i> Voltar e Tentar Novamente
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include_once("../rodape-modern.php"); ?>
    </div>
    
    <script>
        <?php if ($sucesso && empty($erros)): ?>
        // Toast de sucesso
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Conversão concluída com sucesso!',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        <?php elseif ($sucesso && !empty($erros)): ?>
        // Alerta de aviso
        Swal.fire({
            icon: 'warning',
            title: 'Conversão concluída com avisos',
            text: 'Alguns arquivos não puderam ser convertidos. Verifique os detalhes na página.',
            confirmButtonColor: '#10b981'
        });
        <?php elseif (!$sucesso): ?>
        // Alerta de erro
        Swal.fire({
            icon: 'error',
            title: 'Erro na Conversão',
            text: 'Não foi possível converter os arquivos. Verifique os detalhes na página.',
            confirmButtonColor: '#10b981'
        });
        <?php endif; ?>
    </script>
</body>
</html>