<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DocMark - Login">
    <title>DocMark - Login</title>
    
    <!-- Favicon -->
    <link rel="icon" href="img/NOVA_LOGO.png" type="image/png">
    
    <!-- Styles -->
    <link rel="stylesheet" href="css/login-modern.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php
    // Verifica se há uma mensagem de erro
    if (isset($_GET['error']) && $_GET['error'] == 1) {
        echo '<div class="error-popup" id="errorPopup">';
        echo '<div class="error-popup-content">';
        echo '<div class="error-popup-icon"><i class="fa-solid fa-circle-exclamation"></i></div>';
        echo '<p class="error-message">Credenciais inválidas. Por favor, tente novamente.</p>';
        echo '<button class="btn-close" onclick="closeErrorPopup()">Entendi</button>';
        echo '</div>';
        echo '</div>';
    }
    ?>

    <div class="login-container">
        <!-- Logo -->
        <div class="login-logo">
            <img src="img/NOVA_LOGO.png" alt="DocMark Logo">
            <h1>DocMark</h1>
        </div>
        
        <!-- Login Card -->
        <div class="login-card">
            <h2>Acesso Restrito</h2>
            
            <form action="login_process.php" method="post">
                <div class="form-group" hidden>
                    <label for="empresa_id" class="form-label">Selecione seu cartório</label>
                    <select name="empresa_id" id="empresa_id" class="form-control form-select" required>
                        <option value="1">Registro de Imóveis</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" name="usuario" id="usuario" class="form-control" 
                           placeholder="Digite seu usuário" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" name="senha" id="senha" class="form-control" 
                           placeholder="Digite sua senha" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Entrar
                </button>
            </form>
            
            <!-- <div class="login-footer">
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/login-cadastro.php' ?>">
                    <i class="fa-solid fa-user-plus"></i>
                    Cadastrar novo funcionário
                </a>
            </div> -->
        </div>
    </div>
    
    <!-- Copyright -->
    <div class="copyright">
        &copy; <span id="year"></span> DocMark | By <a href="https://backupcloud.site/" target="_blank">TCloud</a>
    </div>

    <script>
        // Set current year
        document.getElementById("year").textContent = new Date().getFullYear();
        
        // Close error popup
        function closeErrorPopup() {
            var errorPopup = document.getElementById('errorPopup');
            if (errorPopup) {
                errorPopup.style.display = 'none';
            }
        }
        
        // Show error popup on load if exists
        window.onload = function() {
            var errorPopup = document.getElementById('errorPopup');
            if (errorPopup) {
                errorPopup.style.display = 'flex';
            }
        };
    </script>
</body>
</html>
