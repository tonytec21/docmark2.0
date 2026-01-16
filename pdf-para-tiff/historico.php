<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('America/Sao_Paulo');

$pastaHistorico = __DIR__ . '/historico';
$arquivos = glob($pastaHistorico . '/*');

if (!empty($arquivos)) {
    setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
    $numerosArquivos = array();
    foreach ($arquivos as $arquivo) {
        $numeroArquivo = (int) str_replace('.tiff', '', basename($arquivo));
        $numerosArquivos[] = $numeroArquivo;
    }
}

// Upload XML
$msg_success = null;
$msg_error = null;

if(isset($_FILES['xml_file'])) {
    $target_dir = "indicador-pessoal/";
    $history_dir = "historico-indicador/";

    $files = glob($target_dir . "*");
    foreach($files as $file){
        if(is_file($file)) unlink($file);
    }

    $target_file = $target_dir . basename($_FILES['xml_file']['name']);
    $history_file = $history_dir . basename($_FILES['xml_file']['name']);
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if($file_type == "xml") {
        if(move_uploaded_file($_FILES['xml_file']['tmp_name'], $target_file)) {
            $msg_success = "Arquivo XML anexado com sucesso!";
            if(copy($target_file, $history_file)) {
                $msg_success .= " Disponível para visualização.";
            }
        } else {
            $msg_error = "Erro ao anexar o arquivo.";
        }
    } else {
        $msg_error = "Por favor, selecione um arquivo XML válido.";
    }
}

// Meses em português
$meses = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho',
    7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];

// Definir filtros padrão (última semana) se não houver filtros na URL
$filtroAplicado = isset($_GET['dtinicial']) || isset($_GET['dtfinal']) || isset($_GET['matricula']);
$mostrarTodos = isset($_GET['todos']) && $_GET['todos'] == '1';

if (!$filtroAplicado && !$mostrarTodos) {
    // Por padrão, mostrar última semana
    $dataFinalPadrao = date('Y-m-d');
    $dataInicialPadrao = date('Y-m-d', strtotime('-7 days'));
} else {
    $dataInicialPadrao = $_GET['dtinicial'] ?? '';
    $dataFinalPadrao = $_GET['dtfinal'] ?? '';
}

$matriculaPesquisa = $_GET['matricula'] ?? '';

