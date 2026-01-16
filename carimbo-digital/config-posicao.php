<!DOCTYPE html>
<html>
<head>
    <title>Configuração do Carimbo Digital</title>
    <style>
        .container {
            width: 300px;
            margin: 0 auto;
            padding: 20px;
        }
        .input-field {
            margin-bottom: 10px;
        }
        .input-field label {
            display: block;
            margin-bottom: 5px;
        }
        .input-field input {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Configuração do Carimbo Digital</h1>
        <form action="config.php" method="post">
            <div class="input-field">
                <label for="x">Posição X:</label>
                <input type="text" id="x" name="x">
            </div>
            <div class="input-field">
                <label for="y">Posição Y:</label>
                <input type="text" id="y" name="y">
            </div>
            <input type="submit" value="Salvar">
        </form>
    </div>
</body>
</html>
