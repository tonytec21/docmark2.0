<?php
// Inclua a função verificar_sessao_ativa()
require_once 'funcoes.php';

// Verifique se a sessão está ativa
verificar_sessao_ativa();

// Array para armazenar os logs
$logs = [];

// O arquivo de log selecionado pelo usuário
$nome = '';

// Função para formatar o número do documento
function formatarDocumento($numero) {
    $numero = preg_replace("/\D/", '', $numero);

    if (strlen($numero) === 11) {
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $numero);
    } else if (strlen($numero) === 14) {
        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $numero);
    } else {
        return $numero;
    }
}

// Verifica se um arquivo de log foi selecionado
if (isset($_POST['log'])) {
    $nome = $_POST['log'];
    $arquivo = 'logs/' . $nome;

    if (file_exists($arquivo)) {
        $linhas = file($arquivo);

        foreach ($linhas as $linha) {
            if (strpos($linha, 'Número de documento inválido:') !== false) {
                $partes = explode('Número de documento inválido:', $linha);
                $data = explode(' ', $partes[0])[0];
                $documento = trim($partes[1]);

                if (empty($documento)) {
                    continue;
                }

                $documentoFormatado = formatarDocumento($documento);
                $tipo = strlen($documento) == 11 ? 'CPF' : 'CNPJ';
                $logs[] = ['erro' => 'Número de documento inválido', 'documento' => $documentoFormatado, 'tipo' => $tipo, 'data' => $data];
            }
        }
    }
}

// Função para obter a lista de arquivos de log
function getLogFiles() {
    $arquivos = glob('logs/*.txt');
    $datas = [];
    $nomes = [];

    foreach ($arquivos as $arquivo) {
        $nome = basename($arquivo, '.txt');
        if (!preg_match('/^\d{8}$/', $nome)) {
            continue;
        }
        $dataObj = DateTime::createFromFormat('Ymd', $nome);
        $datas[] = $dataObj;
        $nomes[] = $nome;
    }

    array_multisort($datas, SORT_DESC, $nomes);
    return $nomes;
}

