<?php
require_once 'funcoes.php';

// Verifique se a sessão está ativa
verificar_sessao_ativa();

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

// Processar arquivos
$arquivosProcessados = [];
$erro = null;

if (isset($_POST['submit']) && isset($_FILES['arquivoPDF'])) {
    $todosDados = array();

    foreach ($_FILES['arquivoPDF']['tmp_name'] as $key => $caminhoTemporario) {
        $nomeArquivo = $_FILES['arquivoPDF']['name'][$key];
        $numeroMatricula = extrairNumeroMatricula($nomeArquivo);

        $dados = array('numeroMatricula' => $numeroMatricula, 'nomeArquivo' => $nomeArquivo);
        array_push($todosDados, $dados);
        $arquivosProcessados[] = $dados;

        $diretorioDestino = 'upload/';

        if (!is_dir($diretorioDestino)) {
            mkdir($diretorioDestino, 0755, true);
        }

        $caminhoArquivoDestino = $diretorioDestino . $nomeArquivo;
        if (!move_uploaded_file($caminhoTemporario, $caminhoArquivoDestino)) {
            $erro = "Erro ao fazer upload do arquivo: " . $nomeArquivo;
        }
    }

    $json = json_encode($todosDados);
    file_put_contents('numero_matricula.json', $json);
    
    $ultimaMatricula = end($todosDados)['numeroMatricula'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Arquivos Processados</title>
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/docmark-modern.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .result-header-card {
            background: linear-gradient(135deg, var(--color-accent) 0%, #059669 100%);
            border-radius: var(--radius-xl);
            padding: 32px;
            margin-bottom: 24px;
            text-align: center;
        }
        .result-header-card h2 {
            color: white;
            margin: 0 0 8px 0;
            font-size: 1.75rem;
        }
        .result-header-card p {
            color: rgba(255,255,255,0.9);
            margin: 0;
        }
        .result-header-card .badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: var(--radius-full);
            display: inline-block;
            margin-top: 12px;
            font-weight: 600;
            color: white;
        }
        
        .files-container {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-xl);
            padding: 24px;
            margin-bottom: 24px;
        }
        .files-container h3 {
            color: var(--color-accent-light);
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
        }
        
        .file-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: var(--radius-lg);
            padding: 16px 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
        }
        .file-item:hover {
            transform: translateX(8px);
            border-color: var(--color-accent);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
        }
        .file-item:last-child {
            margin-bottom: 0;
        }
        
        .file-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .file-icon i {
            color: white;
            font-size: 1.5rem;
        }
        
        .file-info {
            flex: 1;
        }
        .file-name {
            color: var(--color-white);
            font-weight: 600;
            margin-bottom: 4px;
            word-break: break-all;
        }
        .file-matricula {
            color: var(--color-gray-400);
            font-size: 0.875rem;
        }
        .file-matricula strong {
            color: var(--color-accent-light);
        }
        
        .file-status {
            background: rgba(16, 185, 129, 0.2);
            color: var(--color-accent-light);
            padding: 6px 14px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .action-container {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-xl);
            padding: 24px;
            text-align: center;
        }
        .action-container p {
            color: var(--color-gray-300);
            margin: 0 0 20px 0;
        }
        .action-container .btn {
            padding: 14px 32px;
            font-size: 1rem;
        }
        
        .error-alert {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: var(--radius-lg);
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fca5a5;
        }
        .error-alert i {
            color: #ef4444;
            font-size: 1.25rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-gray-400);
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        .empty-state h3 {
            color: var(--color-white);
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include_once("../header-modern.php"); ?>
        <?php include_once("../menu-modern.php"); ?>
        
        <main class="main-content">
            <!-- Header -->
            <div class="result-header-card animate-fade-in">
                <h2><i class="fa-solid fa-check-circle"></i> Arquivos Processados</h2>
                <p>Upload concluído com sucesso</p>
                <?php if (!empty($arquivosProcessados)): ?>
                <div class="badge">
                    <i class="fa-solid fa-file-pdf"></i> <?= count($arquivosProcessados) ?> arquivo(s)
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($erro): ?>
            <div class="error-alert animate-slide-up">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($erro) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($arquivosProcessados)): ?>
            <!-- Lista de Arquivos -->
            <div class="files-container animate-slide-up">
                <h3><i class="fa-solid fa-folder-open"></i> Arquivos Enviados</h3>
                
                <?php foreach ($arquivosProcessados as $arquivo): ?>
                <div class="file-item">
                    <div class="file-icon">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <div class="file-info">
                        <div class="file-name"><?= htmlspecialchars($arquivo['nomeArquivo']) ?></div>
                        <div class="file-matricula">Matrícula nº: <strong><?= htmlspecialchars($arquivo['numeroMatricula']) ?></strong></div>
                    </div>
                    <div class="file-status">
                        <i class="fa-solid fa-check-circle"></i> Pronto
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Ação -->
            <div class="action-container animate-slide-up">
                <p><i class="fa-solid fa-info-circle"></i> Clique no botão abaixo para gerar o CNM e aplicar o carimbo digital nos arquivos.</p>
                <form action="gerar_cnm.php" method="POST" id="formGerar">
                    <input type="hidden" name="numeroMatricula" value="<?= htmlspecialchars($ultimaMatricula) ?>">
                    <button type="submit" name="submit" class="btn btn-primary" id="btnGerar">
                        <i class="fa-solid fa-stamp"></i> Gerar CNM e Carimbar
                    </button>
                </form>
            </div>
            
            <?php else: ?>
            <!-- Estado Vazio -->
            <div class="files-container animate-slide-up">
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    <h3>Nenhum arquivo processado</h3>
                    <p>Faça o upload de arquivos PDF para processá-los.</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fa-solid fa-upload"></i> Fazer Upload
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </main>
        
        <?php include_once("../rodape-modern.php"); ?>
    </div>
    
    <!-- Loading Modal -->
    <div id="loadingModal" class="modal-backdrop">
        <div class="modal" style="text-align: center; max-width: 350px;">
            <div class="spinner" style="margin: 0 auto 20px;"></div>
            <h3 style="margin-bottom: 8px;">Processando...</h3>
            <p style="color: var(--color-gray-400); margin: 0;">Por favor, aguarde enquanto os arquivos estão sendo carimbados.</p>
        </div>
    </div>
    
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script>
        // Loading ao submeter
        document.getElementById('formGerar')?.addEventListener('submit', function() {
            document.getElementById('loadingModal').classList.add('active');
        });
    </script>
</body>
</html>