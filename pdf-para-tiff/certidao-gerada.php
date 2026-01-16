<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_GET['path']) || !isset($_GET['pdf'])) {
    header('Location: historico.php');
    exit;
}

$certidaoPath = $_GET['path'];
$pdfFileName = $_GET['pdf'];
$certidaoDir = __DIR__ . '/certidao/' . $certidaoPath;
$pdfFilePath = $certidaoDir . '/' . $pdfFileName;

if (!file_exists($pdfFilePath)) {
    echo '<script>alert("PDF da certidão não encontrado!"); window.location.href = "historico.php";</script>';
    exit;
}

$certidaoInfoFile = $certidaoDir . '/certidao_info.json';
$certidaoInfo = [];
if (file_exists($certidaoInfoFile)) {
    $certidaoInfo = json_decode(file_get_contents($certidaoInfoFile), true);
}

$matricula = $certidaoInfo['matricula'] ?? '';
$codigoCertidao = $certidaoInfo['codigo'] ?? '';
$dataGeracao = $certidaoInfo['data_geracao'] ?? date('Y-m-d H:i:s');
$qtdFolhas = $certidaoInfo['qtd_folhas'] ?? 0;

$matriculaNumero = ltrim($matricula, '0');
if (empty($matriculaNumero)) $matriculaNumero = '0';

$fileSize = filesize($pdfFilePath);
$fileSizeFormatted = number_format($fileSize / 1024, 2) . ' KB';
if ($fileSize > 1048576) {
    $fileSizeFormatted = number_format($fileSize / 1048576, 2) . ' MB';
}

$dataFormatada = date('d/m/Y', strtotime($dataGeracao));
$horaFormatada = date('H:i:s', strtotime($dataGeracao));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Certidão Gerada</title>
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    <link rel="stylesheet" href="../css/docmark-modern.css">
    <style>
        .sucesso-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-2xl); overflow: hidden; max-width: 800px; margin: 0 auto; }
        
        .sucesso-header { background: linear-gradient(135deg, var(--color-accent) 0%, #059669 100%); padding: 48px 32px; text-align: center; }
        .sucesso-icon { width: 80px; height: 80px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 40px; color: var(--color-accent); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .sucesso-header h2 { color: white; margin: 0 0 8px 0; font-size: 1.75rem; }
        .sucesso-header p { color: rgba(255,255,255,0.9); margin: 0; font-size: 1.1rem; }
        
        .sucesso-body { padding: 32px; }
        
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 32px; }
        .info-item { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); padding: 20px; border-radius: var(--radius-lg); border-left: 4px solid var(--color-accent); }
        .info-item label { display: block; font-size: 0.75rem; color: var(--color-gray-400); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .info-item span { font-size: 1.25rem; font-weight: 600; color: var(--color-white); }
        
        .download-section { background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.2) 100%); border: 1px solid var(--color-accent); padding: 32px; border-radius: var(--radius-xl); text-align: center; margin-bottom: 24px; }
        .download-section h3 { color: var(--color-accent-light); margin: 0 0 16px 0; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-download { background: var(--color-accent); color: white; border: none; padding: 16px 48px; border-radius: var(--radius-full); font-size: 1.125rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease; }
        .btn-download:hover { background: #059669; transform: translateY(-3px); box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4); }
        
        .acoes-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .btn-acao { padding: 16px 20px; border: none; border-radius: var(--radius-lg); font-size: 0.9rem; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-visualizar { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid #3b82f6; }
        .btn-visualizar:hover { background: #3b82f6; color: white; }
        .btn-nova { background: rgba(251, 191, 36, 0.2); color: #fbbf24; border: 1px solid #f59e0b; }
        .btn-nova:hover { background: #f59e0b; color: white; }
        .btn-historico { background: rgba(148, 163, 184, 0.2); color: #94a3b8; border: 1px solid #64748b; }
        .btn-historico:hover { background: #64748b; color: white; }
        .btn-imprimir { background: rgba(168, 85, 247, 0.2); color: #a855f7; border: 1px solid #9333ea; }
        .btn-imprimir:hover { background: #9333ea; color: white; }
        
        .aviso { background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: var(--radius-lg); padding: 20px; margin-top: 24px; }
        .aviso-header { display: flex; align-items: center; gap: 10px; color: #fbbf24; font-weight: 600; margin-bottom: 8px; }
        .aviso p { color: var(--color-gray-300); margin: 0; font-size: 0.9rem; line-height: 1.6; }
        
        @media (max-width: 600px) {
            .info-grid, .acoes-grid { grid-template-columns: 1fr; }
        }
        
        @media print {
            .page-wrapper { background: white; }
            .main-content { padding: 0; }
            .sucesso-card { box-shadow: none; border: 1px solid #ddd; }
            .sucesso-header { background: #10b981 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .acoes-grid, .download-section { display: none; }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include_once("../header-modern.php"); ?>
        <?php include_once("../menu-modern.php"); ?>
        
        <main class="main-content">
            <div class="sucesso-card animate-fade-in">
                <div class="sucesso-header">
                    <div class="sucesso-icon">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2>Certidão Gerada com Sucesso!</h2>
                    <p>A certidão da Matrícula nº <?= $matriculaNumero ?> está pronta para download.</p>
                </div>
                
                <div class="sucesso-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Matrícula</label>
                            <span>Nº <?= $matriculaNumero ?></span>
                        </div>
                        <div class="info-item">
                            <label>Código da Certidão</label>
                            <span><?= htmlspecialchars($codigoCertidao) ?></span>
                        </div>
                        <div class="info-item">
                            <label>Quantidade de Folhas</label>
                            <span><?= $qtdFolhas ?> folha(s)</span>
                        </div>
                        <div class="info-item">
                            <label>Tamanho do Arquivo</label>
                            <span><?= $fileSizeFormatted ?></span>
                        </div>
                        <div class="info-item">
                            <label>Data de Geração</label>
                            <span><?= $dataFormatada ?></span>
                        </div>
                        <div class="info-item">
                            <label>Horário</label>
                            <span><?= $horaFormatada ?></span>
                        </div>
                    </div>
                    
                    <div class="download-section">
                        <h3><i class="fa-solid fa-file-pdf"></i> Download da Certidão</h3>
                        <a href="certidao/<?= htmlspecialchars($certidaoPath) ?>/<?= htmlspecialchars($pdfFileName) ?>" class="btn-download" download>
                            <i class="fa-solid fa-download"></i> Baixar PDF da Certidão
                        </a>
                    </div>
                    
                    <div class="acoes-grid">
                        <a href="certidao/<?= htmlspecialchars($certidaoPath) ?>/<?= htmlspecialchars($pdfFileName) ?>" class="btn-acao btn-visualizar" target="_blank">
                            <i class="fa-solid fa-eye"></i> Visualizar PDF
                        </a>
                        <a href="historico.php" class="btn-acao btn-historico">
                            <i class="fa-solid fa-clock-rotate-left"></i> Voltar ao Histórico
                        </a>
                    </div>
                    
                    <div class="aviso">
                        <div class="aviso-header">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Importante
                        </div>
                        <p>
                            Esta certidão é válida por <strong>30 (trinta) dias</strong> a contar da data de sua emissão, 
                            conforme art. 557, Código de Normas da CGJ/MA e inciso IV do artigo 1° do Decreto Federal n° 93.240/1986.
                        </p>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include_once("../rodape-modern.php"); ?>
    </div>
</body>
</html>
