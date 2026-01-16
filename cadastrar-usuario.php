<?php
session_start();
// Verifica se o usuário está logado, se não, redireciona para a página de login
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cadastro de Funcionários</title>
    <link rel="icon" href="img/logo.png" type="image/png">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/chart.js"></script>
    <style>
        #form {
            display: flex;
            flex-direction: row-reverse;
        }
        input[type="password"], input[type="text"] {
            padding: 8px;
            margin: 0px 15px 0px 0px;
            border-radius: 5px;
            border: none;
            font-size: 14px;
            color: #333;
        }
  </style>
</head>
<body>
    <div class="header">     
    <form id="form" action="logout.php" method="post">
    <p style="color: #fff;position: absolute;margin-right: 85px; margin-top: 25px;">Usuário logado: <?php echo $_SESSION['usuario']; ?></p>    
        <input type="submit" class="third" value="Sair">
    </form>
              <div class="orb-container">
              <div class="inner-header flex">
          <img src="../img/NOVA_LOGO.png" alt="Logo" class="orb">
        </div>
              </div>
              <h1>DocMark - Cadastro de Funcionários</h1>
            
            <?php include_once("menu.php");?>
        
         <div class="container">
           <h3>Preencha os dados para cadastro</h3><br>
           <form id="cadastroForm" style="display: flex;flex-direction: row;align-items: center;">
           <!-- ID da Empresa -->
             <input type="hidden" id="empresaId" name="empresaId" value="1">
                <label for="usuario" style="margin-bottom: 60px;margin-left: 1px;font-weight: bold;color: #fff;position: absolute;">Usuário:</label><br>
                <input type="text" id="usuario" name="usuario" required><br><br>
                <label for="senha" style="margin-bottom: 60px;margin-left: 220px;font-weight: bold;color: #fff;position: absolute;">Senha:</label><br>
                <input type="password" id="senha" name="senha" required><br><br>
                <button type="submit" class="button">Cadastrar</button>
            </form>
         </div>
           
                <script>
                  document.getElementById('cadastroForm').addEventListener('submit', function(event) {
                    event.preventDefault();

                    const empresaId = document.getElementById('empresaId').value;
                    const usuario = document.getElementById('usuario').value;
                    const senha = document.getElementById('senha').value;

                    const novoUsuario = {
                      id: Date.now(),
                      empresa_id: parseInt(empresaId),
                      usuario: usuario,
                      senha: senha
                    };

                    // Envia os dados para o arquivo PHP para salvar no JSON
                    fetch('salvar.php', {
                      method: 'POST',
                      headers: {
                        'Content-Type': 'application/json'
                      },
                      body: JSON.stringify(novoUsuario)
                    })
                    .then(response => {
                      if (response.ok) {
                        alert('Usuário cadastrado com sucesso!');
                      } else {
                        alert('Erro ao cadastrar usuário. Por favor, tente novamente.');
                      }
                    })
                    .catch(error => console.error('Erro ao salvar dados:', error));
                  });
                </script>
      </div>
  <?php include_once("rodape.php");?>
    </body>
</html>