// Contar registros filtrados
$totalRegistros = count($arquivos);
$registrosFiltrados = 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Controle de Conversões</title>
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/docmark-modern.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { color: var(--color-gray-300); padding: var(--space-4) 0; }
        .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input {
            background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md); color: var(--color-white); padding: 8px 12px;
        }
        .dataTables_wrapper .dataTables_filter input:focus { outline: none; border-color: var(--color-accent); box-shadow: 0 0 0 3px var(--color-accent-glow); }
        table.dataTable thead th { background: rgba(255, 255, 255, 0.03) !important; color: var(--color-gray-200) !important;
            font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 16px !important; border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important; }
        table.dataTable tbody td { padding: 14px 16px !important; border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important; color: var(--color-gray-300); vertical-align: middle; }
        table.dataTable tbody tr:hover { background: rgba(255, 255, 255, 0.03) !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { color: var(--color-gray-300) !important; background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important; border-radius: var(--radius-md) !important; margin: 0 2px !important; padding: 6px 12px !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover, .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--color-accent) !important; border-color: var(--color-accent) !important; color: var(--color-white) !important; }
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px; }
        .action-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-xl); padding: 24px; }
        .action-card h4 { color: var(--color-accent-light); margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
        .action-card p { color: var(--color-gray-400); font-size: 0.875rem; margin-bottom: 16px; }
        .quick-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px; padding-top: 24px; border-top: 1px solid rgba(255, 255, 255, 0.08); }
        .filter-row { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .filter-row .form-group { flex: 1; min-width: 140px; margin-bottom: 0; }
        .filter-row .form-group.matricula-group { min-width: 180px; }
        .btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-color: #10b981; }
        .btn-success:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); }
        .table-actions { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; }
        .filter-info { background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: var(--radius-lg); padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .filter-info-text { color: var(--color-gray-300); font-size: 0.875rem; display: flex; align-items: center; gap: 8px; }
        .filter-info-text i { color: #60a5fa; }
        .filter-info-text strong { color: var(--color-white); }
        .btn-outline { background: transparent; border: 1px solid rgba(255, 255, 255, 0.2); color: var(--color-gray-300); }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.05); border-color: var(--color-accent); color: var(--color-accent-light); }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include_once("../header-modern.php"); ?>
        <?php include_once("../menu-modern.php"); ?>
        
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header animate-fade-in">
                <h1 class="page-title">
                    <i class="fa-solid fa-list-check" style="color: var(--color-accent-light);"></i>
                    Controle de Conversões
                </h1>
                <p class="page-subtitle">Gerencie todas as conversões, adicione sinal público e anexe indicador pessoal</p>
            </div>
            
            <!-- Action Cards -->
            <div class="action-grid animate-slide-up">
                <!-- Sinal Público -->
                <div class="action-card">
                    <h4><i class="fa-solid fa-signature"></i> Adicionar Sinal Público</h4>
                    <p>Selecione os arquivos PDF para adicionar o sinal público (chancela)</p>
                    <form action="../chancela/upload.php" method="post" enctype="multipart/form-data" id="formSinalPublico">
                        <div class="form-group">
                            <div class="file-input-wrapper">
                                <input type="file" name="pdf[]" id="pdf" class="file-input" multiple accept=".pdf">
                                <label for="pdf" class="file-input-label" style="padding: 20px;">
                                    <i class="fa-solid fa-file-pdf file-input-icon" style="font-size: 24px;"></i>
                                    <span class="file-input-text">Selecionar PDFs</span>
                                </label>
                            </div>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary w-full mt-4">
                            <i class="fa-solid fa-stamp"></i> Processar Sinal Público
                        </button>
                    </form>
                </div>
                
                <!-- Indicador Pessoal -->
                <div class="action-card">
                    <h4><i class="fa-solid fa-file-code"></i> Anexar Indicador Pessoal</h4>
                    <p>Selecione um arquivo XML do indicador pessoal para anexar ao sistema</p>
                    <form action="" method="post" enctype="multipart/form-data" id="formIndicador">
                        <div class="form-group">
                            <div class="file-input-wrapper">
                                <input type="file" name="xml_file" id="xml_file" class="file-input" accept=".xml">
                                <label for="xml_file" class="file-input-label" style="padding: 20px;">
                                    <i class="fa-solid fa-code file-input-icon" style="font-size: 24px;"></i>
                                    <span class="file-input-text">Selecionar XML</span>
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-full mt-4">
                            <i class="fa-solid fa-paperclip"></i> Anexar Arquivo
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon"><i class="fa-solid fa-bolt"></i></span>
                        Ações Rápidas
                    </h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions" style="border: none; padding: 0; margin: 0;">
                        <button class="btn btn-primary" id="sincronizar-button">
                            <i class="fa-solid fa-sync"></i> Sincronizar com NextCloud
                        </button>
                        <button class="btn btn-secondary" id="visualizar-indicador">
                            <i class="fa-solid fa-eye"></i> Visualizar Indicador Pessoal
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- History Table -->
            <div class="card animate-slide-up">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                        Histórico de Matrículas Convertidas
                        <?php if (!empty($arquivos)) : ?>
                            <span class="badge badge-info" style="margin-left: 12px;"><?= $totalRegistros ?> total</span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <form method="GET" class="filter-row" id="formFiltro">
                        <div class="form-group matricula-group">
                            <label class="form-label">Nº Matrícula</label>
                            <input type="text" name="matricula" class="form-control" placeholder="Ex: 00012345" value="<?= htmlspecialchars($matriculaPesquisa) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Data Inicial</label>
                            <input type="date" name="dtinicial" class="form-control" value="">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Data Final</label>
                            <input type="date" name="dtfinal" class="form-control" value="<?= htmlspecialchars($dataFinalPadrao) ?>">
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-search"></i> Filtrar
                            </button>
                        </div>
                        <div>
                            <a href="historico.php?todos=1" class="btn btn-outline">
                                <i class="fa-solid fa-list"></i> Ver Todos
                            </a>
                        </div>
                        <div>
                            <a href="historico.php" class="btn btn-secondary">
                                <i class="fa-solid fa-rotate-left"></i> Última Semana
                            </a>
                        </div>
                    </form>
                    
                    <!-- Informação do filtro aplicado -->
                    <?php if (!$mostrarTodos): ?>
                    <div class="filter-info">
                        <span class="filter-info-text">
                            <i class="fa-solid fa-filter"></i>
                            <?php if (!empty($matriculaPesquisa)): ?>
                                Pesquisando matrícula: <strong><?= htmlspecialchars($matriculaPesquisa) ?></strong>
                            <?php elseif (!empty($dataInicialPadrao) && !empty($dataFinalPadrao)): ?>
                                Exibindo registros de <strong><?= date('d/m/Y', strtotime($dataInicialPadrao)) ?></strong> até <strong><?= date('d/m/Y', strtotime($dataFinalPadrao)) ?></strong>
                            <?php elseif (!empty($dataInicialPadrao)): ?>
                                Exibindo registros a partir de <strong><?= date('d/m/Y', strtotime($dataInicialPadrao)) ?></strong>
                            <?php elseif (!empty($dataFinalPadrao)): ?>
                                Exibindo registros até <strong><?= date('d/m/Y', strtotime($dataFinalPadrao)) ?></strong>
                            <?php else: ?>
                                Exibindo registros da <strong>última semana</strong>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="filter-info">
                        <span class="filter-info-text">
                            <i class="fa-solid fa-database"></i>
                            Exibindo <strong>todos</strong> os registros
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($arquivos)) : ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-folder-open empty-state-icon"></i>
                            <h4 class="empty-state-title">Nenhuma conversão encontrada</h4>
                            <p class="empty-state-text">As matrículas convertidas aparecerão aqui</p>
                        </div>
                    <?php else : ?>
                        <div class="table-wrapper">
                            <table id="tabela-historico" class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Matrícula Nº</th>
                                        <th>Data da Conversão</th>
                                        <th>Horário</th>
                                        <th style="text-align: center;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Aplicar filtros
                                    $inicio_filtro = null;
                                    $fim_filtro = null;
                                    
                                    if (!empty($dataInicialPadrao)) {
                                        $inicio_filtro = strtotime($dataInicialPadrao);
                                    }
                                    if (!empty($dataFinalPadrao)) {
                                        $fim_filtro = strtotime($dataFinalPadrao . ' 23:59:59');
                                    }
                                    
                                    foreach ($arquivos as $arquivo):
                                        $data_timestamp = filemtime($arquivo);
                                        $matricula = str_replace('.tiff', '', basename($arquivo));
                                        
                                        // Aplicar filtro de matrícula
                                        if (!empty($matriculaPesquisa)) {
                                            // Pesquisa parcial (contém o texto)
                                            if (strpos($matricula, $matriculaPesquisa) === false) {
                                                continue;
                                            }
                                        }
                                        
                                        // Aplicar filtro de data (se não for "ver todos")
                                        if (!$mostrarTodos) {
                                            if ($inicio_filtro !== null && $data_timestamp < $inicio_filtro) {
                                                continue;
                                            }
                                            if ($fim_filtro !== null && $data_timestamp > $fim_filtro) {
                                                continue;
                                            }
                                        }
                                        
                                        $registrosFiltrados++;
                                        $dataConversao = date('d/m/Y', $data_timestamp);
                                        $horaConversao = date('H:i:s', $data_timestamp);
                                    ?>
                                        <tr>
                                            <td>
                                                <code style="color: var(--color-accent-light); font-weight: 600; font-size: 1rem;">
                                                    <?= $matricula ?>
                                                </code>
                                            </td>
                                            <td><?= $dataConversao ?></td>
                                            <td><?= $horaConversao ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="historico/<?= basename($arquivo) ?>" download class="btn btn-primary btn-sm" title="Download TIFF">
                                                        <i class="fa-solid fa-download"></i>
                                                    </a>
                                                    <a href="pdf-viw/<?= str_replace('.tiff', '.pdf', basename($arquivo)) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Visualizar PDF">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                    <a href="emitir-certidao.php?matricula=<?= $matricula ?>" class="btn btn-success btn-sm btn-certidao" title="Emitir Certidão">
                                                        <i class="fa-solid fa-certificate"></i>
                                                    </a>
                                                    <a href="delete.php?file=<?= urlencode(basename($arquivo)) ?>" class="btn btn-danger btn-sm delete-link" data-matricula="<?= $matricula ?>" title="Excluir">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Contador de registros filtrados -->
                        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.08); text-align: center;">
                            <span style="color: var(--color-gray-400); font-size: 0.875rem;">
                                <i class="fa-solid fa-chart-simple"></i>
                                Exibindo <strong style="color: var(--color-accent-light);"><?= $registrosFiltrados ?></strong> de <strong style="color: var(--color-white);"><?= $totalRegistros ?></strong> registros
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <?php include_once("../rodape-modern.php"); ?>
    </div>
    
    <!-- Loading Modal -->
    <div id="loadingModal" class="modal-backdrop">
        <div class="modal" style="text-align: center; max-width: 350px;">
            <div class="spinner" style="margin: 0 auto 20px;"></div>
            <h3 style="margin-bottom: 8px;" id="loadingTitle">Processando...</h3>
            <p style="color: var(--color-gray-400); margin: 0;" id="loadingText">Por favor, aguarde.</p>
        </div>
    </div>
    
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        // Mostrar alertas de sucesso/erro do PHP
        <?php if ($msg_success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: '<?= addslashes($msg_success) ?>',
            confirmButtonColor: '#10b981'
        });
        <?php endif; ?>
        
        <?php if ($msg_error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: '<?= addslashes($msg_error) ?>',
            confirmButtonColor: '#10b981'
        });
        <?php endif; ?>
        
        $(document).ready(function() {
            <?php if (!empty($arquivos)) : ?>
            $('#tabela-historico').DataTable({
                language: {
                    "emptyTable": "Nenhum registro encontrado",
                    "info": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 até 0 de 0 registros",
                    "infoFiltered": "(Filtrados de _MAX_ registros)",
                    "lengthMenu": "_MENU_ resultados por página",
                    "loadingRecords": "Carregando...",
                    "processing": "Processando...",
                    "search": "Pesquisar na tabela:",
                    "zeroRecords": "Nenhum registro encontrado",
                    "paginate": {
                        "first": "Primeiro",
                        "last": "Último",
                        "next": "Próximo",
                        "previous": "Anterior"
                    }
                },
                order: [[1, 'desc'], [2, 'desc']], // Ordenar por data e hora desc
                pageLength: 25,
                searching: true, // Habilitar busca do DataTable
                dom: '<"top"lf>rt<"bottom"ip><"clear">' // Layout padrão com busca
            });
            <?php endif; ?>
            
            // Delete confirmation com SweetAlert2
            $('.delete-link').on('click', function(e) {
                e.preventDefault();
                let href = $(this).attr('href');
                let matricula = $(this).data('matricula');
                
                Swal.fire({
                    title: 'Confirmar Exclusão',
                    html: `Tem certeza que deseja excluir a matrícula <strong>${matricula}</strong>?<br><br><small style="color: #f87171;">Esta ação não pode ser desfeita.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mostrar loading
                        document.getElementById('loadingTitle').textContent = 'Excluindo...';
                        document.getElementById('loadingText').textContent = 'Removendo a matrícula do sistema.';
                        document.getElementById('loadingModal').classList.add('active');
                        window.location.href = href;
                    }
                });
            });
        });
        
        // Form Sinal Público - Validação e Loading
        document.getElementById('formSinalPublico').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('pdf');
            
            if (fileInput.files.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Nenhum arquivo selecionado',
                    text: 'Por favor, selecione pelo menos um arquivo PDF.',
                    confirmButtonColor: '#10b981'
                });
                return false;
            }
            
            // Verificar se todos são PDFs
            for (let i = 0; i < fileInput.files.length; i++) {
                if (!fileInput.files[i].name.toLowerCase().endsWith('.pdf')) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Arquivo inválido',
                        text: `O arquivo "${fileInput.files[i].name}" não é um PDF válido.`,
                        confirmButtonColor: '#10b981'
                    });
                    return false;
                }
            }
            
            // Mostrar loading
            document.getElementById('loadingTitle').textContent = 'Processando Sinal Público...';
            document.getElementById('loadingText').textContent = 'Adicionando a chancela aos arquivos PDF.';
            document.getElementById('loadingModal').classList.add('active');
        });
        
        // Form Indicador Pessoal - Validação e Loading
        document.getElementById('formIndicador').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('xml_file');
            
            if (fileInput.files.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Nenhum arquivo selecionado',
                    text: 'Por favor, selecione um arquivo XML.',
                    confirmButtonColor: '#10b981'
                });
                return false;
            }
            
            if (!fileInput.files[0].name.toLowerCase().endsWith('.xml')) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Arquivo inválido',
                    text: 'Por favor, selecione um arquivo XML válido.',
                    confirmButtonColor: '#10b981'
                });
                return false;
            }
            
            // Mostrar loading
            document.getElementById('loadingTitle').textContent = 'Anexando Indicador...';
            document.getElementById('loadingText').textContent = 'Processando o arquivo XML.';
            document.getElementById('loadingModal').classList.add('active');
        });
        
        // Filtro de data - Loading
        document.getElementById('formFiltro').addEventListener('submit', function() {
            document.getElementById('loadingTitle').textContent = 'Filtrando...';
            document.getElementById('loadingText').textContent = 'Buscando registros com os filtros selecionados.';
            document.getElementById('loadingModal').classList.add('active');
        });
        
        // Botão Emitir Certidão - Loading
        document.querySelectorAll('.btn-certidao').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('loadingTitle').textContent = 'Carregando...';
                document.getElementById('loadingText').textContent = 'Preparando emissão de certidão.';
                document.getElementById('loadingModal').classList.add('active');
            });
        });
        
        // Sincronizar com SweetAlert2
        document.getElementById('sincronizar-button').addEventListener('click', function() {
            Swal.fire({
                title: 'Sincronizar com NextCloud',
                text: 'Deseja sincronizar as matrículas com o NextCloud?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sim, sincronizar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('loadingTitle').textContent = 'Sincronizando...';
                    document.getElementById('loadingText').textContent = 'Conectando ao NextCloud e transferindo arquivos.';
                    document.getElementById('loadingModal').classList.add('active');
                    
                    fetch('execute_sync.php')
                        .then(response => response.text())
                        .then(output => {
                            document.getElementById('loadingModal').classList.remove('active');
                            Swal.fire({
                                icon: 'success',
                                title: 'Sincronização Concluída!',
                                html: `<p>Comando executado com sucesso!</p><p><strong>1</strong> XML do indicador pessoal e</p><p><strong>${output}</strong> matrículas foram sincronizadas.</p>`,
                                confirmButtonColor: '#10b981'
                            }).then(() => {
                                location.reload();
                            });
                        })
                        .catch(error => {
                            document.getElementById('loadingModal').classList.remove('active');
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro na Sincronização',
                                text: 'Não foi possível conectar ao servidor. Tente novamente.',
                                confirmButtonColor: '#10b981'
                            });
                        });
                }
            });
        });
        
        // Visualizar Indicador
        document.getElementById('visualizar-indicador').addEventListener('click', function() {
            window.open('../indicador-pessoal/indicador-pessoal.php', '_blank');
        });
        
        // File input labels - atualização visual
        document.querySelectorAll('.file-input').forEach(input => {
            input.addEventListener('change', function() {
                const label = this.nextElementSibling;
                const text = label.querySelector('.file-input-text');
                const icon = label.querySelector('.file-input-icon');
                
                if (this.files.length > 0) {
                    text.textContent = this.files.length > 1 ? this.files.length + ' arquivos selecionados' : this.files[0].name;
                    icon.className = 'fa-solid fa-check-circle file-input-icon';
                    icon.style.color = 'var(--color-accent)';
                }
            });
        });
    </script>
</body>
</html>