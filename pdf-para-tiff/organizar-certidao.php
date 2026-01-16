<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_GET['path']) || empty($_GET['path'])) {
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<script>Swal.fire({icon:"error",title:"Erro",text:"Certidão não encontrada!",confirmButtonColor:"#10b981"}).then(()=>{window.location.href="historico.php";});</script>';
    exit;
}

$certidaoPath = $_GET['path'];
$certidaoDir = __DIR__ . '/certidao/' . $certidaoPath;

if (!is_dir($certidaoDir)) {
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<script>Swal.fire({icon:"error",title:"Erro",text:"Diretório da certidão não encontrado!",confirmButtonColor:"#10b981"}).then(()=>{window.location.href="historico.php";});</script>';
    exit;
}

$certidaoInfoFile = $certidaoDir . '/certidao_info.json';
if (!file_exists($certidaoInfoFile)) {
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<script>Swal.fire({icon:"error",title:"Erro",text:"Informações da certidão não encontradas!",confirmButtonColor:"#10b981"}).then(()=>{window.location.href="historico.php";});</script>';
    exit;
}

$certidaoInfo = json_decode(file_get_contents($certidaoInfoFile), true);
$matricula = $certidaoInfo['matricula'];
$codigoCertidao = $certidaoInfo['codigo'];

$imagens = glob($certidaoDir . '/*.jpg');
sort($imagens);

$matriculaNumero = ltrim($matricula, '0');
if (empty($matriculaNumero)) $matriculaNumero = '0';

// Verificar se já existem selos para esta certidão
$selosFile = $certidaoDir . '/selos.json';
$selosExistentes = [];
if (file_exists($selosFile)) {
    $selosExistentes = json_decode(file_get_contents($selosFile), true) ?? [];
}

