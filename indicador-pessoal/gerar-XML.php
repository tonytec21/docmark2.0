<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar XML</title>
</head>
<body>
    <form action="processXML.php" method="post">
        <label>Data Inicial:</label>
        <input type="date" name="start_date" required>
        <br>

        <label>Data Final:</label>
        <input type="date" name="end_date" required>
        <br>

        <input type="submit" value="Gerar XML">
    </form>
</body>
</html>
