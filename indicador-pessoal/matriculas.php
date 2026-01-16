<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

$files = glob('matriculas/*.json');

function formatarDocumento($documento) {
    if (strlen($documento) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $documento);
    } elseif (strlen($documento) === 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $documento);
    }
    return $documento;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Matrículas Cadastradas</title>
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
        table.dataTable thead th { background: rgba(255, 255, 255, 0.03) !important; color: var(--color-gray-200) !important;
            font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 16px !important; border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important; }
        table.dataTable tbody td { padding: 14px 16px !important; border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important; color: var(--color-gray-300); }
        table.dataTable tbody tr:hover { background: rgba(255, 255, 255, 0.03) !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { color: var(--color-gray-300) !important; background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important; border-radius: var(--radius-md) !important; margin: 0 2px !important; padding: 6px 12px !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover, .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--color-accent) !important; border-color: var(--color-accent) !important; color: var(--color-white) !important; }
        .action-buttons { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
        .owner-group { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 16px; }
        .owner-group-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .owner-group-title { font-weight: 600; color: var(--color-accent-light); }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include_once("../header-modern.php"); ?>
        <?php include_once("../menu-modern.php"); ?>
        
        <main class="main-content">
            <div class="page-header animate-fade-in">
                <h1 class="page-title"><i class="fa-solid fa-user-plus" style="color: var(--color-accent-light);"></i> Matrículas Cadastradas</h1>
                <p class="page-subtitle">Gerencie os cadastros do indicador pessoal</p>
            </div>
            
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title"><span class="card-title-icon"><i class="fa-solid fa-bolt"></i></span> Ações Rápidas</h3>
                </div>
                <div class="card-body">
                    <div class="action-buttons">
                        <button class="btn btn-primary" id="btnCadastrar"><i class="fa-solid fa-plus"></i> Cadastrar Matrícula</button>
                        <button class="btn btn-secondary" id="btnVisualizarXML"><i class="fa-solid fa-eye"></i> Visualizar XML</button>
                        <button class="btn btn-secondary" id="btnGerarXML"><i class="fa-solid fa-file-code"></i> Gerar XML</button>
                    </div>
                </div>
            </div>
            
            <div class="card animate-slide-up">
                <div class="card-header">
                    <h3 class="card-title"><span class="card-title-icon"><i class="fa-solid fa-list"></i></span> Indicador Pessoal
                        <span class="badge badge-info" style="margin-left: 12px;"><?= count($files) ?> registros</span></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($files)) : ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-folder-open empty-state-icon"></i>
                            <h4 class="empty-state-title">Nenhuma matrícula cadastrada</h4>
                            <p class="empty-state-text">Clique em "Cadastrar Matrícula" para adicionar</p>
                        </div>
                    <?php else : ?>
                        <div class="table-wrapper">
                            <table id="matriculasTable" class="table" style="width: 100%;">
                                <thead><tr><th>Matrícula</th><th>Proprietário(s)</th><th>CPF/CNPJ</th><th>Atualização</th><th>Ações</th></tr></thead>
                                <tbody>
                                    <?php foreach ($files as $file) :
                                        $content = json_decode(file_get_contents($file), true);
                                        $nomes = []; $cpfs = [];
                                        foreach ($content['entries'] as $entry) { $nomes[] = $entry['nome']; $cpfs[] = $entry['cpf']; }
                                        $nomeString = implode(", ", $nomes);
                                        $cpfString = implode(", ", array_map('formatarDocumento', $cpfs));
                                        $filename = basename($file, '.json');
                                        $lastModified = date("d/m/Y", filemtime($file)); ?>
                                        <tr>
                                            <td><code style="color: var(--color-accent-light); font-weight: 600;"><?= htmlspecialchars($filename) ?></code></td>
                                            <td><?= htmlspecialchars($nomeString) ?></td>
                                            <td><span class="badge badge-info"><?= htmlspecialchars($cpfString) ?></span></td>
                                            <td><?= $lastModified ?></td>
                                            <td><div class="flex gap-2">
                                                <a href="edit.php?matricula=<?= $filename ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i></a>
                                                <a href="delete.php?matricula=<?= $filename ?>" class="btn btn-danger btn-sm" onclick="return confirm('Excluir esta matrícula?')"><i class="fa-solid fa-trash"></i></a>
                                            </div></td>
                                        </tr>
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
    
    <!-- Modal: Cadastrar -->
    <div id="cadastroModal" class="modal-backdrop">
        <div class="modal" style="max-width: 700px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-plus" style="color: var(--color-accent);"></i> Cadastrar Matrícula</h3>
                <button class="modal-close" onclick="fecharModal('cadastroModal')">&times;</button>
            </div>
            <form action="save.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Nº Matrícula</label>
                    <input type="text" name="matricula" class="form-control" placeholder="Número da matrícula" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
                </div>
                <div id="proprietariosContainer">
                    <div class="owner-group">
                        <div class="owner-group-header"><span class="owner-group-title"><i class="fa-solid fa-user"></i> Proprietário 1</span></div>
                        <div class="form-row">
                            <div class="form-group"><label class="form-label">Nome</label><input type="text" name="nome[]" class="form-control" required></div>
                            <div class="form-group"><label class="form-label">CPF/CNPJ</label><input type="text" name="cpf[]" class="form-control" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label class="form-label">Tipo de Ato</label><input type="text" name="tipo_ato[]" class="form-control"></div>
                            <div class="form-group"><label class="form-label">Data AV/R</label><input type="date" name="data_avr[]" class="form-control" required></div>
                            <div class="form-group"><label class="form-label">Data Venda</label><input type="date" name="data_venda[]" class="form-control"></div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-4 mt-4">
                    <button type="button" class="btn btn-secondary" onclick="addProprietario()"><i class="fa-solid fa-plus"></i> Proprietário</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Gerar XML -->
    <div id="xmlModal" class="modal-backdrop">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa-solid fa-file-code" style="color: var(--color-accent);"></i> Gerar XML</h3>
                <button class="modal-close" onclick="fecharModal('xmlModal')">&times;</button>
            </div>
            <form action="processXML.php" method="post">
                <div class="form-group"><label class="form-label">Data Inicial</label><input type="date" name="start_date" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Data Final</label><input type="date" name="end_date" class="form-control" required></div>
                <button type="submit" class="btn btn-primary w-full"><i class="fa-solid fa-download"></i> Gerar XML</button>
            </form>
        </div>
    </div>
    
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() { <?php if (!empty($files)) : ?>$('#matriculasTable').DataTable({language:{url:'//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'},pageLength:25});<?php endif; ?> });
        
        document.getElementById('btnCadastrar').onclick = () => document.getElementById('cadastroModal').classList.add('active');
        document.getElementById('btnGerarXML').onclick = () => document.getElementById('xmlModal').classList.add('active');
        document.getElementById('btnVisualizarXML').onclick = () => window.open('indicador-pessoal.php', '_blank');
        
        function fecharModal(id) { document.getElementById(id).classList.remove('active'); }
        document.querySelectorAll('.modal-backdrop').forEach(m => m.onclick = e => { if(e.target === m) m.classList.remove('active'); });
        
        let ownerCount = 1;
        function addProprietario() {
            ownerCount++;
            const container = document.getElementById('proprietariosContainer');
            const div = document.createElement('div');
            div.className = 'owner-group';
            div.innerHTML = `<div class="owner-group-header"><span class="owner-group-title"><i class="fa-solid fa-user"></i> Proprietário ${ownerCount}</span>
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.owner-group').remove()"><i class="fa-solid fa-times"></i></button></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Nome</label><input type="text" name="nome[]" class="form-control" required></div>
                <div class="form-group"><label class="form-label">CPF/CNPJ</label><input type="text" name="cpf[]" class="form-control" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required></div></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Tipo de Ato</label><input type="text" name="tipo_ato[]" class="form-control"></div>
                <div class="form-group"><label class="form-label">Data AV/R</label><input type="date" name="data_avr[]" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Data Venda</label><input type="date" name="data_venda[]" class="form-control"></div></div>`;
            container.appendChild(div);
        }
    </script>
</body>
</html>
