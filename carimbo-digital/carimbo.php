<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

require_once('tcpdf/tcpdf.php');
require_once('fpdf/fpdf.php');
require_once('src/autoload.php');

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;
use setasign\Fpdi\FpdiTpl;

// Função para adicionar o carimbo digital em cada página do PDF
function addStampToPDF($pdfPath, $stampText)
{
    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($pdfPath);
    for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
        $pdf->AddPage();
        $templateId = $pdf->importPage($pageNumber);
        $pdf->useTemplate($templateId);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(145, 4);
        $pdf->Cell(0, 0, 'CNM: ' . $stampText, 0, false, 'L');
    }
    return $pdf;
}

// Função para copiar arquivo TIFF para diretório histórico
function copyToHistory($tiffFileName)
{
    $source = 'upload/' . $tiffFileName;
    $destination = '../pdf-para-tiff/historico/' . $tiffFileName;
    if (!copy($source, $destination)) {
        return false;
    }
    return true;
}

// Função para copiar arquivo PDF para diretório pdf-viw
function copyToPdfViw($pdfFileName, $numeroMatricula)
{
    $source = 'upload/' . $pdfFileName;
    $destination = '../pdf-para-tiff/pdf-viw/' . $numeroMatricula . '.pdf';
    if (!copy($source, $destination)) {
        return false;
    }
    return true;
}

// Variáveis para controle de erros e sucesso
$erros = [];
$sucesso = false;
$arquivosProcessados = 0;
$matriculasProcessadas = [];

// Verifica se o arquivo "cnm.json" existe
if (file_exists('cnm.json')) {
    $cnmData = json_decode(file_get_contents('cnm.json'), true);
    $pdfFiles = array();

    foreach ($cnmData as $item) {
        if (isset($item['cnm']) && isset($item['nomeArquivo'])) {
            $cnm = $item['cnm'];
            $nomeArquivo = $item['nomeArquivo'];
            $pdfPath = 'upload/' . $nomeArquivo;
            
            if (file_exists($pdfPath)) {
                $pdf = addStampToPDF($pdfPath, $cnm);
                $numeroMatricula = preg_replace('/\D/', '', $nomeArquivo);
                $numeroMatricula = str_pad($numeroMatricula, 8, '0', STR_PAD_LEFT);
                $newFileName = $numeroMatricula . '.pdf';
                array_push($pdfFiles, $newFileName);
                $pdf->Output('F', 'upload/' . $newFileName);

                $tiffFileName = $numeroMatricula . '.tiff';
                $command = 'magick convert -density 200 -monochrome -compress Group4 "upload/' . $newFileName . '" "upload/' . $tiffFileName . '"';
                exec($command);

                if (!copyToHistory($tiffFileName)) {
                    $erros[] = "Erro ao copiar $tiffFileName para o diretório histórico.";
                }

                if (!copyToPdfViw($newFileName, $numeroMatricula)) {
                    $erros[] = "Erro ao copiar $newFileName para o diretório pdf-viw.";
                }

                array_push($pdfFiles, $tiffFileName);
                unlink('upload/' . $newFileName);
                
                $arquivosProcessados++;
                $matriculasProcessadas[] = ['matricula' => ltrim($numeroMatricula, '0'), 'cnm' => $cnm];
            } else {
                $erros[] = "Arquivo PDF '$nomeArquivo' não encontrado.";
            }
        } else {
            $erros[] = "Campo 'cnm' e/ou 'nomeArquivo' não encontrados no arquivo 'cnm.json'.";
        }
    }

    $dateTime = new DateTime();
    $timestamp = $dateTime->format('Ymd_His');
    $zipFileName = 'arquivos_' . $timestamp . '.zip';
    $zipFilePath = 'arquivos/' . $zipFileName;

    $zip = new ZipArchive;
    if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
        foreach ($pdfFiles as $pdfFile) {
            if (file_exists('upload/' . $pdfFile)) {
                $zip->addFile('upload/' . $pdfFile, $pdfFile);
            }
        }
        $zip->close();
        $sucesso = true;

        foreach ($pdfFiles as $pdfFile) {
            if (file_exists('upload/' . $pdfFile)) {
                unlink('upload/' . $pdfFile);
            }
        }

        $pdfFilesToDelete = glob('upload/*.pdf');
        foreach ($pdfFilesToDelete as $file) {
            unlink($file);
        }
    } else {
        $erros[] = "Erro ao criar o arquivo zip.";
    }
} else {
    $erros[] = "Arquivo 'cnm.json' não encontrado.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Resultado do Processamento</title>
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
        .file-item .matricula {
            color: var(--color-white);
            font-weight: 600;
        }
        .file-item .cnm {
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
                    <h2 class="result-title">Processamento Concluído!</h2>
                    <p class="result-description">
                        <strong><?= $arquivosProcessados ?></strong> arquivo(s) carimbado(s) com sucesso.<br>
                        Os arquivos TIFF foram salvos no histórico.
                    </p>
                    
                    <?php elseif ($sucesso && !empty($erros)): ?>
                    <!-- Sucesso com Avisos -->
                    <div class="result-icon warning">
                        <i class="fa-solid fa-exclamation"></i>
                    </div>
                    <h2 class="result-title">Concluído com Avisos</h2>
                    <p class="result-description">
                        <strong><?= $arquivosProcessados ?></strong> arquivo(s) processado(s).<br>
                        Alguns erros ocorreram durante o processamento.
                    </p>
                    
                    <?php else: ?>
                    <!-- Erro -->
                    <div class="result-icon error">
                        <i class="fa-solid fa-times"></i>
                    </div>
                    <h2 class="result-title">Erro no Processamento</h2>
                    <p class="result-description">
                        Ocorreram erros durante o processamento dos arquivos.
                    </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($matriculasProcessadas)): ?>
                    <!-- Arquivos Processados -->
                    <div class="files-processed">
                        <h4><i class="fa-solid fa-folder-open"></i> Arquivos Processados</h4>
                        <?php foreach ($matriculasProcessadas as $item): ?>
                        <div class="file-item">
                            <div class="icon">
                                <i class="fa-solid fa-file-pdf"></i>
                            </div>
                            <div class="info">
                                <div class="matricula">Matrícula nº <?= htmlspecialchars($item['matricula']) ?></div>
                                <div class="cnm">CNM: <?= htmlspecialchars($item['cnm']) ?></div>
                            </div>
                            <div class="status">
                                <i class="fa-solid fa-check-circle"></i> Carimbado
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
                        <a href="../pdf-para-tiff/historico.php" class="btn btn-primary">
                            <i class="fa-solid fa-clock-rotate-left"></i> Ir para o Histórico
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fa-solid fa-plus"></i> Novo Carimbo
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
            title: 'Processamento concluído com sucesso!',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        <?php elseif (!$sucesso): ?>
        // Alerta de erro
        Swal.fire({
            icon: 'error',
            title: 'Erro no Processamento',
            text: 'Verifique os detalhes na página.',
            confirmButtonColor: '#10b981'
        });
        <?php endif; ?>
    </script>
</body>
</html>