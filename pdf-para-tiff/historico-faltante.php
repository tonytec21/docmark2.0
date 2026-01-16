<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

error_reporting(0);
ini_set('display_errors', 0);

$pastaHistorico = __DIR__ . '/historico';
$arquivos = glob($pastaHistorico . '/*');
$numerosFaltantes = [];
$totalConvertidos = 0;
$maximo = 0;

if (!empty($arquivos)) {
    setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
    $numerosArquivos = array();
    foreach ($arquivos as $arquivo) {
        $numeroArquivo = (int) str_replace('.tiff', '', basename($arquivo));
        $numerosArquivos[] = $numeroArquivo;
    }
    
    $totalConvertidos = count($numerosArquivos);
    $minimo = 1;
    $maximo = max($numerosArquivos);

    for ($i = $minimo; $i <= $maximo; $i++) {
        if (!in_array($i, $numerosArquivos)) {
            $numerosFaltantes[] = str_pad($i, 8, '0', STR_PAD_LEFT);
        }
    }
}

// Upload XML
if(isset($_FILES['xml_file'])) {
    $target_dir = "indicador-pessoal/";
    $history_dir = "historico-indicador/";
    $files = glob($target_dir . "*");
    foreach($files as $file){ if(is_file($file)) unlink($file); }
    $target_file = $target_dir . basename($_FILES['xml_file']['name']);
    $history_file = $history_dir . basename($_FILES['xml_file']['name']);
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if($file_type == "xml") {
        if(move_uploaded_file($_FILES['xml_file']['tmp_name'], $target_file)) {
            $msg_success = "Arquivo XML anexado com sucesso!";
            copy($target_file, $history_file);
        } else { $msg_error = "Erro ao anexar o arquivo."; }
    } else { $msg_error = "Por favor, selecione um arquivo XML válido."; }
}

