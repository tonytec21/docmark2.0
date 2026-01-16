<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

require_once('../carimbo-digital/tcpdf/tcpdf.php');
require_once('../carimbo-digital/fpdf/fpdf.php');
require_once '../carimbo-digital/src/autoload.php';

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;

function copyFiles($sourceDir, $targetDir) {
    if (!is_dir($sourceDir)) {
        return false;
    }

    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            return false;
        }
    }

    $files = glob($sourceDir . '*');

    foreach ($files as $file) {
        $targetFile = $targetDir . basename($file);
        copy($file, $targetFile);
    }
    return true;
}

function deleteFiles($target) {
    if (is_dir($target)) {
        $files = glob($target . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file))
                deleteFiles($file);
            else
                unlink($file);
        }
    }
}

function formatarNomeArquivo($nomeOriginal) {
    preg_match_all('/\d+/', $nomeOriginal, $matches);
    $numeros = implode('', $matches[0]);

    if (!$numeros) {
        return false;
    }

    $numeros = str_pad($numeros, 8, '0', STR_PAD_LEFT);
    return $numeros . '.pdf';
}

// Variáveis para controle
$erros = [];
$sucesso = false;
$arquivosProcessados = [];

if (isset($_FILES['pdf'])) {
    $file_names = $_FILES['pdf']['name'];
    $file_tmps = $_FILES['pdf']['tmp_name'];

    $extensions = array("pdf");
    $upload_dir = "pdfs/";
    $temp_dir = "temp/";
    $temp_dir2 = "../pdf-para-tiff/pdf-viw/";
    $output_dir = "arquivos/";
    $stamp_image = 'config/chancela.png';
    $stamp_image_2 = 'config/chancela-2.png';
    $zip_file = 'arquivos_' . date('Ymd_His') . '.zip';
    $zip_path = $output_dir . $zip_file;
    $zip = new ZipArchive;

    copyFiles($temp_dir, $temp_dir2);
    deleteFiles($upload_dir);
    deleteFiles($temp_dir);

    if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
        foreach ($file_names as $key => $file_name) {
            $formatted_name = formatarNomeArquivo($file_name);
            
            if (!$formatted_name) {
                $erros[] = "Nome do arquivo '$file_name' precisa conter ao menos um número.";
                continue;
            }
            
            $file_tmp = $file_tmps[$key];

            if (in_array(pathinfo($file_name, PATHINFO_EXTENSION), $extensions) === false) {
                $erros[] = "Arquivo '$file_name' não é um PDF válido.";
                continue;
            }

            $dest_file_path = $upload_dir . $formatted_name;
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            move_uploaded_file($file_tmp, $dest_file_path);

            if (!file_exists($dest_file_path)) {
                $erros[] = "Falha ao mover o arquivo '$file_name' para o diretório.";
                continue;
            }

            $pdf = new Fpdi();

            try {
                $pageCount = $pdf->setSourceFile($dest_file_path);
            } catch (Exception $e) {
                $erros[] = "Falha ao ler o arquivo '$file_name': " . $e->getMessage();
                continue;
            }

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $pdf->AddPage();
                $templateId = $pdf->importPage($pageNo);
                $pdf->useTemplate($templateId);

                $pdf->Image($stamp_image, 190, 2, 18, 0, 'PNG');
                $pdf->Image($stamp_image_2, 5, 200, 10, 0, 'PNG');
            }

            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0777, true);
            }

            $output_file = $temp_dir . $formatted_name;
            $pdf->Output('F', $output_file);

            $zip->addFile($output_file, basename($output_file));

            // Convertendo para TIFF e salvando em "historico"
            try {
                $nomeArquivoTIFF = str_replace('.pdf', '.tiff', $formatted_name);
                $arquivoTIFF = "../pdf-para-tiff/historico/" . $nomeArquivoTIFF;
                $comandoImageMagick = "magick convert -density 200 -monochrome -compress Group4 {$output_file} {$arquivoTIFF}";
                exec($comandoImageMagick);
            } catch (Exception $e) {
                $erros[] = "Erro na conversão TIFF do arquivo '$file_name': " . $e->getMessage();
            }
            
            // Adicionar à lista de processados
            $matricula = str_replace('.pdf', '', $formatted_name);
            $arquivosProcessados[] = [
                'nome_original' => $file_name,
                'matricula' => ltrim($matricula, '0'),
                'paginas' => $pageCount
            ];
        }

        $zip->close();
        copyFiles($temp_dir, $temp_dir2);
        
        if (!empty($arquivosProcessados)) {
            $sucesso = true;
        }
    } else {
        $erros[] = "Falha ao criar o arquivo ZIP.";
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
                    <h2 class="result-title">Processamento Concluído!</h2>
                    <p class="result-description">
                        <strong><?= count($arquivosProcessados) ?></strong> arquivo(s) processado(s) com sucesso.<br>
                        O sinal público foi adicionado e os arquivos TIFF foram salvos no histórico.
                    </p>
                    
                    <?php elseif ($sucesso && !empty($erros)): ?>
                    <!-- Sucesso com Avisos -->
                    <div class="result-icon warning">
                        <i class="fa-solid fa-exclamation"></i>
                    </div>
                    <h2 class="result-title">Concluído com Avisos</h2>
                    <p class="result-description">
                        <strong><?= count($arquivosProcessados) ?></strong> arquivo(s) processado(s).<br>
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
                    
                    <?php if (!empty($arquivosProcessados)): ?>
                    <!-- Arquivos Processados -->
                    <div class="files-processed">
                        <h4><i class="fa-solid fa-folder-open"></i> Arquivos Processados</h4>
                        <?php foreach ($arquivosProcessados as $item): ?>
                        <div class="file-item">
                            <div class="icon">
                                <i class="fa-solid fa-file-pdf"></i>
                            </div>
                            <div class="info">
                                <div class="nome"><?= htmlspecialchars($item['nome_original']) ?></div>
                                <div class="detalhes">Matrícula: <?= htmlspecialchars($item['matricula']) ?> • <?= $item['paginas'] ?> página(s)</div>
                            </div>
                            <div class="status">
                                <i class="fa-solid fa-check-circle"></i> Processado
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
                            <i class="fa-solid fa-plus"></i> Novo Processamento
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
        <?php elseif ($sucesso && !empty($erros)): ?>
        // Alerta de aviso
        Swal.fire({
            icon: 'warning',
            title: 'Processamento concluído com avisos',
            text: 'Alguns arquivos não puderam ser processados. Verifique os detalhes na página.',
            confirmButtonColor: '#10b981'
        });
        <?php elseif (!$sucesso): ?>
        // Alerta de erro
        Swal.fire({
            icon: 'error',
            title: 'Erro no Processamento',
            text: 'Não foi possível processar os arquivos. Verifique os detalhes na página.',
            confirmButtonColor: '#10b981'
        });
        <?php endif; ?>
    </script>
</body>
</html>
