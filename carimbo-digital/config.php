<?php
if (isset($_POST['x']) && isset($_POST['y'])) {
    $config = ['x' => $_POST['x'], 'y' => $_POST['y']];
    file_put_contents('posicao.json', json_encode($config)); // modificamos o nome do arquivo aqui
    header('Location: success.php');
} else {
    header('Location: error.php');
}
