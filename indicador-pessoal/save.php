<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricula'])) {
    $matricula = $_POST['matricula'];
    $data = [];

    $data['matricula'] = $matricula;

    $names = $_POST['nome'];
    $cpfs = $_POST['cpf'];
    $tipos_ato = $_POST['tipo_ato'];
    $datas_avr = $_POST['data_avr'];
    $datas_venda = $_POST['data_venda'];

    $data['entries'] = [];

    for ($i = 0; $i < count($names); $i++) {
        $data['entries'][] = [
            'nome' => $names[$i],
            'cpf' => $cpfs[$i],
            'tipo_ato' => $tipos_ato[$i],
            'data_avr' => $datas_avr[$i],
            'data_venda' => $datas_venda[$i]
        ];
    }

    $json_data = json_encode($data, JSON_PRETTY_PRINT);

    // Verificar e criar o diretório "matriculas" se ele não existir
    if (!is_dir('matriculas')) {
        mkdir('matriculas', 0777, true);
    }

    $filename = 'matriculas/' . $matricula . '.json';
    file_put_contents($filename, $json_data);
}
?>

<html>
<head>
    <title>Salvar Matrícula</title>
    <script>
        function showPopup() {
            alert("Dados salvos com sucesso!");
            window.location.href = "matriculas.php";
        }
    </script>
</head>
<body onload="showPopup()">
</body>
</html>
