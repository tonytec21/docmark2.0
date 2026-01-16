<?php
// Recebe os dados enviados via POST
$data = json_decode(file_get_contents('php://input'), true);

// Lê os dados existentes do arquivo JSON
$jsonData = json_decode(file_get_contents('data.json'), true);

// Adiciona o novo usuário aos dados existentes
$jsonData['usuarios'][] = $data;

// Escreve os dados de volta no arquivo JSON
file_put_contents('data.json', json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Retorna uma resposta HTTP 200 (OK) se tudo correr bem
http_response_code(200);
?>
