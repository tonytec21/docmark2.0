<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

$files = glob('indicador/*.json');

function formatarDocumento($documento) {
    if (strlen($documento) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $documento);
    } elseif (strlen($documento) === 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $documento);
    }
    return $documento;
}

$pastaHistorico = __DIR__ . '/indicador';
$arquivos = glob($pastaHistorico . '/*');
$numerosFaltantes = [];

if (!empty($arquivos)) {
    setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
    $numerosArquivos = array();
    foreach ($arquivos as $arquivo) {
        $nomeArquivo = basename($arquivo);
        $numeroArquivo = (int) substr($nomeArquivo, strpos($nomeArquivo, '_') + 1);
        $numerosArquivos[] = $numeroArquivo;
    }
    $minimo = 1;
    $maximo = max($numerosArquivos);
    for ($i = $minimo; $i <= $maximo; $i++) {
        if (!in_array($i, $numerosArquivos)) {
            $numerosFaltantes[] = str_pad($i, 8, '0', STR_PAD_LEFT);
        }
    }
}

// Arrays de opções
$tiposRegistro = [1 => 'Matrícula', 2 => 'Matrícula MÃE', 3 => 'Livro 3-reg-aux', 4 => 'Transcrição'];
$tiposImovel = [1 => 'Casa', 2 => 'Apartamento', 3 => 'Loja', 4 => 'Sala/Conjunto', 5 => 'Terreno/Fração', 6 => 'Galpão', 7 => 'Prédio comercial', 8 => 'Prédio residencial', 9 => 'Fazenda/sítio/chácara', 10 => 'Vaga', 11 => 'Depósito'];
$localizacoes = [0 => 'Urbano', 1 => 'Rural'];

