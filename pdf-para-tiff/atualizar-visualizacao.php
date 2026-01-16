<?php
shell_exec('tiff-para-pdf.bat');
$output = file_get_contents('temp.txt');
echo $output;
?>
