<?php
// Inclua a função verificar_sessao_ativa()
require_once 'funcoes.php';
// Verifique se a sessão está ativa
verificar_sessao_ativa();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DocMark - Sistema de Gestão de Matriculas de Imóveis">
    <title>DocMark - Painel Principal</title>
    
    <!-- Favicon -->
    <link rel="icon" href="img/NOVA_LOGO.png" type="image/png">
    
    <!-- Styles -->
    <link rel="stylesheet" href="css/docmark-modern.css">
</head>
<body>
    <div class="page-wrapper">
        <!-- Header -->
        <?php include_once("header-modern.php"); ?>
        
        <!-- Navigation -->
        <?php include_once("menu-modern.php"); ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header animate-fade-in">
                <h1 class="page-title">Bem-vindo ao DocMark</h1>
                <p class="page-subtitle">Sistema completo de gestão e processamento de Matriculas de Imóveis</p>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-grid stagger-children">
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/carimbo-digital/index.php' ?>" class="dashboard-card animate-slide-up">
                    <i class="fa-solid fa-stamp dashboard-card-icon"></i>
                    <span class="dashboard-card-title">Carimbo Digital</span>
                </a>
                
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/pdf-para-tiff/index.php' ?>" class="dashboard-card animate-slide-up">
                    <i class="fa-solid fa-file-image dashboard-card-icon"></i>
                    <span class="dashboard-card-title">Converter PDF para TIFF</span>
                </a>
                
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/pdf-para-tiff/historico.php' ?>" class="dashboard-card animate-slide-up">
                    <i class="fa-solid fa-list-check dashboard-card-icon"></i>
                    <span class="dashboard-card-title">Controle de Conversões</span>
                </a>
                
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/indicador-pessoal/index.php' ?>" class="dashboard-card animate-slide-up">
                    <i class="fa-solid fa-clock-rotate-left dashboard-card-icon"></i>
                    <span class="dashboard-card-title">Logs do Indicador Pessoal</span>
                </a>
                
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/chancela/index.php' ?>" class="dashboard-card animate-slide-up">
                    <i class="fa-solid fa-signature dashboard-card-icon"></i>
                    <span class="dashboard-card-title">Adicionar Sinal Público</span>
                </a>
                
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/indicador-pessoal/matriculas.php' ?>" class="dashboard-card animate-slide-up">
                    <i class="fa-solid fa-user-plus dashboard-card-icon"></i>
                    <span class="dashboard-card-title">Cadastrar Indicador Pessoal</span>
                </a>
                
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/indicador-real/index.php' ?>" class="dashboard-card animate-slide-up">
                    <i class="fa-solid fa-building-columns dashboard-card-icon"></i>
                    <span class="dashboard-card-title">Cadastrar Indicador Real</span>
                </a>
                
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/pdf-para-tiff/historico-faltante.php' ?>" class="dashboard-card animate-slide-up">
                    <i class="fa-solid fa-chart-bar dashboard-card-icon"></i>
                    <span class="dashboard-card-title">Relatórios</span>
                </a>
            </div>
        </main>
        
        <!-- Footer -->
        <?php include_once("rodape-modern.php"); ?>
    </div>
    
    <!-- Scripts -->
    <script src="js/jquery-3.6.0.min.js"></script>
</body>
</html>
