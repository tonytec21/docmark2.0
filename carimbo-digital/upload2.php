<?php
$target_dir = "../chancela/config/";
$target_file = $target_dir . 'chancela-2.png';
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST["submit2"])) {
        $check = getimagesize($_FILES["image2"]["tmp_name"]);
        if($check !== false && $imageFileType == "png") {
            if (move_uploaded_file($_FILES["image2"]["tmp_name"], $target_file)) {
                echo "O arquivo ". basename( $_FILES["image2"]["name"]). " foi carregado.";
            } else {
                echo "Desculpe, houve um erro ao carregar seu arquivo.";
            }
        } else {
            echo "Arquivo não é uma imagem PNG.";
        }
    }
}
?>
