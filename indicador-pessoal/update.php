<?php

$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricula'])) {
    $matricula = $_POST['matricula'];  
    $data['matricula'] = $matricula;

    $names = $_POST['nome'];
    $cpfs = $_POST['cpf'];
    $tipos_ato = $_POST['tipo_ato'];
    $datas_avr = $_POST['data_avr'];
    $datas_venda = $_POST['data_venda'];

    for ($i = 0; $i < count($names); $i++) {
        if(trim($names[$i]) === "") continue; 

        $data['entries'][] = [
            'nome' => $names[$i],
            'cpf' => $cpfs[$i],
            'tipo_ato' => $tipos_ato[$i],
            'data_avr' => $datas_avr[$i],
            'data_venda' => $datas_venda[$i]
        ];
    }

    $json_data = json_encode($data, JSON_PRETTY_PRINT);

    $filename = 'matriculas/' . $matricula . '.json';
    file_put_contents($filename, $json_data);

    echo "<script>
            alert('Dados atualizados com sucesso! Clique OK para voltar para a lista.');
            window.location.href = 'matriculas.php';
          </script>";
}
?>
