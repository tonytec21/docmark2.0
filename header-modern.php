<?php
// Header component for DocMark Modern UI
?>
<!-- Google Fonts & Font Awesome -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Source+Sans+Pro:wght@300;400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<header class="header">
    <div class="header-inner">
        <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/index.php' ?>" class="logo-section">
            <img src="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/img/NOVA_LOGO.png' ?>" alt="DocMark Logo" class="logo">
            <span class="logo-text">DocMark</span>
        </a>
        
        <div class="header-actions">
            <form action="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/logout.php' ?>" method="post" style="margin: 0;">
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Sair</span>
                </button>
            </form>
        </div>
    </div>
</header>