$logFiles = getLogFiles();
$arquivoSelecionado = isset($_POST['log']) ? $_POST['log'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DocMark - Logs do Indicador Pessoal">
    <title>DocMark - Logs do Indicador Pessoal</title>
    
    <!-- Favicon -->
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../css/docmark-modern.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <style>
        /* DataTables Custom Styles */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--color-gray-300);
            padding: var(--space-4) 0;
        }
        
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label {
            color: var(--color-gray-300);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            color: var(--color-white);
            padding: 8px 12px;
        }
        
        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none;
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px var(--color-accent-glow);
        }
        
        table.dataTable {
            border-collapse: collapse !important;
        }
        
        table.dataTable thead th {
            background: rgba(255, 255, 255, 0.03) !important;
            color: var(--color-gray-200) !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 16px !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        
        table.dataTable tbody td {
            padding: 14px 16px !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            color: var(--color-gray-300);
        }
        
        table.dataTable tbody tr:hover {
            background: rgba(255, 255, 255, 0.03) !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--color-gray-300) !important;
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: var(--radius-md) !important;
            margin: 0 2px !important;
            padding: 6px 12px !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--color-accent) !important;
            border-color: var(--color-accent) !important;
            color: var(--color-white) !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--color-accent) !important;
            border-color: var(--color-accent) !important;
            color: var(--color-white) !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5;
        }
        
        /* Filter Section */
        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
            margin-bottom: 24px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Header -->
        <?php include_once("../header-modern.php"); ?>
        
        <!-- Navigation -->
        <?php include_once("../menu-modern.php"); ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header animate-fade-in">
                <h1 class="page-title">
                    <i class="fa-solid fa-clock-rotate-left" style="color: var(--color-accent-light);"></i>
                    Logs do Indicador Pessoal
                </h1>
                <p class="page-subtitle">Visualize e analise os logs de erros do indicador pessoal</p>
            </div>
            
            <!-- Filter Card -->
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon">
                            <i class="fa-solid fa-filter"></i>
                        </span>
                        Filtros
                    </h3>
                </div>
                <div class="card-body">
                    <form action="index.php" method="post" class="filter-section">
                        <div class="filter-group">
                            <label class="form-label">Arquivo de Log</label>
                            <select id="log" name="log" class="form-control form-select">
                                <?php foreach ($logFiles as $logFile) :
                                    $dataFormatada = DateTime::createFromFormat('Ymd', $logFile)->format('d/m/Y');
                                    $selecionado = $logFile . '.txt' === $arquivoSelecionado ? 'selected' : '';
                                ?>
                                    <option value="<?= $logFile ?>.txt" <?= $selecionado ?>>
                                        Log do dia <?= $dataFormatada ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-search"></i>
                                Verificar
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary" id="sincronizar-button">
                                <i class="fa-solid fa-sync"></i>
                                Sincronizar Logs
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Results Card -->
            <div class="card animate-slide-up">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon">
                            <i class="fa-solid fa-list"></i>
                        </span>
                        Lista de Erros
                        <?php if (!empty($logs)) : ?>
                            <span class="badge badge-error" style="margin-left: 12px;"><?= count($logs) ?> registros</span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($logs)) : ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-file-lines empty-state-icon"></i>
                            <h4 class="empty-state-title">Nenhum erro encontrado</h4>
                            <p class="empty-state-text">Selecione um arquivo de log para visualizar os erros</p>
                        </div>
                    <?php else : ?>
                        <div class="table-wrapper">
                            <table id="logsTable" class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Tipo de Erro</th>
                                        <th>Tipo de Documento</th>
                                        <th>Número do Documento</th>
                                        <th>Data do Log</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log) :
                                        $data = date_create_from_format('Y-m-d', $log['data']);
                                        $dataFormatada = $data ? date_format($data, 'd/m/Y') : $log['data'];
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="badge badge-error">
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                    <?= htmlspecialchars($log['erro']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info"><?= htmlspecialchars($log['tipo']) ?></span>
                                            </td>
                                            <td>
                                                <code style="color: var(--color-accent-light);"><?= htmlspecialchars($log['documento']) ?></code>
                                            </td>
                                            <td><?= htmlspecialchars($dataFormatada) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <?php include_once("../rodape-modern.php"); ?>
    </div>
    
    <!-- Loading Modal -->
    <div id="loadingModal" class="modal-backdrop">
        <div class="modal" style="text-align: center; max-width: 300px;">
            <div class="spinner" style="margin: 0 auto 16px;"></div>
            <p style="color: var(--color-gray-300); margin: 0;">Sincronizando arquivos...</p>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div id="successModal" class="modal-backdrop">
        <div class="modal" style="text-align: center; max-width: 400px;">
            <div style="font-size: 48px; color: var(--color-accent); margin-bottom: 16px;">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            <h3 style="margin-bottom: 8px;">Sincronização Concluída!</h3>
            <p style="color: var(--color-gray-400); margin-bottom: 24px;">Os arquivos de logs foram importados com sucesso.</p>
            <button class="btn btn-primary" onclick="closeSuccessModal()">Fechar</button>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            <?php if (!empty($logs)) : ?>
            $('#logsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
                },
                pageLength: 25,
                order: [[3, 'desc']]
            });
            <?php endif; ?>
            
            // Sync button
            $('#sincronizar-button').on('click', function() {
                $('#loadingModal').addClass('active');
                
                fetch('execute_sync.php')
                    .then(response => response.text())
                    .then(output => {
                        $('#loadingModal').removeClass('active');
                        $('#successModal').addClass('active');
                    })
                    .catch(error => {
                        $('#loadingModal').removeClass('active');
                        alert('Erro ao sincronizar arquivos.');
                    });
            });
        });
        
        function closeSuccessModal() {
            $('#successModal').removeClass('active');
            location.reload();
        }
        
        // Close modal on backdrop click
        $('.modal-backdrop').on('click', function(e) {
            if (e.target === this) {
                $(this).removeClass('active');
            }
        });
    </script>
</body>
</html>
