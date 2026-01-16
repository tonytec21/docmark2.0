<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

function formatarDocumento($documento) {
    if (strlen($documento) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $documento);
    } elseif (strlen($documento) === 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $documento);
    }
    return $documento;
}

$matricula = $_GET['matricula'] ?? '';
$entries = [];

if (!empty($matricula)) {
    $filename = 'matriculas/' . $matricula . '.json';
    if (file_exists($filename)) {
        $data = json_decode(file_get_contents($filename), true);
        $entries = $data['entries'] ?? [];
    }
}

$matriculaNumero = ltrim($matricula, '0');
if (empty($matriculaNumero)) $matriculaNumero = $matricula;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Editar Indicador Pessoal</title>
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    <link rel="stylesheet" href="../css/docmark-modern.css">
    <style>
        .proprietario-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: var(--radius-xl);
            padding: 24px;
            margin-bottom: 20px;
            position: relative;
        }
        .proprietario-card:hover {
            border-color: rgba(16, 185, 129, 0.3);
        }
        .proprietario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .proprietario-numero {
            background: var(--color-accent);
            color: white;
            padding: 6px 16px;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 0.875rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        .form-row .form-group {
            margin-bottom: 0;
        }
        .btn-remove {
            background: rgba(248, 113, 113, 0.2);
            color: #f87171;
            border: 1px solid #f87171;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .btn-remove:hover {
            background: #f87171;
            color: white;
        }
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .matricula-badge {
            background: linear-gradient(135deg, var(--color-accent) 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            border-radius: var(--radius-lg);
            font-size: 1.25rem;
            font-weight: 600;
        }
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
                    <i class="fa-solid fa-edit" style="color: var(--color-accent-light);"></i>
                    Editar Indicador Pessoal
                </h1>
                <p class="page-subtitle">Edite os proprietários da matrícula</p>
            </div>
            
            <!-- Matrícula Info -->
            <div class="card animate-slide-up mb-8">
                <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; color: var(--color-gray-200);">Matrícula</h3>
                        <span class="matricula-badge">Nº <?= htmlspecialchars($matriculaNumero) ?></span>
                    </div>
                    <a href="matriculas.php" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
            
            <!-- Edit Form -->
            <form action="update.php" method="POST" id="editForm">
                <input type="hidden" name="matricula" value="<?= htmlspecialchars($matricula) ?>">
                
                <div id="proprietariosContainer">
                    <?php if (empty($entries)): ?>
                        <div class="alert alert-warning mb-6">
                            <i class="fa-solid fa-exclamation-triangle alert-icon"></i>
                            <div class="alert-content">Nenhum proprietário encontrado para esta matrícula.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($entries as $index => $entry): ?>
                            <div class="proprietario-card animate-slide-up" data-index="<?= $index ?>">
                                <div class="proprietario-header">
                                    <span class="proprietario-numero">Proprietário <?= $index + 1 ?></span>
                                    <button type="button" class="btn-remove" onclick="removeProprietario(this)">
                                        <i class="fa-solid fa-trash"></i> Remover
                                    </button>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Nome *</label>
                                        <input type="text" name="nome[]" class="form-control" value="<?= htmlspecialchars($entry['nome'] ?? '') ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">CPF/CNPJ *</label>
                                        <input type="text" name="cpf[]" class="form-control" value="<?= htmlspecialchars($entry['cpf'] ?? '') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row" style="margin-top: 16px;">
                                    <div class="form-group">
                                        <label class="form-label">Tipo de Ato</label>
                                        <input type="text" name="tipo_ato[]" class="form-control" value="<?= htmlspecialchars($entry['tipo_ato'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Data AV/R *</label>
                                        <input type="date" name="data_avr[]" class="form-control" value="<?= htmlspecialchars($entry['data_avr'] ?? '') ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Data Venda</label>
                                        <input type="date" name="data_venda[]" class="form-control" value="<?= htmlspecialchars($entry['data_venda'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="actions-bar">
                    <button type="button" class="btn btn-secondary" onclick="addProprietario()">
                        <i class="fa-solid fa-plus"></i> Adicionar Proprietário
                    </button>
                    <div class="flex gap-4">
                        <a href="matriculas.php" class="btn btn-secondary">
                            <i class="fa-solid fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </div>
            </form>
        </main>
        
        <?php include_once("../rodape-modern.php"); ?>
    </div>
    
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script>
        let proprietarioCount = <?= count($entries) ?>;
        
        function addProprietario() {
            proprietarioCount++;
            const container = document.getElementById('proprietariosContainer');
            
            const card = document.createElement('div');
            card.className = 'proprietario-card animate-slide-up';
            card.innerHTML = `
                <div class="proprietario-header">
                    <span class="proprietario-numero">Proprietário ${proprietarioCount}</span>
                    <button type="button" class="btn-remove" onclick="removeProprietario(this)">
                        <i class="fa-solid fa-trash"></i> Remover
                    </button>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome[]" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CPF/CNPJ *</label>
                        <input type="text" name="cpf[]" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 16px;">
                    <div class="form-group">
                        <label class="form-label">Tipo de Ato</label>
                        <input type="text" name="tipo_ato[]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data AV/R *</label>
                        <input type="date" name="data_avr[]" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data Venda</label>
                        <input type="date" name="data_venda[]" class="form-control">
                    </div>
                </div>
            `;
            
            container.appendChild(card);
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        function removeProprietario(btn) {
            const card = btn.closest('.proprietario-card');
            
            if (confirm('Tem certeza que deseja remover este proprietário?')) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    card.remove();
                    updateNumeros();
                }, 300);
            }
        }
        
        function updateNumeros() {
            const cards = document.querySelectorAll('.proprietario-card');
            cards.forEach((card, index) => {
                const numero = card.querySelector('.proprietario-numero');
                if (numero) {
                    numero.textContent = 'Proprietário ' + (index + 1);
                }
            });
            proprietarioCount = cards.length;
        }
        
        // Validação antes de enviar
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const cards = document.querySelectorAll('.proprietario-card');
            if (cards.length === 0) {
                e.preventDefault();
                alert('Adicione pelo menos um proprietário antes de salvar.');
                return false;
            }
        });
    </script>
</body>
</html>