$totalFaltantes = count($numerosFaltantes);
$percentualConvertido = $maximo > 0 ? round(($totalConvertidos / $maximo) * 100, 1) : 0;
$percentualFaltante = $maximo > 0 ? round(($totalFaltantes / $maximo) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Relatórios</title>
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    <link rel="stylesheet" href="../css/docmark-modern.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { color: var(--color-gray-300); padding: var(--space-4) 0; }
        .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input {
            background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md); color: var(--color-white); padding: 8px 12px;
        }
        table.dataTable thead th { background: rgba(255, 255, 255, 0.03) !important; color: var(--color-gray-200) !important;
            font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 16px !important; border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important; }
        table.dataTable tbody td { padding: 14px 16px !important; border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important; color: var(--color-gray-300); }
        table.dataTable tbody tr:hover { background: rgba(255, 255, 255, 0.03) !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { color: var(--color-gray-300) !important; background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important; border-radius: var(--radius-md) !important; margin: 0 2px !important; padding: 6px 12px !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover, .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--color-accent) !important; border-color: var(--color-accent) !important; color: var(--color-white) !important; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-xl); padding: 24px; text-align: center; }
        .stat-card i { font-size: 32px; margin-bottom: 12px; }
        .stat-card .stat-value { font-size: 2.5rem; font-weight: 700; color: var(--color-white); }
        .stat-card .stat-label { color: var(--color-gray-400); font-size: 0.875rem; margin-top: 4px; }
        .stat-card.success i, .stat-card.success .stat-value { color: var(--color-accent); }
        .stat-card.danger i, .stat-card.danger .stat-value { color: #f87171; }
        .stat-card.info i, .stat-card.info .stat-value { color: #60a5fa; }
        .chart-wrapper { max-width: 400px; margin: 0 auto; }
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px; }
        .action-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-xl); padding: 24px; }
        .action-card h4 { color: var(--color-accent-light); margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
        .action-card p { color: var(--color-gray-400); font-size: 0.875rem; margin-bottom: 16px; }
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
                    <i class="fa-solid fa-chart-pie" style="color: var(--color-accent-light);"></i>
                    Relatórios
                </h1>
                <p class="page-subtitle">Visualize estatísticas e matrículas faltantes do sistema</p>
            </div>
            
            <!-- Alerts -->
            <?php if (isset($msg_success)) : ?>
                <div class="alert alert-success mb-6 animate-fade-in">
                    <i class="fa-solid fa-check-circle alert-icon"></i>
                    <div class="alert-content"><?= $msg_success ?></div>
                </div>
            <?php endif; ?>
            <?php if (isset($msg_error)) : ?>
                <div class="alert alert-error mb-6 animate-fade-in">
                    <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                    <div class="alert-content"><?= $msg_error ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid animate-slide-up">
                <div class="stat-card success">
                    <i class="fa-solid fa-check-circle"></i>
                    <div class="stat-value"><?= number_format($totalConvertidos, 0, ',', '.') ?></div>
                    <div class="stat-label">Matrículas Convertidas</div>
                </div>
                <div class="stat-card danger">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <div class="stat-value"><?= number_format($totalFaltantes, 0, ',', '.') ?></div>
                    <div class="stat-label">Matrículas Faltantes</div>
                </div>
                <div class="stat-card info">
                    <i class="fa-solid fa-percentage"></i>
                    <div class="stat-value"><?= $percentualConvertido ?>%</div>
                    <div class="stat-label">Taxa de Conversão</div>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon"><i class="fa-solid fa-chart-pie"></i></span>
                        Gráfico de Conversão
                    </h3>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="grafico"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Action Cards -->
            <div class="action-grid animate-slide-up">
                <div class="action-card">
                    <h4><i class="fa-solid fa-signature"></i> Adicionar Sinal Público</h4>
                    <p>Selecione arquivos PDF para adicionar o sinal público (chancela)</p>
                    <form action="../chancela/upload.php" method="post" enctype="multipart/form-data">
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
                            <i class="fa-solid fa-stamp"></i> Processar
                        </button>
                    </form>
                </div>
                
                <div class="action-card">
                    <h4><i class="fa-solid fa-file-code"></i> Anexar Indicador Pessoal</h4>
                    <p>Selecione um arquivo XML do indicador pessoal</p>
                    <form action="" method="post" enctype="multipart/form-data">
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
                            <i class="fa-solid fa-paperclip"></i> Anexar
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title"><span class="card-title-icon"><i class="fa-solid fa-bolt"></i></span> Ações Rápidas</h3>
                </div>
                <div class="card-body">
                    <div class="flex flex-wrap gap-4">
                        <button class="btn btn-primary" id="sincronizar-button"><i class="fa-solid fa-sync"></i> Sincronizar com NextCloud</button>
                        <button class="btn btn-secondary" id="visualizar-indicador"><i class="fa-solid fa-eye"></i> Visualizar Indicador Pessoal</button>
                        <button class="btn btn-secondary" id="exportar-excel"><i class="fa-solid fa-file-excel"></i> Exportar Faltantes para Excel</button>
                    </div>
                </div>
            </div>
            
            <!-- Missing List -->
            <div class="card animate-slide-up">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon"><i class="fa-solid fa-list-ol"></i></span>
                        Lista de Matrículas Faltantes
                        <span class="badge badge-error" style="margin-left: 12px;"><?= count($numerosFaltantes) ?> registros</span>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($numerosFaltantes)) : ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-check-circle empty-state-icon" style="color: var(--color-accent);"></i>
                            <h4 class="empty-state-title">Nenhuma matrícula faltante!</h4>
                            <p class="empty-state-text">Todas as matrículas foram convertidas</p>
                        </div>
                    <?php else : ?>
                        <div class="table-wrapper">
                            <table id="tabela-faltantes" class="table" style="width: 100%;">
                                <thead><tr><th>Matrícula Nº</th></tr></thead>
                                <tbody>
                                    <?php foreach ($numerosFaltantes as $numeroFaltante): ?>
                                        <tr><td><code style="color: #f87171; font-weight: 600;"><?= $numeroFaltante ?></code></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <?php include_once("../rodape-modern.php"); ?>
    </div>
    
    <!-- Loading Modal -->
    <div id="loadingModal" class="modal-backdrop">
        <div class="modal" style="text-align: center; max-width: 300px;">
            <div class="spinner" style="margin: 0 auto 16px;"></div>
            <p style="color: var(--color-gray-300); margin: 0;">Processando, aguarde...</p>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div id="successModal" class="modal-backdrop">
        <div class="modal" style="text-align: center; max-width: 450px;">
            <div style="font-size: 48px; color: var(--color-accent); margin-bottom: 16px;"><i class="fa-solid fa-check-circle"></i></div>
            <h3 style="margin-bottom: 8px;">Operação Concluída!</h3>
            <p id="successMessage" style="color: var(--color-gray-400); margin-bottom: 24px;"></p>
            <button class="btn btn-primary" onclick="closeSuccessModal()">Fechar</button>
        </div>
    </div>
    
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        // Chart
        const ctx = document.getElementById('grafico').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Convertidas', 'Faltantes'],
                datasets: [{
                    data: [<?= $totalConvertidos ?>, <?= $totalFaltantes ?>],
                    backgroundColor: ['rgba(16, 185, 129, 0.7)', 'rgba(248, 113, 113, 0.7)'],
                    borderColor: ['rgba(16, 185, 129, 1)', 'rgba(248, 113, 113, 1)'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#cbd5e1', padding: 20, font: { family: "'Poppins', sans-serif" } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        // DataTable
        $(document).ready(function() {
            <?php if (!empty($numerosFaltantes)) : ?>
            $('#tabela-faltantes').DataTable({
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
                order: [[0, 'asc']],
                pageLength: 25
            });
            <?php endif; ?>
        });
        
        // Sync
        document.getElementById('sincronizar-button').addEventListener('click', function() {
            document.getElementById('loadingModal').classList.add('active');
            fetch('execute_sync.php')
                .then(r => r.text())
                .then(output => {
                    document.getElementById('loadingModal').classList.remove('active');
                    document.getElementById('successMessage').innerHTML = 'Sincronização concluída!<br>' + output + ' matrículas sincronizadas.';
                    document.getElementById('successModal').classList.add('active');
                }).catch(() => { document.getElementById('loadingModal').classList.remove('active'); alert('Erro!'); });
        });
        
        // View Indicator
        document.getElementById('visualizar-indicador').addEventListener('click', () => window.open('../indicador-pessoal/indicador-pessoal.php', '_blank'));
        
        // Export Excel
        document.getElementById('exportar-excel').addEventListener('click', function() {
            var table = "<table><thead><tr><th>Matrícula Nº</th></tr></thead><tbody>";
            <?php foreach ($numerosFaltantes as $nf): ?>table += "<tr><td><?= $nf ?></td></tr>";<?php endforeach; ?>
            table += "</tbody></table>";
            var blob = new Blob([table], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8" });
            var a = document.createElement('a');
            a.href = window.URL.createObjectURL(blob);
            a.download = 'matriculas_faltantes.xls';
            a.click();
        });
        
        function closeSuccessModal() { document.getElementById('successModal').classList.remove('active'); location.reload(); }
        
        // File inputs
        document.querySelectorAll('.file-input').forEach(input => {
            input.addEventListener('change', function() {
                const label = this.nextElementSibling;
                const text = label.querySelector('.file-input-text');
                if (this.files.length > 0) {
                    text.textContent = this.files.length > 1 ? this.files.length + ' arquivos' : this.files[0].name;
                    label.querySelector('.file-input-icon').className = 'fa-solid fa-check-circle file-input-icon';
                    label.querySelector('.file-input-icon').style.color = 'var(--color-accent)';
                }
            });
        });
    </script>
</body>
</html>
