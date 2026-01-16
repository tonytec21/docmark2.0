<?php
shell_exec('sincronizar.bat');
$output = file_get_contents('temp.txt');
echo $output;
?>