// Cidades do Maranhão (resumido)
$cidades = ["2100055" => "Açailândia", "2100105" => "Afonso Cunha", "2100154" => "Água Doce do Maranhão", "2100204" => "Alcântara", "2100303" => "Aldeias Altas", "2100402" => "Altamira do Maranhão", "2100436" => "Alto Alegre do Maranhão", "2100477" => "Alto Alegre do Pindaré", "2100501" => "Alto Parnaíba", "2100550" => "Amapá do Maranhão", "2100600" => "Amarante do Maranhão", "2100709" => "Anajatuba", "2100808" => "Anapurus", "2100832" => "Apicum-Açu", "2100873" => "Araguanã", "2100907" => "Araioses", "2100956" => "Arame", "2101004" => "Arari", "2101103" => "Axixá", "2101202" => "Bacabal", "2101251" => "Bacabeira", "2101301" => "Bacuri", "2101350" => "Bacurituba", "2101400" => "Balsas", "2101509" => "Barão de Grajaú", "2101608" => "Barra do Corda", "2101707" => "Barreirinhas", "2101731" => "Bela Vista do Maranhão", "2101772" => "Belágua", "2101806" => "Benedito Leite", "2101905" => "Bequimão", "2101939" => "Bernardo do Mearim", "2101970" => "Boa Vista do Gurupi", "2102002" => "Bom Jardim", "2102036" => "Bom Jesus das Selvas", "2102077" => "Bom Lugar", "2102101" => "Brejo", "2102150" => "Brejo de Areia", "2102200" => "Buriti", "2102309" => "Buriti Bravo", "2102325" => "Buriticupu", "2102358" => "Buritirana", "2102374" => "Cachoeira Grande", "2102408" => "Cajapió", "2102507" => "Cajari", "2102556" => "Campestre do Maranhão", "2102606" => "Cândido Mendes", "2102705" => "Cantanhede", "2102754" => "Capinzal do Norte", "2102804" => "Carolina", "2102903" => "Carutapera", "2103000" => "Caxias", "2103109" => "Cedral", "2103125" => "Central do Maranhão", "2103158" => "Centro do Guilherme", "2103174" => "Centro Novo do Maranhão", "2103208" => "Chapadinha", "2103257" => "Cidelândia", "2103307" => "Codó", "2103406" => "Coelho Neto", "2103505" => "Colinas", "2103604" => "Conceição do Lago-Açu", "2103703" => "Coroatá", "2103752" => "Cururupu", "2103802" => "Davinópolis", "2103901" => "Dom Pedro", "2103950" => "Duque Bacelar", "2104008" => "Esperantinópolis", "2104057" => "Estreito", "2104073" => "Feira Nova do Maranhão", "2104099" => "Fernando Falcão", "2104107" => "Formosa da Serra Negra", "2104206" => "Fortaleza dos Nogueiras", "2104305" => "Fortuna", "2104404" => "Godofredo Viana", "2104503" => "Gonçalves Dias", "2104552" => "Governador Archer", "2104602" => "Governador Edison Lobão", "2104628" => "Governador Eugênio Barros", "2104651" => "Governador Luiz Rocha", "2104677" => "Governador Newton Bello", "2104701" => "Governador Nunes Freire", "2104800" => "Graça Aranha", "2104909" => "Grajaú", "2105005" => "Guimarães", "2105104" => "Humberto de Campos", "2105153" => "Icatu", "2105203" => "Igarapé do Meio", "2105302" => "Igarapé Grande", "2105351" => "Imperatriz", "2105401" => "Itaipava do Grajaú", "2105427" => "Itapecuru Mirim", "2105450" => "Itinga do Maranhão", "2105476" => "Jatobá", "2105500" => "Jenipapo dos Vieiras", "2105609" => "João Lisboa", "2105658" => "Joselândia", "2105708" => "Junco do Maranhão", "2105807" => "Lago da Pedra", "2105906" => "Lago do Junco", "2105922" => "Lago dos Rodrigues", "2105948" => "Lago Verde", "2105963" => "Lagoa do Mato", "2105989" => "Lagoa Grande do Maranhão", "2106003" => "Lajeado Novo", "2106102" => "Lima Campos", "2106201" => "Loreto", "2106300" => "Luís Domingues", "2106326" => "Magalhães de Almeida", "2106359" => "Maracaçumé", "2106375" => "Marajá do Sena", "2106409" => "Maranhãozinho", "2106508" => "Mata Roma", "2106607" => "Matinha", "2106631" => "Matões", "2106672" => "Matões do Norte", "2106706" => "Mirador", "2106755" => "Miranda do Norte", "2106805" => "Mirinzal", "2106904" => "Monção", "2107001" => "Montes Altos", "2107100" => "Morros", "2107209" => "Nina Rodrigues", "2107258" => "Nova Colinas", "2107308" => "Nova Iorque", "2107357" => "Nova Olinda do Maranhão", "2107407" => "Olho d'Água das Cunhãs", "2107456" => "Olinda Nova do Maranhão", "2107506" => "Paço do Lumiar", "2107605" => "Palmeirândia", "2107704" => "Paraibano", "2107803" => "Parnarama", "2107902" => "Passagem Franca", "2108009" => "Pastos Bons", "2108058" => "Paulino Neves", "2108108" => "Paulo Ramos", "2108207" => "Pedreiras", "2108256" => "Pedro do Rosário", "2108306" => "Penalva", "2108405" => "Peri Mirim", "2108454" => "Peritoró", "2108504" => "Pindaré-Mirim", "2108603" => "Pinheiro", "2108702" => "Pio XII", "2108801" => "Pirapemas", "2108900" => "Poção de Pedras", "2109007" => "Porto Franco", "2109056" => "Porto Rico do Maranhão", "2109106" => "Presidente Dutra", "2109205" => "Presidente Juscelino", "2109239" => "Presidente Médici", "2109270" => "Presidente Sarney", "2109304" => "Presidente Vargas", "2109403" => "Primeira Cruz", "2109452" => "Raposa", "2109502" => "Riachão", "2109551" => "Ribamar Fiquene", "2109601" => "Rosário", "2109700" => "Sambaíba", "2109759" => "Santa Filomena do Maranhão", "2109809" => "Santa Helena", "2109908" => "Santa Inês", "2110005" => "Santa Luzia", "2110039" => "Santa Luzia do Paruá", "2110104" => "Santa Quitéria do Maranhão", "2110203" => "Santa Rita", "2110237" => "Santana do Maranhão", "2110278" => "Santo Amaro do Maranhão", "2110302" => "Santo Antônio dos Lopes", "2110401" => "São Benedito do Rio Preto", "2110500" => "São Bento", "2110609" => "São Bernardo", "2110658" => "São Domingos do Azeitão", "2110708" => "São Domingos do Maranhão", "2110807" => "São Félix de Balsas", "2110856" => "São Francisco do Brejão", "2110906" => "São Francisco do Maranhão", "2111003" => "São João Batista", "2111029" => "São João do Carú", "2111052" => "São João do Paraíso", "2111078" => "São João do Soter", "2111102" => "São João dos Patos", "2111201" => "São José de Ribamar", "2111250" => "São José dos Basílios", "2111300" => "São Luís", "2111409" => "São Luís Gonzaga do Maranhão", "2111508" => "São Mateus do Maranhão", "2111532" => "São Pedro da Água Branca", "2111573" => "São Pedro dos Crentes", "2111607" => "São Raimundo das Mangabeiras", "2111631" => "São Raimundo do Doca Bezerra", "2111672" => "São Roberto", "2111706" => "São Vicente Ferrer", "2111722" => "Satubinha", "2111748" => "Senador Alexandre Costa", "2111763" => "Senador La Rocque", "2111789" => "Serrano do Maranhão", "2111805" => "Sítio Novo", "2111904" => "Sucupira do Norte", "2111953" => "Sucupira do Riachão", "2112001" => "Tasso Fragoso", "2112100" => "Timbiras", "2112209" => "Timon", "2112233" => "Trizidela do Vale", "2112274" => "Tufilândia", "2112308" => "Tuntum", "2112407" => "Turiaçu", "2112456" => "Turilândia", "2112506" => "Tutóia", "2112605" => "Urbano Santos", "2112704" => "Vargem Grande", "2112803" => "Viana", "2112852" => "Vila Nova dos Martírios", "2112902" => "Vitória do Mearim", "2113009" => "Vitorino Freire", "2114007" => "Zé Doca"];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocMark - Indicador Real</title>
    <link rel="icon" href="../img/NOVA_LOGO.png" type="image/png">
    <link rel="stylesheet" href="../css/docmark-modern.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { color: var(--color-gray-300); padding: var(--space-4) 0; }
        .dataTables_wrapper .dataTables_length select, .dataTables_wrapper .dataTables_filter input { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: var(--radius-md); color: var(--color-white); padding: 8px 12px; }
        .dataTables_wrapper .dataTables_filter input:focus { outline: none; border-color: var(--color-accent); box-shadow: 0 0 0 3px var(--color-accent-glow); }
        table.dataTable thead th { background: rgba(255, 255, 255, 0.03) !important; color: var(--color-gray-200) !important; font-weight: 600; text-transform: uppercase; font-size: 0.7rem; padding: 14px 10px !important; border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important; }
        table.dataTable tbody td { padding: 12px 10px !important; border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important; color: var(--color-gray-300); font-size: 0.85rem; }
        table.dataTable tbody tr:hover { background: rgba(255, 255, 255, 0.03) !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { color: var(--color-gray-300) !important; background: rgba(255, 255, 255, 0.05) !important; border: 1px solid rgba(255, 255, 255, 0.1) !important; border-radius: var(--radius-md) !important; margin: 0 2px !important; padding: 6px 12px !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover, .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--color-accent) !important; border-color: var(--color-accent) !important; color: var(--color-white) !important; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .form-grid .form-group { margin-bottom: 0; }
        .form-grid-full { grid-column: 1 / -1; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include_once("../header-modern.php"); ?>
        <?php include_once("../menu-modern.php"); ?>
        
        <main class="main-content">
            <div class="page-header animate-fade-in">
                <h1 class="page-title"><i class="fa-solid fa-building-columns" style="color: var(--color-accent-light);"></i> Indicador Real</h1>
                <p class="page-subtitle">Cadastre e gerencie os indicadores reais</p>
            </div>
            
            <!-- Cadastro -->
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title"><span class="card-title-icon"><i class="fa-solid fa-plus-circle"></i></span> Cadastrar Indicador Real</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Número de Registro *</label>
                                <input type="text" name="numero_registro" class="form-control" required oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="Ex.: 123...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tipo de Registro *</label>
                                <select name="registro_tipo" class="form-control" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($tiposRegistro as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tipo de Imóvel *</label>
                                <select name="tipo_imovel" class="form-control" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($tiposImovel as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Localização *</label>
                                <select name="localizacao" class="form-control" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($localizacoes as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nome do Logradouro</label>
                                <input type="text" name="nome_logradouro" class="form-control" placeholder="Rua, Av...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Número</label>
                                <input type="text" name="numero_logradouro" class="form-control" placeholder="Nº">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control" placeholder="Bairro">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Cidade *</label>
                                <select name="cidade" class="form-control" required>
                                    <option value="">Selecione a cidade</option>
                                    <?php foreach ($cidades as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">CEP</label>
                                <input type="text" name="cep" class="form-control" placeholder="00000-000">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Área (m²)</label>
                                <input type="text" name="area" class="form-control" placeholder="0.00">
                            </div>
                            <div class="form-group form-grid-full" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px;">
                                <button type="reset" class="btn btn-secondary"><i class="fa-solid fa-eraser"></i> Limpar</button>
                                <button type="submit" name="cadastrar" class="btn btn-primary"><i class="fa-solid fa-save"></i> Cadastrar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php
            // Processar cadastro
            if (isset($_POST['cadastrar'])) {
                $dados = [
                    'REGISTRO_TIPO' => $_POST['registro_tipo'],
                    'NUMERO_REGISTRO' => $_POST['numero_registro'],
                    'TIPO_IMOVEL' => $_POST['tipo_imovel'],
                    'LOCALIZACAO' => $_POST['localizacao'],
                    'NOME_LOGRADOURO' => $_POST['nome_logradouro'] ?? '',
                    'NUMERO_LOGRADOURO' => $_POST['numero_logradouro'] ?? '',
                    'BAIRRO' => $_POST['bairro'] ?? '',
                    'CIDADE' => $_POST['cidade'],
                    'CEP' => $_POST['cep'] ?? '',
                    'AREA' => $_POST['area'] ?? ''
                ];
                $nomeArquivo = 'indicador/' . $dados['REGISTRO_TIPO'] . '_' . $dados['NUMERO_REGISTRO'] . '.json';
                if (file_put_contents($nomeArquivo, json_encode($dados, JSON_PRETTY_PRINT))) {
                    echo '<div class="alert alert-success mb-6 animate-fade-in"><i class="fa-solid fa-check-circle alert-icon"></i><div class="alert-content">Indicador cadastrado com sucesso!</div></div>';
                    $files = glob('indicador/*.json'); // Atualiza lista
                } else {
                    echo '<div class="alert alert-error mb-6 animate-fade-in"><i class="fa-solid fa-circle-exclamation alert-icon"></i><div class="alert-content">Erro ao cadastrar indicador.</div></div>';
                }
            }
            ?>
            
            <!-- Ações Rápidas -->
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title"><span class="card-title-icon"><i class="fa-solid fa-bolt"></i></span> Ações Rápidas</h3>
                </div>
                <div class="card-body">
                    <div class="flex flex-wrap gap-4">
                        <button class="btn btn-primary" id="openModalBtn"><i class="fa-solid fa-file-export"></i> Exportar Carga ONR</button>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de Registros -->
            <div class="card animate-slide-up mb-8">
                <div class="card-header">
                    <h3 class="card-title"><span class="card-title-icon"><i class="fa-solid fa-list"></i></span> Indicadores Cadastrados
                        <?php if (!empty($files)): ?><span class="badge badge-info" style="margin-left: 12px;"><?= count($files) ?> registros</span><?php endif; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($files)): ?>
                        <div class="empty-state"><i class="fa-regular fa-folder-open empty-state-icon"></i><h4 class="empty-state-title">Nenhum indicador cadastrado</h4><p class="empty-state-text">Cadastre um novo indicador usando o formulário acima</p></div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table id="matriculasTable" class="table" style="width: 100%;">
                                <thead><tr><th>Nº Registro</th><th>Tipo Registro</th><th>Tipo Imóvel</th><th>Localização</th><th>Logradouro</th><th>Nº</th><th>Bairro</th><th>Cidade</th><th>Ações</th></tr></thead>
                                <tbody>
                                <?php foreach ($files as $file):
                                    $item = json_decode(file_get_contents($file), true);
                                    $tipo_registro = $tiposRegistro[$item['REGISTRO_TIPO']] ?? '-';
                                    $tipo_imovel = $tiposImovel[$item['TIPO_IMOVEL']] ?? '-';
                                    $localizacao = $localizacoes[$item['LOCALIZACAO']] ?? '-';
                                    $descricaoCidade = $cidades[$item['CIDADE']] ?? '-';
                                ?>
                                    <tr>
                                        <td><code style="color: var(--color-accent-light); font-weight: 600;"><?= str_pad($item['NUMERO_REGISTRO'], 8, '0', STR_PAD_LEFT) ?></code></td>
                                        <td><?= $tipo_registro ?></td>
                                        <td><?= $tipo_imovel ?></td>
                                        <td><span class="badge <?= $item['LOCALIZACAO'] == 0 ? 'badge-info' : 'badge-success' ?>"><?= $localizacao ?></span></td>
                                        <td><?= htmlspecialchars($item['NOME_LOGRADOURO'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($item['NUMERO_LOGRADOURO'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($item['BAIRRO'] ?? '-') ?></td>
                                        <td><?= $descricaoCidade ?></td>
                                        <td>
                                            <div class="flex gap-2">
                                                <a href="indicador/<?= $item['REGISTRO_TIPO'] . '_' . $item['NUMERO_REGISTRO'] ?>.json" download class="btn btn-primary btn-sm" title="Download"><i class="fa-solid fa-download"></i></a>
                                                <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= $item['REGISTRO_TIPO'] . '_' . $item['NUMERO_REGISTRO'] ?>')" title="Excluir"><i class="fa-solid fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Indicadores Faltantes -->
            <?php if (!empty($numerosFaltantes)): ?>
            <div class="card animate-slide-up">
                <div class="card-header">
                    <h3 class="card-title"><span class="card-title-icon"><i class="fa-solid fa-exclamation-triangle"></i></span> Indicadores Faltantes <span class="badge badge-error" style="margin-left: 12px;"><?= count($numerosFaltantes) ?></span></h3>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table id="faltantesTable" class="table" style="width: 100%;"><thead><tr><th>Matrícula Nº</th></tr></thead>
                            <tbody><?php foreach ($numerosFaltantes as $nf): ?><tr><td><code style="color: #f87171;"><?= $nf ?></code></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
        
        <?php include_once("../rodape-modern.php"); ?>
    </div>
    
    <!-- Modal Exportar -->
    <div id="exportModal" class="modal-backdrop">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header"><h3 class="modal-title">Exportar Carga ONR</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Data Inicial</label><input type="date" id="dataInicial" class="form-control"></div>
                <div class="form-group"><label class="form-label">Data Final</label><input type="date" id="dataFinal" class="form-control"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal()">Cancelar</button><button class="btn btn-primary" id="searchBtn"><i class="fa-solid fa-download"></i> Gerar Arquivo</button></div>
        </div>
    </div>
    
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        const ptBR = { "emptyTable": "Nenhum registro", "info": "Mostrando _START_ a _END_ de _TOTAL_", "infoEmpty": "Mostrando 0", "infoFiltered": "(filtrado de _MAX_)", "lengthMenu": "_MENU_ por página", "search": "Pesquisar:", "zeroRecords": "Nenhum encontrado", "paginate": { "first": "«", "last": "»", "next": "›", "previous": "‹" } };
        $(document).ready(function() {
            <?php if (!empty($files)): ?>$('#matriculasTable').DataTable({ language: ptBR, pageLength: 25, order: [[0, 'desc']] });<?php endif; ?>
            <?php if (!empty($numerosFaltantes)): ?>$('#faltantesTable').DataTable({ language: ptBR, pageLength: 25, order: [[0, 'asc']] });<?php endif; ?>
        });
        
        function confirmDelete(matricula) {
            if (confirm("Tem certeza que deseja excluir este registro?")) {
                fetch('delete.php?matricula=' + matricula).then(() => location.reload());
            }
        }
        
        document.getElementById('openModalBtn').addEventListener('click', () => document.getElementById('exportModal').classList.add('active'));
        function closeModal() { document.getElementById('exportModal').classList.remove('active'); }
        
        document.getElementById('searchBtn').addEventListener('click', function() {
            var dataInicial = document.getElementById('dataInicial').value;
            var dataFinal = document.getElementById('dataFinal').value;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'buscar_arquivos.php?dataInicial=' + dataInicial + '&dataFinal=' + dataFinal, true);
            xhr.responseType = 'blob';
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(xhr.response);
                    link.download = 'indicador-real-' + dataInicial + '-' + dataFinal + '.json';
                    link.click();
                    closeModal();
                }
            };
            xhr.send();
        });
    </script>
</body>
</html>
