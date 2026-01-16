<?php
// Função verificar_sessao_ativa()
require_once 'funcoes.php';

// Verifique se a sessão está ativa
verificar_sessao_ativa();

// Função para limpar os arquivos PDF dentro do diretório "pdfs"
function limparPastaPDFs($pastaPDFs)
{
    $arquivosPDF = glob($pastaPDFs . '/*.pdf');
    foreach ($arquivosPDF as $arquivoPDF) {
        unlink($arquivoPDF);
    }
}

// Pasta onde estão armazenados os arquivos ZIP
$pastaArquivos = __DIR__ . '/arquivos';
$pastaPDFs = __DIR__ . '/pdfs';

// Limpa os arquivos PDF sempre que a página for carregada
limparPastaPDFs($pastaPDFs);

ob_start();

// Obtém a lista de arquivos ZIP na pasta
$arquivosZIP = glob($pastaArquivos . '/*.zip');

// Função para obter a data e hora de um arquivo ZIP
function getDataHoraArquivoZIP($arquivoZIP)
{
    $nomeArquivo = basename($arquivoZIP, '.zip');
    $dataHora = substr($nomeArquivo, strlen('arquivos_'));
    return DateTime::createFromFormat('YmdHis', $dataHora);
}

// Ordena os arquivos ZIP por data e hora
usort($arquivosZIP, function ($a, $b) {
    $dataHoraA = getDataHoraArquivoZIP($a);
    $dataHoraB = getDataHoraArquivoZIP($b);
    return $dataHoraA > $dataHoraB ? -1 : 1;
});

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
    <meta name="description" content="DocMark - Converter PDF para TIFF">
    <title>DocMark - PDF para TIFF</title>
    
    <!-- Favicon -->
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../css/docmark-modern.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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
                    <i class="fa-solid fa-file-image" style="color: var(--color-accent-light);"></i>
                    PDF para TIFF
                </h1>
                <p class="page-subtitle">Converta seus arquivos PDF para o formato TIFF de alta qualidade</p>
            </div>
            
            <!-- Upload Card -->
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="card-title-icon">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </span>
                        Converter Arquivos
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="converter.php" enctype="multipart/form-data" id="uploadForm">
                        <div class="form-group">
                            <label class="form-label">Selecione os arquivos PDF</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="pdfs" name="pdfs[]" class="file-input" accept=".pdf" required multiple>
                                <label for="pdfs" class="file-input-label" id="fileLabel">
                                    <i class="fa-solid fa-cloud-arrow-up file-input-icon"></i>
                                    <span class="file-input-text">Clique para selecionar ou arraste os arquivos</span>
                                    <span class="file-input-hint">Formato aceito: PDF</span>
                                </label>
                            </div>
                            <div id="fileList" class="mt-4" style="display: none;"></div>
                        </div>
                        <div class="flex justify-center mt-6">
                            <button type="submit" class="btn btn-primary btn-lg" id="btnConverter">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                Converter para TIFF
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
                        Histórico de Conversões
                    </h3>
                </div>
                <div class="card-body">
                    <?php
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
                    <?php if (empty($arquivosZIP)) : ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-folder-open empty-state-icon"></i>
                            <h4 class="empty-state-title">Nenhuma conversão encontrada</h4>
                            <p class="empty-state-text">Os arquivos convertidos aparecerão aqui</p>
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
                                            Conversão realizada em <?= $dataHoraFormatada ?>
                                        </div>
                                    </div>
                                    <div class="list-item-actions">
                                        <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/pdf-para-tiff/arquivos/' . basename($arquivoZIP) ?>" class="btn btn-primary btn-sm">
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
    
    <!-- Loading Modal -->
    <div id="loadingModal" class="modal-backdrop">
        <div class="modal" style="text-align: center; max-width: 350px;">
            <div class="spinner" style="margin: 0 auto 20px;"></div>
            <h3 style="margin-bottom: 8px;" id="loadingTitle">Convertendo Arquivos...</h3>
            <p style="color: var(--color-gray-400); margin: 0;" id="loadingText">Por favor, aguarde enquanto os PDFs estão sendo convertidos para TIFF.</p>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script>
        // File input handling
        const fileInput = document.getElementById('pdfs');
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
        
        // Form submit com validação e loading
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const files = fileInput.files;
            
            if (files.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Nenhum arquivo selecionado',
                    text: 'Por favor, selecione pelo menos um arquivo PDF para converter.',
                    confirmButtonColor: '#10b981'
                });
                return false;
            }
            
            // Verificar se todos são PDFs
            for (let i = 0; i < files.length; i++) {
                if (!files[i].name.toLowerCase().endsWith('.pdf')) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Arquivo inválido',
                        text: `O arquivo "${files[i].name}" não é um PDF válido.`,
                        confirmButtonColor: '#10b981'
                    });
                    return false;
                }
            }
            
            // Mostrar loading
            document.getElementById('loadingTitle').textContent = 'Convertendo Arquivos...';
            document.getElementById('loadingText').textContent = `Convertendo ${files.length} arquivo(s) PDF para TIFF. Por favor, aguarde.`;
            document.getElementById('loadingModal').classList.add('active');
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
                        label: 'Conversões por Dia',
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