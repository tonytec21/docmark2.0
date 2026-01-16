<?php
// Inclua a função verificar_sessao_ativa()
require_once 'funcoes.php';

// Verifique se a sessão está ativa
verificar_sessao_ativa();

function getDataHoraArquivoZIP($arquivoZIP) {
    $nomeArquivo = basename($arquivoZIP);
    $padrao = '/arquivos_(\d{8}_\d{6})\.zip/';
    preg_match($padrao, $nomeArquivo, $matches);
    if (!isset($matches[1])) return null;
    $dataHoraString = str_replace('_', '', $matches[1]);
    $dataHora = DateTime::createFromFormat('YmdHis', $dataHoraString);
    return $dataHora;
}

// Array de meses em português
$meses = [
    1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
    5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
    9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DocMark - Carimbo Digital">
    <title>DocMark - Carimbo Digital</title>
    
    <!-- Favicon -->
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../css/docmark-modern.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <i class="fa-solid fa-stamp" style="color: var(--color-accent-light);"></i>
                    Carimbo Digital
                </h1>
                <p class="page-subtitle">Processe arquivos PDF e adicione carimbos digitais automaticamente</p>
            </div>
            
            <!-- Upload Card -->
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon">
                            <i class="fa-solid fa-upload"></i>
                        </span>
                        Processar Arquivos
                    </h3>
                </div>
                <div class="card-body">
                    <form action="processar.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="form-group">
                            <label class="form-label">Selecione os arquivos PDF</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="arquivoPDF[]" id="arquivoPDF" class="file-input" multiple accept=".pdf">
                                <label for="arquivoPDF" class="file-input-label" id="fileLabel">
                                    <i class="fa-solid fa-cloud-arrow-up file-input-icon"></i>
                                    <span class="file-input-text">Clique para selecionar ou arraste os arquivos</span>
                                    <span class="file-input-hint">Formato aceito: PDF</span>
                                </label>
                            </div>
                            <div id="fileList" class="mt-4" style="display: none;"></div>
                        </div>
                        <div class="flex justify-center mt-6">
                            <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                <i class="fa-solid fa-cogs"></i>
                                Processar Arquivos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- History Section -->
            <div class="card animate-slide-up">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </span>
                        Histórico de Processamento
                    </h3>
                </div>
                <div class="card-body">
                    <?php
                    $arquivosZIP = glob('arquivos/arquivos_*.zip');
                    $conversoesPorDia = [];
                    
                    // Processar dados para o gráfico
                    foreach ($arquivosZIP as $arquivoZIP) {
                        $dataHora = getDataHoraArquivoZIP($arquivoZIP);
                        if ($dataHora) {
                            $data = $dataHora->format('Y-m-d');
                            if (!isset($conversoesPorDia[$data])) {
                                $conversoesPorDia[$data] = 0;
                            }
                            $conversoesPorDia[$data]++;
                        }
                    }
                    ?>
                    
                    <!-- Chart -->
                    <div class="chart-container mb-8">
                        <canvas id="conversionChart" style="max-height: 300px;"></canvas>
                    </div>
                    
                    <!-- History List -->
                    <?php
                    $arquivosZIP = array_reverse(glob('arquivos/arquivos_*.zip'));
                    
                    if (empty($arquivosZIP)) : ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-folder-open empty-state-icon"></i>
                            <h4 class="empty-state-title">Nenhum processamento encontrado</h4>
                            <p class="empty-state-text">Os arquivos processados aparecerão aqui</p>
                        </div>
                    <?php else : ?>
                        <ul class="list">
                            <?php foreach ($arquivosZIP as $arquivoZIP) :
                                $dataHora = getDataHoraArquivoZIP($arquivoZIP);
                                if (!$dataHora) continue;
                                
                                $dia = $dataHora->format('d');
                                $mes = $meses[(int)$dataHora->format('m')];
                                $ano = $dataHora->format('Y');
                                $hora = $dataHora->format('H:i:s');
                                $dataHoraFormatada = $dia . ' de ' . $mes . ' de ' . $ano . ', às ' . $hora;
                            ?>
                                <li class="list-item">
                                    <div class="list-item-content">
                                        <div class="list-item-title">
                                            <i class="fa-regular fa-file-zipper" style="color: var(--color-accent-light);"></i>
                                            <?= basename($arquivoZIP) ?>
                                        </div>
                                        <div class="list-item-subtitle">
                                            <i class="fa-regular fa-clock"></i>
                                            Processado em <?= $dataHoraFormatada ?>
                                        </div>
                                    </div>
                                    <div class="list-item-actions">
                                        <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/carimbo-digital/arquivos/' . basename($arquivoZIP) ?>" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-download"></i>
                                            Download
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <?php include_once("../rodape-modern.php"); ?>
    </div>
    
    <!-- Scripts -->
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script>
        // File input handling
        const fileInput = document.getElementById('arquivoPDF');
        const fileLabel = document.getElementById('fileLabel');
        const fileList = document.getElementById('fileList');
        
        fileInput.addEventListener('change', function() {
            const files = this.files;
            if (files.length > 0) {
                fileLabel.innerHTML = `
                    <i class="fa-solid fa-check-circle file-input-icon" style="color: var(--color-accent);"></i>
                    <span class="file-input-text">${files.length} arquivo(s) selecionado(s)</span>
                    <span class="file-input-hint">Clique para alterar a seleção</span>
                `;
                
                let listHTML = '<div style="text-align: left;">';
                for (let i = 0; i < files.length; i++) {
                    listHTML += `<div class="badge badge-info" style="margin: 4px;"><i class="fa-regular fa-file-pdf"></i> ${files[i].name}</div>`;
                }
                listHTML += '</div>';
                fileList.innerHTML = listHTML;
                fileList.style.display = 'block';
            }
        });
        
        // Drag and drop
        fileLabel.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--color-accent)';
            this.style.background = 'rgba(16, 185, 129, 0.1)';
        });
        
        fileLabel.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.background = '';
        });
        
        fileLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.background = '';
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        });
        
        // Chart configuration
        const conversionData = <?= json_encode($conversoesPorDia) ?>;
        const labels = Object.keys(conversionData);
        const data = Object.values(conversionData);
        
        if (labels.length > 0) {
            const ctx = document.getElementById('conversionChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Processamentos por Dia',
                        data: data,
                        backgroundColor: 'rgba(16, 185, 129, 0.6)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#cbd5e1',
                                font: {
                                    family: "'Poppins', sans-serif"
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)'
                            },
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    family: "'Source Sans Pro', sans-serif"
                                }
                            },
                            title: {
                                display: true,
                                text: 'Quantidade',
                                color: '#cbd5e1',
                                font: {
                                    family: "'Poppins', sans-serif"
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)'
                            },
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    family: "'Source Sans Pro', sans-serif"
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
