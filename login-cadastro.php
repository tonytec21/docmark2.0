<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DocMark - Cadastrar Funcionário">
    <title>DocMark - Cadastrar Funcionário</title>
    
    <!-- Favicon -->
    <link rel="icon" href="img/NOVA_LOGO.png" type="image/png">
    
    <!-- Styles -->
    <link rel="stylesheet" href="css/login-modern.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <!-- Logo -->
        <div class="login-logo">
            <img src="img/NOVA_LOGO.png" alt="DocMark Logo">
            <h1>DocMark</h1>
            <p>Cadastro de Funcionário</p>
        </div>
        
        <!-- Register Card -->
        <div class="login-card">
            <h2>Novo Funcionário</h2>
            
            <form action="cadastrar_usuario.php" method="post">
                <div class="form-group">
                    <label for="empresa_id" class="form-label">Selecione o cartório</label>
                    <select name="empresa_id" id="empresa_id" class="form-control form-select" required>
                        <option value="1">Registro de Imóveis</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" name="usuario" id="usuario" class="form-control" 
                           placeholder="Digite o nome de usuário" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" name="senha" id="senha" class="form-control" 
                           placeholder="Digite a senha" required autocomplete="new-password">
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-user-plus"></i>
                    Cadastrar
                </button>
            </form>
            
            <div class="login-footer">
                <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/login.php' ?>">
                    <i class="fa-solid fa-arrow-left"></i>
                    Voltar para o login
                </a>
            </div>
        </div>
    </div>
    
    <!-- Copyright -->
    <div class="copyright">
        &copy; <span id="year"></span> DocMark | By <a href="https://backupcloud.site/" target="_blank">Backup Cloud</a>
    </div>

    <script>
        document.getElementById("year").textContent = new Date().getFullYear();
    </script>
</body>
</html>