// Obter nome do usuário logado
$usuarioLogado = $_SESSION['nome_completo'] ?? $_SESSION['usuario'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Emitir Certidão - Matrícula <?= $matriculaNumero ?></title>
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/docmark-modern.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        .certidao-header-card { background: linear-gradient(135deg, var(--color-accent) 0%, #059669 100%); border-radius: var(--radius-xl); padding: 32px; margin-bottom: 24px; text-align: center; }
        .certidao-header-card h2 { color: white; margin: 0 0 8px 0; font-size: 1.75rem; }
        .certidao-header-card p { color: rgba(255,255,255,0.9); margin: 0; }
        .certidao-header-card .codigo { background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: var(--radius-full); display: inline-block; margin-top: 12px; font-weight: 600; color: white; }
        
        .instrucoes-card { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 24px; }
        .instrucoes-card h4 { color: var(--color-accent-light); margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px; }
        .instrucoes-card ul { margin: 0; padding-left: 24px; color: var(--color-gray-300); }
        .instrucoes-card li { margin-bottom: 8px; }
        .instrucoes-card strong { color: var(--color-white); }
        
        .acoes-rapidas { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        
        .imagens-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px; }
        
        .imagem-item { background: rgba(255, 255, 255, 0.03); border: 2px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-lg); padding: 12px; cursor: move; transition: all 0.3s ease; position: relative; }
        .imagem-item:hover { border-color: var(--color-accent); transform: translateY(-4px); box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2); }
        .imagem-item.selected { border-color: var(--color-accent); background: rgba(16, 185, 129, 0.1); }
        .imagem-item.removed { border-color: #f87171; background: rgba(248, 113, 113, 0.1); opacity: 0.6; }
        .imagem-item img { width: 100%; height: auto; border-radius: var(--radius-md); display: block; }
        
        .imagem-info { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1); }
        .imagem-numero { background: var(--color-accent); color: white; padding: 6px 14px; border-radius: var(--radius-full); font-weight: 600; font-size: 0.875rem; }
        .imagem-item.removed .imagem-numero { background: #f87171; }
        
        .imagem-checkbox { width: 20px; height: 20px; cursor: pointer; accent-color: var(--color-accent); }
        
        .btn-remover { background: rgba(248, 113, 113, 0.2); color: #f87171; border: 1px solid #f87171; padding: 6px 12px; border-radius: var(--radius-md); cursor: pointer; font-size: 0.75rem; transition: all 0.2s; }
        .btn-remover:hover { background: #f87171; color: white; }
        .btn-restaurar { background: rgba(16, 185, 129, 0.2); color: var(--color-accent-light); border: 1px solid var(--color-accent); }
        .btn-restaurar:hover { background: var(--color-accent); color: white; }
        
        .acoes-container { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); padding: 24px; border-radius: var(--radius-xl); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; backdrop-filter: blur(10px); margin-top: 24px; }
        .acoes-info { font-size: 1rem; color: var(--color-gray-300); }
        .acoes-info strong { color: var(--color-accent-light); }
        .acoes-botoes { display: flex; gap: 12px; }
        
        .sortable-ghost { opacity: 0.4; background: rgba(16, 185, 129, 0.2); }
        .sortable-chosen { box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4); }
        
        /* Estilos do Selo */
        .selo-section { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-xl); padding: 24px; margin-bottom: 24px; }
        .selo-section h3 { color: var(--color-accent-light); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; font-size: 1.25rem; }
        .selo-section .hint { color: var(--color-gray-400); font-size: 0.875rem; margin-bottom: 20px; }
        
        .selo-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end; }
        .selo-form .form-group { margin-bottom: 0; }
        .selo-form .form-group label { display: block; margin-bottom: 6px; color: var(--color-gray-300); font-size: 0.875rem; }
        .selo-form .form-control { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-md); color: var(--color-white); padding: 10px 14px; width: 100%; }
        .selo-form .form-control:focus { outline: none; border-color: var(--color-accent); box-shadow: 0 0 0 3px var(--color-accent-glow); }
        .selo-form select.form-control { cursor: pointer; background-color: #1e293b; }
        .selo-form select.form-control option { background-color: #1e293b; color: #fff; padding: 10px; }
        .selo-form select.form-control optgroup { background-color: #0f172a; color: #94a3b8; font-weight: 600; }
        .selo-form select.form-control option:hover, .selo-form select.form-control option:checked { background-color: #334155; }
        
        .switch-wrapper { display: flex; align-items: center; gap: 10px; padding: 10px 0; }
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.1); transition: .3s; border-radius: 26px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--color-accent); }
        input:checked + .slider:before { transform: translateX(24px); }
        .switch-label { color: var(--color-gray-300); font-size: 0.875rem; }
        
        .motivo-wrapper { grid-column: 1 / -1; display: none; }
        .motivo-wrapper.show { display: block; }
        
        .btn-selo { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; color: white; padding: 10px 20px; border-radius: var(--radius-md); cursor: pointer; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 8px; }
        .btn-selo:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4); }
        .btn-selo:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        
        /* Selos gerados */
        .selos-container { margin-top: 20px; }
        .selo-card { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: var(--radius-lg); padding: 16px; margin-bottom: 12px; }
        .selo-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .selo-title { color: var(--color-white); font-weight: 600; }
        .selo-badge { background: rgba(16, 185, 129, 0.2); color: var(--color-accent-light); padding: 4px 12px; border-radius: var(--radius-full); font-size: 0.75rem; display: flex; align-items: center; gap: 6px; }
        .selo-body { display: flex; gap: 16px; align-items: flex-start; }
        .selo-qr { flex-shrink: 0; }
        .selo-qr img { width: 100px; height: 100px; border-radius: var(--radius-md); background: white; padding: 4px; }
        .selo-info { flex: 1; }
        .selo-numero { color: var(--color-accent-light); font-size: 0.875rem; margin-bottom: 8px; }
        .selo-numero strong { color: var(--color-white); }
        .selo-texto { color: var(--color-gray-400); font-size: 0.8rem; line-height: 1.5; margin: 0; max-height: 100px; overflow-y: auto; }
        
        .btn-add-selo { background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid #3b82f6; padding: 8px 16px; border-radius: var(--radius-md); cursor: pointer; font-size: 0.875rem; transition: all 0.2s; }
        .btn-add-selo:hover { background: #3b82f6; color: white; }
        
        .selo-form-container { display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.1); }
        .selo-form-container.show { display: block; }
        
        /* Botões de adicionar/remover partes */
        .btn-add-parte, .btn-remove-parte { width: 38px; height: 38px; border-radius: var(--radius-md); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0; }
        .btn-add-parte { background: rgba(16, 185, 129, 0.2); color: var(--color-accent-light); border: 1px solid var(--color-accent); }
        .btn-add-parte:hover { background: var(--color-accent); color: white; }
        .btn-remove-parte { background: rgba(248, 113, 113, 0.2); color: #f87171; border: 1px solid #f87171; }
        .btn-remove-parte:hover { background: #f87171; color: white; }
        .parte-row { animation: slideIn 0.2s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Alerta de selo */
        .selo-alert { background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: var(--radius-lg); padding: 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .selo-alert i { color: #fbbf24; font-size: 1.5rem; }
        .selo-alert-text { color: var(--color-gray-300); }
        .selo-alert-text strong { color: var(--color-white); }
        
        /* Tipo de Certidão */
        .tipo-certidao-section { background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 24px; }
        .tipo-certidao-section h4 { color: #60a5fa; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px; }
        .tipo-certidao-options { display: flex; gap: 16px; flex-wrap: wrap; }
        .tipo-certidao-option { flex: 1; min-width: 200px; background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-lg); padding: 20px; cursor: pointer; transition: all 0.3s ease; text-align: center; }
        .tipo-certidao-option:hover { border-color: rgba(59, 130, 246, 0.5); background: rgba(59, 130, 246, 0.1); }
        .tipo-certidao-option.selected { border-color: #3b82f6; background: rgba(59, 130, 246, 0.15); }
        .tipo-certidao-option input { display: none; }
        .tipo-certidao-option .icon { font-size: 2.5rem; margin-bottom: 12px; color: #60a5fa; }
        .tipo-certidao-option .title { color: var(--color-white); font-weight: 600; font-size: 1.1rem; margin-bottom: 4px; }
        .tipo-certidao-option .desc { color: var(--color-gray-400); font-size: 0.85rem; }
        .tipo-certidao-option.selected .icon { color: #3b82f6; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include_once("../header-modern.php"); ?>
        <?php include_once("../menu-modern.php"); ?>
        
        <main class="main-content">
            <!-- Header -->
            <div class="certidao-header-card animate-fade-in">
                <h2><i class="fa-solid fa-file-circle-check"></i> Emissão de Certidão</h2>
                <p>Matrícula nº <?= $matriculaNumero ?></p>
                <div class="codigo">Código: <?= $codigoCertidao ?></div>
            </div>
            
            <!-- Seção de Selos -->
            <div class="selo-section animate-slide-up">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3><i class="fa-solid fa-stamp"></i> Solicitar Selo</h3>
                    <?php if (!empty($selosExistentes)): ?>
                    <button type="button" class="btn-add-selo" id="btnAddSelo">
                        <i class="fa-solid fa-plus"></i> Adicionar mais selo
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($selosExistentes)): ?>
                <div class="selo-alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div class="selo-alert-text">
                        <strong>Atenção:</strong> Esta certidão ainda não possui selos. Solicite os selos antes de gerar o PDF final.
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Selos existentes -->
                <?php if (!empty($selosExistentes)): ?>
                <div class="selos-container" id="selosContainer">
                    <?php foreach ($selosExistentes as $selo): ?>
                    <div class="selo-card">
                        <div class="selo-header">
                            <span class="selo-title">Poder Judiciário – TJMA</span>
                            <span class="selo-badge"><i class="fa-solid fa-check-circle"></i> Selo gerado</span>
                        </div>
                        <div class="selo-body">
                            <?php if (!empty($selo['qr_code'])): ?>
                            <div class="selo-qr">
                                <img src="data:image/png;base64,<?= $selo['qr_code'] ?>" alt="QR Code">
                            </div>
                            <?php endif; ?>
                            <div class="selo-info">
                                <div class="selo-numero">Selo: <strong><?= htmlspecialchars($selo['numero_selo']) ?></strong></div>
                                <p class="selo-texto"><?= nl2br(htmlspecialchars($selo['texto_selo'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Formulário de Selo -->
                <div class="selo-form-container <?= empty($selosExistentes) ? 'show' : '' ?>" id="seloFormContainer">
                    <p class="hint">Informe o ato, as partes envolvidas e a quantidade de selos. Os demais campos são preenchidos automaticamente.</p>
                    
                    <form id="seloForm" class="selo-form">
                        <input type="hidden" name="certidao_path" value="<?= htmlspecialchars($certidaoPath) ?>">
                        <input type="hidden" name="matricula" value="<?= htmlspecialchars($matricula) ?>">
                        <input type="hidden" name="escrevente" value="<?= htmlspecialchars($usuarioLogado) ?>">
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="partes">Nome partes: <small style="color: var(--color-gray-400);">(clique em + para adicionar mais)</small></label>
                            <div id="partesContainer">
                                <div class="parte-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                                    <input type="text" name="partes[]" class="form-control parte-input" placeholder="Nome da parte envolvida" style="flex: 1;" required>
                                    <button type="button" class="btn-add-parte" onclick="adicionarParte()" title="Adicionar parte">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="ato">Ato</label>
                            <select id="ato" name="ato" class="form-control" required>
                                <optgroup label="Certidões de RI">
                                    <option value="">Selecione</option>
                                    <option value="16.24.1">16.24.1 - Das certidões: Com uma folha</option>
                                    <option value="16.24.2">16.24.2 - Das certidões: Por folha acrescida além da primeira</option>
                                    <option value="16.24.4">16.24.4 - Certidões de inteiro teor, ônus e de ações reais e pessoais reipersecutórias e de cadeia dominial, com uma folha</option>
                                    <option value="16.24.4.1">16.24.4.1 - Por folha acrescida além da primeira</option>
                                </optgroup>
                                <optgroup label="Arquivamento">
                                    <option value="16.39">16.39 - Arquivamento, por folha do documento</option>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tabela_custas">Tabela de Custas</label>
                            <select id="tabela_custas" name="tabela_custas" class="form-control">
                                <option value="2026" selected>2026</option>
                                <option value="2025">2025</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantidade">Quantidade</label>
                            <input type="number" id="quantidade" name="quantidade" class="form-control" min="1" value="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Selo isento?</label>
                            <div class="switch-wrapper">
                                <label class="switch">
                                    <input type="checkbox" id="isento" name="isento">
                                    <span class="slider"></span>
                                </label>
                                <span class="switch-label">Isento</span>
                            </div>
                        </div>
                        
                        <div class="form-group motivo-wrapper" id="motivoWrapper">
                            <label for="motivo_isencao">Motivo da isenção</label>
                            <input type="text" id="motivo_isencao" name="motivo_isencao" class="form-control" placeholder="Descreva o motivo da isenção">
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-selo" id="btnSolicitar">
                                <i class="fa-solid fa-stamp"></i> Solicitar Selo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tipo de Certidão -->
            <div class="tipo-certidao-section animate-slide-up">
                <h4><i class="fa-solid fa-file-signature"></i> Tipo de Certidão</h4>
                <div class="tipo-certidao-options">
                    <label class="tipo-certidao-option selected" id="optionEletronica">
                        <input type="radio" name="tipo_certidao" value="eletronica" checked>
                        <div class="icon"><i class="fa-solid fa-laptop-file"></i></div>
                        <div class="title">Certidão Eletrônica</div>
                        <div class="desc">Assinatura digital (sem linha de assinatura)</div>
                    </label>
                    <label class="tipo-certidao-option" id="optionFisica">
                        <input type="radio" name="tipo_certidao" value="fisica">
                        <div class="icon"><i class="fa-solid fa-file-pen"></i></div>
                        <div class="title">Certidão Física</div>
                        <div class="desc">Com linha para assinatura manuscrita</div>
                    </label>
                </div>
            </div>
            
            <!-- Instruções -->
            <div class="instrucoes-card animate-slide-up">
                <h4><i class="fa-solid fa-circle-info"></i> Instruções</h4>
                <ul>
                    <li><strong>Selecionar:</strong> Clique nas imagens que deseja incluir na certidão (checkbox)</li>
                    <li><strong>Ordenar:</strong> Arraste e solte as imagens para alterar a ordem</li>
                    <li><strong>Remover:</strong> Clique em "Remover" para excluir uma imagem da certidão</li>
                    <li><strong>Gerar:</strong> Após selecionar e ordenar, clique em "Gerar Certidão"</li>
                </ul>
            </div>
            
            <!-- Ações Rápidas -->
            <div class="acoes-rapidas animate-slide-up">
                <button type="button" class="btn btn-primary" onclick="selecionarTodas()">
                    <i class="fa-solid fa-check-double"></i> Selecionar Todas
                </button>
                <button type="button" class="btn btn-secondary" onclick="desmarcarTodas()">
                    <i class="fa-solid fa-xmark"></i> Desmarcar Todas
                </button>
            </div>
            
            <!-- Formulário -->
            <form id="certidaoForm" action="gerar-certidao.php" method="POST">
                <input type="hidden" name="certidao_path" value="<?= htmlspecialchars($certidaoPath) ?>">
                <input type="hidden" name="matricula" value="<?= htmlspecialchars($matricula) ?>">
                <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigoCertidao) ?>">
                <input type="hidden" name="imagens_ordem" id="imagensOrdem" value="">
                <input type="hidden" name="tipo_certidao" id="tipoCertidaoInput" value="eletronica">
                
                <div class="imagens-grid animate-slide-up" id="imagensGrid">
                    <?php foreach ($imagens as $index => $imagem): ?>
                        <?php $nomeImagem = basename($imagem); ?>
                        <div class="imagem-item selected" data-imagem="<?= htmlspecialchars($nomeImagem) ?>">
                            <img src="certidao/<?= htmlspecialchars($certidaoPath) ?>/<?= htmlspecialchars($nomeImagem) ?>" alt="Página <?= $index + 1 ?>">
                            <div class="imagem-info">
                                <span class="imagem-numero">Página <?= $index + 1 ?></span>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" class="imagem-checkbox" name="imagens_selecionadas[]" value="<?= htmlspecialchars($nomeImagem) ?>" checked>
                                    <button type="button" class="btn-remover" onclick="toggleRemover(this)">Remover</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="acoes-container">
                    <div class="acoes-info">
                        <i class="fa-solid fa-images"></i>
                        <strong id="contadorSelecionadas"><?= count($imagens) ?></strong> de <?= count($imagens) ?> páginas selecionadas
                    </div>
                    <div class="acoes-botoes">
                        <button type="button" class="btn btn-secondary" onclick="cancelar()">
                            <i class="fa-solid fa-arrow-left"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnGerar">
                            <i class="fa-solid fa-file-pdf"></i> Gerar Certidão
                        </button>
                    </div>
                </div>
            </form>
        </main>
        
        <?php include_once("../rodape-modern.php"); ?>
    </div>
    
    <!-- Loading Modal -->
    <div id="loadingModal" class="modal-backdrop">
        <div class="modal" style="text-align: center; max-width: 350px;">
            <div class="spinner" style="margin: 0 auto 20px;"></div>
            <h3 style="margin-bottom: 8px;" id="loadingTitle">Gerando Certidão...</h3>
            <p style="color: var(--color-gray-400); margin: 0;" id="loadingText">Por favor, aguarde enquanto a certidão está sendo gerada.</p>
        </div>
    </div>
    
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script>
        // SortableJS para ordenação das imagens
        var sortable = new Sortable(document.getElementById('imagensGrid'), {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function() { atualizarNumeros(); atualizarOrdem(); }
        });
        
        function atualizarNumeros() {
            var items = document.querySelectorAll('.imagem-item:not(.removed)');
            var numero = 1;
            items.forEach(function(item) {
                item.querySelector('.imagem-numero').textContent = 'Página ' + numero++;
            });
            atualizarContador();
        }
        
        function atualizarOrdem() {
            var items = document.querySelectorAll('.imagem-item:not(.removed)');
            var ordem = [];
            items.forEach(function(item) {
                var checkbox = item.querySelector('.imagem-checkbox');
                if (checkbox.checked) ordem.push(item.dataset.imagem);
            });
            document.getElementById('imagensOrdem').value = ordem.join(',');
        }
        
        function toggleRemover(btn) {
            var item = btn.closest('.imagem-item');
            var checkbox = item.querySelector('.imagem-checkbox');
            
            if (item.classList.contains('removed')) {
                item.classList.remove('removed');
                item.classList.add('selected');
                checkbox.checked = true;
                checkbox.disabled = false;
                btn.textContent = 'Remover';
                btn.classList.remove('btn-restaurar');
            } else {
                item.classList.add('removed');
                item.classList.remove('selected');
                checkbox.checked = false;
                checkbox.disabled = true;
                btn.textContent = 'Restaurar';
                btn.classList.add('btn-restaurar');
            }
            atualizarNumeros();
            atualizarOrdem();
        }
        
        function atualizarContador() {
            var total = document.querySelectorAll('.imagem-checkbox:checked').length;
            document.getElementById('contadorSelecionadas').textContent = total;
        }
        
        function selecionarTodas() {
            document.querySelectorAll('.imagem-item').forEach(function(item) {
                item.classList.remove('removed');
                item.classList.add('selected');
                var checkbox = item.querySelector('.imagem-checkbox');
                checkbox.checked = true;
                checkbox.disabled = false;
                var btn = item.querySelector('.btn-remover');
                btn.textContent = 'Remover';
                btn.classList.remove('btn-restaurar');
            });
            atualizarNumeros();
            atualizarOrdem();
        }
        
        function desmarcarTodas() {
            document.querySelectorAll('.imagem-item').forEach(function(item) {
                item.classList.add('removed');
                item.classList.remove('selected');
                var checkbox = item.querySelector('.imagem-checkbox');
                checkbox.checked = false;
                checkbox.disabled = true;
                var btn = item.querySelector('.btn-remover');
                btn.textContent = 'Restaurar';
                btn.classList.add('btn-restaurar');
            });
            atualizarNumeros();
            atualizarOrdem();
        }
        
        function cancelar() {
            Swal.fire({
                title: 'Cancelar emissão?',
                text: 'Tem certeza que deseja cancelar a emissão desta certidão?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sim, cancelar',
                cancelButtonText: 'Não, continuar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'historico.php';
                }
            });
        }
        
        // Validação do formulário
        document.getElementById('certidaoForm').addEventListener('submit', function(e) {
            var selecionadas = document.querySelectorAll('.imagem-checkbox:checked').length;
            if (selecionadas === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Nenhuma imagem selecionada',
                    text: 'Selecione pelo menos uma imagem para gerar a certidão.',
                    confirmButtonColor: '#10b981'
                });
                return false;
            }
            
            // Atualizar ordem antes de enviar
            atualizarOrdem();
            
            // Mostrar loading
            document.getElementById('loadingTitle').textContent = 'Gerando Certidão...';
            document.getElementById('loadingText').textContent = 'Por favor, aguarde enquanto a certidão está sendo gerada.';
            document.getElementById('loadingModal').classList.add('active');
        });
        
        // Tipo de Certidão - seleção visual
        document.querySelectorAll('.tipo-certidao-option').forEach(function(option) {
            option.addEventListener('click', function() {
                // Remover selected de todos
                document.querySelectorAll('.tipo-certidao-option').forEach(function(opt) {
                    opt.classList.remove('selected');
                });
                // Adicionar selected no clicado
                this.classList.add('selected');
                // Marcar o radio
                this.querySelector('input[type="radio"]').checked = true;
                // Atualizar o hidden input do formulário principal
                document.getElementById('tipoCertidaoInput').value = this.querySelector('input[type="radio"]').value;
            });
        });
        
        // ===== Funcionalidade de Partes =====
        function adicionarParte() {
            var container = document.getElementById('partesContainer');
            var novaRow = document.createElement('div');
            novaRow.className = 'parte-row';
            novaRow.style.cssText = 'display: flex; gap: 8px; margin-bottom: 8px;';
            novaRow.innerHTML = `
                <input type="text" name="partes[]" class="form-control parte-input" placeholder="Nome da parte envolvida" style="flex: 1;">
                <button type="button" class="btn-remove-parte" onclick="removerParte(this)" title="Remover parte">
                    <i class="fa-solid fa-minus"></i>
                </button>
            `;
            container.appendChild(novaRow);
            novaRow.querySelector('input').focus();
        }
        
        function removerParte(btn) {
            var row = btn.closest('.parte-row');
            row.style.animation = 'slideIn 0.2s ease reverse';
            setTimeout(function() {
                row.remove();
            }, 200);
        }
        
        // ===== Funcionalidade de Selos =====
        
        // Mostrar/ocultar motivo de isenção
        document.getElementById('isento').addEventListener('change', function() {
            var wrapper = document.getElementById('motivoWrapper');
            if (this.checked) {
                wrapper.classList.add('show');
            } else {
                wrapper.classList.remove('show');
            }
        });
        
        // Botão adicionar mais selo
        var btnAddSelo = document.getElementById('btnAddSelo');
        if (btnAddSelo) {
            btnAddSelo.addEventListener('click', function() {
                document.getElementById('seloFormContainer').classList.add('show');
                this.style.display = 'none';
                document.getElementById('ato').focus();
            });
        }
        
        // Formulário de selo
        document.getElementById('seloForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var btn = document.getElementById('btnSolicitar');
            var originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Solicitando...';
            
            // Mostrar loading
            document.getElementById('loadingTitle').textContent = 'Solicitando Selo...';
            document.getElementById('loadingText').textContent = 'Por favor, aguarde enquanto o selo está sendo solicitado.';
            document.getElementById('loadingModal').classList.add('active');
            
            var formData = new FormData(this);
            
            fetch('solicitar_selo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingModal').classList.remove('active');
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                if (data.success) {
                    // Adicionar selo ao container
                    var container = document.getElementById('selosContainer');
                    if (!container) {
                        container = document.createElement('div');
                        container.id = 'selosContainer';
                        container.className = 'selos-container';
                        document.getElementById('seloFormContainer').before(container);
                    }
                    container.insertAdjacentHTML('beforeend', data.html);
                    
                    // Esconder formulário e mostrar botão adicionar
                    document.getElementById('seloFormContainer').classList.remove('show');
                    
                    var btnAdd = document.getElementById('btnAddSelo');
                    if (!btnAdd) {
                        var headerDiv = document.querySelector('.selo-section > div:first-child');
                        var newBtn = document.createElement('button');
                        newBtn.type = 'button';
                        newBtn.className = 'btn-add-selo';
                        newBtn.id = 'btnAddSelo';
                        newBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Adicionar mais selo';
                        newBtn.addEventListener('click', function() {
                            document.getElementById('seloFormContainer').classList.add('show');
                            this.style.display = 'none';
                            document.getElementById('ato').focus();
                        });
                        headerDiv.appendChild(newBtn);
                    } else {
                        btnAdd.style.display = '';
                    }
                    
                    // Remover alerta se existir
                    var alertEl = document.querySelector('.selo-alert');
                    if (alertEl) alertEl.remove();
                    
                    // Limpar formulário
                    document.getElementById('seloForm').reset();
                    document.getElementById('motivoWrapper').classList.remove('show');
                    
                    // Feedback com SweetAlert2
                    Swal.fire({
                        icon: 'success',
                        title: 'Selo Solicitado!',
                        html: '<p>Selo gerado com sucesso!</p><p><strong>Total: ' + data.total + ' selo(s)</strong></p>',
                        confirmButtonColor: '#10b981'
                    });
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro ao solicitar selo',
                        text: data.error || 'Erro desconhecido ao solicitar selo',
                        confirmButtonColor: '#10b981'
                    });
                    console.error('Erro:', data);
                }
            })
            .catch(error => {
                document.getElementById('loadingModal').classList.remove('active');
                btn.disabled = false;
                btn.innerHTML = originalText;
                Swal.fire({
                    icon: 'error',
                    title: 'Erro de Conexão',
                    text: 'Erro ao solicitar selo. Verifique a conexão com o servidor.',
                    confirmButtonColor: '#10b981'
                });
                console.error('Erro:', error);
            });
        });
    </script>
</body>
</html>