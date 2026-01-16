<?php
shell_exec('sincronizar.bat');
$output = file_get_contents('temp.txt');
echo $output;
?>
<?php
shell_exec('sincronizar-indicador.bat');
$output2 = file_get_contents('temp-indicador.txt');
echo $output2;
?>
