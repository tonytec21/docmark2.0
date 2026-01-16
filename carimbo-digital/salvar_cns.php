<?php
// Verificar se o corpo da requisição contém dados JSON
$data = json_decode(file_get_contents('php://input'), true);

// Verificar se o CNS foi enviado
if (isset($data['cns'])) {
    $cns = $data['cns'];

    // Função para salvar o CNS em um arquivo JSON
    function salvarCNS($cns) {
        $dados = array(
            'cns' => $cns
        );

        $json = json_encode($dados);

        file_put_contents('cns.json', $json);
    }

    salvarCNS($cns);

    // Retornar uma resposta de sucesso
    $response = array(
        'success' => true
    );
} else {
    // Retornar uma resposta de erro
    $response = array(
        'success' => false,
        'message' => 'O CNS não foi fornecido.'
    );
}

// Enviar a resposta como JSON
header('Content-Type: application/json');
echo json_encode($response);
