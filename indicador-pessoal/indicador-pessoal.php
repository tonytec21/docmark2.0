<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

function formatCNPJCPF($value) {
    if (strlen($value) == 11) {
        return substr($value, 0, 3) . '.' . substr($value, 3, 3) . '.' . substr($value, 6, 3) . '-' . substr($value, 9, 2);
    } elseif (strlen($value) == 14) {
        return substr($value, 0, 2) . '.' . substr($value, 2, 3) . '.' . substr($value, 5, 3) . '/' . substr($value, 8, 4) . '-' . substr($value, 12, 2);
    }
    return $value;
}

function formatDateToBrazilian($dateString) {
    if (strlen($dateString) == 8) {
        return substr($dateString, 0, 2) . '/' . substr($dateString, 2, 2) . '/' . substr($dateString, 4, 4);
    }
    return $dateString;
}

function compareFileDates($a, $b) {
    return filemtime($b) - filemtime($a);
}

function fixEncodingWithIconv($input) {
    return iconv("UTF-8", "ISO-8859-1//TRANSLIT", $input);
}

$files = glob("../pdf-para-tiff/historico-indicador/*.xml");
usort($files, 'compareFileDates');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Indicador Pessoal</title>
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    <link rel="stylesheet" href="../css/docmark-modern.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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
        .select-wrapper { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; }
        .select-wrapper .form-group { flex: 1; min-width: 250px; margin-bottom: 0; }
        .form-control.select-file { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2310b981' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; cursor: pointer; }
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
                    <i class="fa-solid fa-file-lines" style="color: var(--color-accent-light);"></i>
                    Indicador Pessoal
                </h1>
                <p class="page-subtitle">Visualize os dados do indicador pessoal por data de envio</p>
            </div>
            
            <!-- File Selector -->
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon"><i class="fa-solid fa-calendar-days"></i></span>
                        Selecionar Arquivo
                        <?php if (!empty($files)) : ?>
                            <span class="badge badge-info" style="margin-left: 12px;"><?= count($files) ?> arquivos disponíveis</span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($files)) : ?>
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-triangle alert-icon"></i>
                            <div class="alert-content">Nenhum arquivo XML de indicador pessoal encontrado.</div>
                        </div>
                    <?php else : ?>
                        <form method="post" class="select-wrapper">
                            <div class="form-group">
                                <label class="form-label">Arquivo do Indicador</label>
                                <select name="selected_file" class="form-control select-file">
                                    <option value="">-- Selecione um arquivo --</option>
                                    <?php foreach($files as $file): ?>
                                        <option value="<?= htmlspecialchars($file) ?>" 
                                            <?php if (isset($_POST['selected_file']) && $_POST['selected_file'] == $file) echo 'selected'; ?>>
                                            📄 INDICADOR DO DIA <?= date("d/m/Y", filemtime($file)) ?> (<?= date("H:i", filemtime($file)) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-eye"></i> Visualizar
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Data Table -->
            <?php if(isset($_POST['selected_file']) && in_array($_POST['selected_file'], $files)): 
                $fileContent = file_get_contents($_POST['selected_file']);
                $xml = simplexml_load_string($fileContent);
                $totalRegistros = count($xml->INDIVIDUO);
            ?>
            <div class="card animate-slide-up">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon"><i class="fa-solid fa-table"></i></span>
                        Dados do Indicador - <?= date("d/m/Y", filemtime($_POST['selected_file'])) ?>
                        <span class="badge badge-success" style="margin-left: 12px;"><?= $totalRegistros ?> registros</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table id="indicadorTable" class="table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>CNPJ/CPF</th>
                                    <th>Matrícula</th>
                                    <th>Ato</th>
                                    <th>Data R/AV</th>
                                    <th>Data de Venda</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($xml->INDIVIDUO as $individuo): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(fixEncodingWithIconv($individuo->NOME)) ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= htmlspecialchars(formatCNPJCPF(fixEncodingWithIconv($individuo->CNPJCPF))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code style="color: var(--color-accent-light); font-weight: 600;">
                                                <?= htmlspecialchars(fixEncodingWithIconv($individuo->NMATRICULA)) ?>
                                            </code>
                                        </td>
                                        <td><?= htmlspecialchars(fixEncodingWithIconv($individuo->TIPODEATO)) ?></td>
                                        <td><?= htmlspecialchars(formatDateToBrazilian($individuo->DTREGAVERB)) ?></td>
                                        <td><?= htmlspecialchars(formatDateToBrazilian($individuo->DTVENDA)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php elseif(isset($_POST['selected_file']) && empty($_POST['selected_file'])): ?>
            <div class="card animate-slide-up">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fa-solid fa-hand-pointer empty-state-icon"></i>
                        <h4 class="empty-state-title">Selecione um arquivo</h4>
                        <p class="empty-state-text">Escolha um arquivo de indicador acima para visualizar os dados</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
        
        <?php include_once("../rodape-modern.php"); ?>
    </div>
    
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            <?php if(isset($_POST['selected_file']) && in_array($_POST['selected_file'], $files)): ?>
            $('#indicadorTable').DataTable({
                language: {
                    "emptyTable": "Nenhum registro encontrado",
                    "info": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 até 0 de 0 registros",
                    "infoFiltered": "(Filtrados de _MAX_ registros)",
                    "lengthMenu": "_MENU_ resultados por página",
                    "loadingRecords": "Carregando...",
                    "processing": "Processando...",
                    "search": "Pesquisar:",
                    "zeroRecords": "Nenhum registro encontrado",
                    "paginate": {
                        "first": "Primeiro",
                        "last": "Último",
                        "next": "Próximo",
                        "previous": "Anterior"
                    }
                },
                order: [[2, 'asc']],
                pageLength: 25
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
