<?php
// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Dados enviados pelo formulário
    $empresa_id = $_POST["empresa_id"];
    $usuario = $_POST["usuario"];
    $senha = $_POST["senha"];
    // Criptografar a senha antes de armazenar no banco de dados
    $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);
    // Conexão com o banco de dados (substitua as credenciais de acordo com o seu ambiente)
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "docmark";
    // Criar conexão
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Verificar se a conexão foi bem-sucedida
    if ($conn->connect_error) {
        die("Erro na conexão com o banco de dados: " . $conn->connect_error);
    }
    // Consulta para inserir o usuário na tabela "usuarios"
    $query = "INSERT INTO usuarios (empresa_id, usuario, senha) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $empresa_id, $usuario, $senha_hashed);
    $stmt->execute();
    // Fechar a consulta e a conexão
    $stmt->close();
    $conn->close();
    // Redirecionar para a página de login após o cadastro
    header("Location: login.php");
    exit();
} else {
    // Se o método de requisição não for POST, redirecionar para a página de registro
    header("Location: registro.php");
    exit();
}
