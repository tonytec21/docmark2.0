<?php
// Determinar a página atual para destacar o item ativo no menu
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];

// Função para verificar se o link está ativo
function isActive($page, $paths = []) {
    global $current_page, $current_path;
    if ($current_page === $page) return true;
    foreach ($paths as $path) {
        if (strpos($current_path, $path) !== false) return true;
    }
    return false;
}
?>
<!-- Font Awesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<nav class="nav-menu">
    <div class="nav-inner">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/index.php' ?>" 
                   class="nav-link <?= isActive('index.php', ['/docmark/index.php']) && !strpos($current_path, 'carimbo') && !strpos($current_path, 'pdf-para-tiff') && !strpos($current_path, 'chancela') && !strpos($current_path, 'indicador') ? 'active' : '' ?>" 
                   title="Página inicial">
                    <i class="fa-solid fa-house nav-icon"></i>
                    <span class="nav-text">Início</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/carimbo-digital/index.php' ?>" 
                   class="nav-link <?= isActive('', ['carimbo-digital']) ? 'active' : '' ?>" 
                   title="Carimbo Digital">
                    <i class="fa-solid fa-stamp nav-icon"></i>
                    <span class="nav-text">Carimbo Digital</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/pdf-para-tiff/index.php' ?>" 
                   class="nav-link <?= isActive('', ['pdf-para-tiff/index']) ? 'active' : '' ?>" 
                   title="Converter PDF para TIFF">
                    <i class="fa-solid fa-file-image nav-icon"></i>
                    <span class="nav-text">PDF → TIFF</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/pdf-para-tiff/historico.php' ?>" 
                   class="nav-link <?= isActive('historico.php', ['pdf-para-tiff/historico']) ? 'active' : '' ?>" 
                   title="Controle de Conversões">
                    <i class="fa-solid fa-list-check nav-icon"></i>
                    <span class="nav-text">Controle</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/pdf-para-tiff/historico-faltante.php' ?>" 
                   class="nav-link <?= isActive('historico-faltante.php') ? 'active' : '' ?>" 
                   title="Relatório de Conversão">
                    <i class="fa-solid fa-chart-bar nav-icon"></i>
                    <span class="nav-text">Relatórios</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/chancela/index.php' ?>" 
                   class="nav-link <?= isActive('', ['chancela']) ? 'active' : '' ?>" 
                   title="Adicionar Sinal Público">
                    <i class="fa-solid fa-signature nav-icon"></i>
                    <span class="nav-text">Sinal Público</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/indicador-pessoal/index.php' ?>" 
                   class="nav-link <?= isActive('', ['indicador-pessoal/index']) ? 'active' : '' ?>" 
                   title="Logs do Indicador Pessoal">
                    <i class="fa-solid fa-clock-rotate-left nav-icon"></i>
                    <span class="nav-text">Logs Pessoal</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
