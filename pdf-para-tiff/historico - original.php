<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

error_reporting(0);
ini_set('display_errors', 0);

$pastaHistorico = __DIR__ . '/historico';
$arquivos = glob($pastaHistorico . '/*');

if (!empty($arquivos)) { // Verifica se a pasta contém arquivos
    setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');

    // Obtém os números dos nomes dos arquivos
    $numerosArquivos = array();
    foreach ($arquivos as $arquivo) {
        $numeroArquivo = (int) str_replace('.tiff', '', basename($arquivo));
        $numerosArquivos[] = $numeroArquivo;
    }

    // Obtém o intervalo de números
    $minimo = 1;
    $maximo = max($numerosArquivos);

    // Obtém os números faltantes
    $numerosFaltantes = array();
    for ($i = $minimo; $i <= $maximo; $i++) {
        if (!in_array($i, $numerosArquivos)) {
            $numerosFaltantes[] = str_pad($i, 8, '0', STR_PAD_LEFT);
        }
    }
}

if(isset($_FILES['xml_file'])) {
    $target_dir = "indicador-pessoal/";
    $history_dir = "historico-indicador/";

    // Apagar todos os arquivos dentro do diretório "indicador-pessoal"
    $files = glob($target_dir . "*");
    foreach($files as $file){
        if(is_file($file))
            unlink($file);
    }

    $target_file = $target_dir . basename($_FILES['xml_file']['name']);
    $history_file = $history_dir . basename($_FILES['xml_file']['name']); // O caminho para a cópia no diretório "historico-indicador"
    $file_type = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Verificar se o arquivo é um XML
    if($file_type == "xml") {
        if(move_uploaded_file($_FILES['xml_file']['tmp_name'], $target_file)) {
            echo '<script>alert("Arquivo anexado com sucesso");</script>';

            // Copiar o arquivo para "historico-indicador"
            if(copy($target_file, $history_file)) {
                echo '<script>alert("Arquivo disponível para visualização em indicador pessoal");</script>';
            } else {
                echo '<script>alert("Ocorreu um erro ao copiar o arquivo");</script>';
            }
        } else {
            echo '<script>alert("Ocorreu um erro ao anexar o arquivo em indicador-pessoal");</script>';
        }
    } else {
        echo '<script>alert("Por favor, selecione um arquivo XML");</script>';
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>DocMark - Controle de Conversões</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/chart.js"></script>
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="../js/pop-up.js"></script>
    <link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css">
    <script src="js/jquery.min.js"></script>
    <script src="js/chart.js"></script>
    <script src="js/chartjs-plugin-datalabels.js"></script>
    <script type="text/javascript" charset="utf8" src="js/jquery-3.5.1.js"></script>
    <script type="text/javascript" charset="utf8" src="js/jquery.dataTables.js"></script>

<style>
    form {
        display: flex;
        flex-direction: column;
        flex-wrap: wrap;
        align-items: center;
    }
    #sincronizar2{
        margin-left: 60%;
        margin-top: -7%;
    }
    
    </style>
</head>
<body>

    <div class="orb-container">
            <div class="orb"></div>
    </div>
            <h1>DocMark - Controle de Conversões</h1>
            <?php include_once("../menu.php");?>
            <!-- SINAL PÚBLICO E INDICADOR PESSOAL -->
            <div class="container">
            <div id="sincronizar">
                <form style="border-right: 1px solid #ccc;padding-right: 30px;" action="../chancela/upload.php" method="post" enctype="multipart/form-data" onsubmit="return onSubmitForm();">
                <h3>ADICIONAR SINAL PÚBLICO</h3>
                <div>Selecione os arquivos PDF para adicionar o sinal público</div>    
                    <input type="file" name="pdf[]" id="pdf" multiple>
                    <input type="submit" value="Processar" name="submit" class="button">
                </form>

                <form action="" method="post" enctype="multipart/form-data">
                <h3>ANEXAR INDICADOR PESSOAL</h3>
                <div>Selecione um arquivo XML do indicador pessoal para anexar: </div>
                    <input type="file" name="xml_file" accept=".xml">
                    <input type="submit" value="Anexar Arquivo">
                </form>
            </div>
            <br>

            <div id="sincronizar" style="border-top: 1px solid #ccc;padding-top: 30px;">
                        <button class="btn2 first" id="sincronizar-button">Sincronizar com NexCloud</button>                        
                        <button class="btn2 first" id="visualizar-indicador">Visualizar Indicador Pessoal</button>
            </div>
            <br>
            <h3>Histórico de Matrículas Convertidas</h3>

                <div id="popup" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background-color:rgba(0,0,0,0.5); text-align:center;">
                    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background-color:white; padding:20px; border-radius:5px;">
                        <p>Executando a tarefa, por favor aguarde...</p>
                    </div>
                </div>

        <script>
            function showSuccessPopup(message) {
                    const successHtml = `
                        <div id="success-modal" style="
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background-color: rgba(0, 0, 0, 0.5);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            z-index: 9999;
                        ">
                            <div style="
                                padding: 20px;
                                background-color: #f1f1f1;
                                border: 1px solid #ccc;
                                border-radius: 4px;
                            ">
                                <p>${message}</p>
                                <button id="close-success-btn" style="
                                    display: block;
                                    margin: 10px auto;
                                    padding: 5px 10px;
                                ">Fechar</button>
                            </div>
                        </div>
                    `;
                    $('body').append(successHtml);

                    $('#close-success-btn').on('click', function() {
                        hideSuccessPopup();
                    });
                }

                function hideSuccessPopup() {
                    $('#success-modal').remove();
                }

                document.getElementById('sincronizar-button').addEventListener('click', function () {
                    showProcessingPopup(); // Mostrar pop-up de processamento
                    fetch('execute_sync.php')
                        .then(response => response.text())
                        .then(output => {
                            hideProcessingPopup(); // Esconder pop-up de processamento
                            showSuccessPopup('Comando executado com sucesso!<br> ' + output + ' matrículas foram sincronizadas e 1 arquivo XML do indicador pessoal'); // Mostrar pop-up de sucesso
                        })
                        .catch(error => {
                            hideProcessingPopup(); // Esconder pop-up de processamento em caso de erro
                        });
                });


            function showSuccessPopup(message) {
                    const successHtml = `
                        <div id="success-modal" style="
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background-color: rgba(0, 0, 0, 0.5);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            z-index: 9999;
                        ">
                            <div style="
                                padding: 20px;
                                background-color: #f1f1f1;
                                border: 1px solid #ccc;
                                border-radius: 4px;
                            ">
                                <p>${message}</p>
                                <button id="close-success-btn" style="
                                    display: block;
                                    margin: 10px auto;
                                    padding: 5px 10px;
                                ">Fechar</button>
                            </div>
                        </div>
                    `;
                    $('body').append(successHtml);

                    $('#close-success-btn').on('click', function() {
                        hideSuccessPopup();
                    });
                }

                function hideSuccessPopup() {
                    $('#success-modal').remove();
                }

                document.getElementById('visualizar-button').addEventListener('click', function () {
                    showProcessingPopup(); // Mostrar pop-up de processamento
                    fetch('atualizar-visualizacao.php')
                        .then(response => response.text())
                        .then(output => {
                            hideProcessingPopup(); // Esconder pop-up de processamento
                            showSuccessPopup('Visualização das Matrículas atualizada com sucesso!'); // Mostrar pop-up de sucesso
                        })
                        .catch(error => {
                            hideProcessingPopup(); // Esconder pop-up de processamento em caso de erro
                        });
                });

                document.getElementById('visualizar-indicador').addEventListener('click', function () {
                    showProcessingPopup(); // Mostrar pop-up de processamento
                    
                    fetch('../indicador-pessoal/indicador-pessoal.php')
                        .then(response => response.text())
                        .then(output => {
                            hideProcessingPopup(); // Esconder pop-up de processamento
                            
                            // Abre a página em uma nova guia
                            window.open('../indicador-pessoal/indicador-pessoal.php', '_blank');
                        })
                        .catch(error => {
                            hideProcessingPopup(); // Esconder pop-up de processamento em caso de erro
                        });
                });

        </script>

        <!-- HISTÓRICO DE MATRÍCULAS CONVERTIDAS -->
                    <table id="tabela-historico" class="display">
                    <thead>
                        <tr>
                            <th>Matrícula Nº</th>
                            <th>Data da última conversão</th>
                            <th>Horário</th>
                            <th>Download TIFF</th>
                            <th>Visualizar</th>
                            <th>Excluir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($arquivos as $arquivo): ?>
                            <tr>
                                <td><?php echo str_replace('.tiff', '', basename($arquivo)); ?></td>
                                <td><?php echo date('Y-m-d', filemtime($arquivo)); ?></td>
                                <td><?php echo strftime('%H:%M:%S', filemtime($arquivo)); ?></td>
                                <td><a class="btn first" style="text-align: center!important;" href="historico/<?php echo basename($arquivo); ?>" download>Download</a></td>
                                <td><a class="btn first" href="pdf-viw/<?php echo str_replace('.tiff', '.pdf', basename($arquivo)); ?>" target="_blank">Visualizar</a></td>
                                <td><a class="btn2-gradient delete-link" style="background: rgb(255 99 132 / 53%)" href="delete.php?file=<?php echo urlencode(basename($arquivo)); ?>"><i class="fa fa-trash-o fa-1x" style="color: #fff" aria-hidden="true"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <script>
                    $(document).ready(function() {
                        $('.delete-link').on('click', function(e) {
                            e.preventDefault();
                            let href = $(this).attr('href');
                            let confirmBox = confirm('Tem certeza de que deseja excluir esta matrícula?');
                            if (confirmBox) {
                                window.location.href = href;
                            }
                        });
                    });
                </script>

                <script>
                $(document).ready(function() {
                    $('#tabela-historico').DataTable({
                        "order": [[ 0, "asc" ]]
                    });
                });
                </script>

    </div>

<!-- RELATÓRIO DE CONVERSÃO -->
<div class="container">
    <h3 style="margin: 0px 0;">Relatório de Conversão</h3>

    <div id="chart-container">
        <canvas id="grafico"></canvas>
    </div>

    <script>
        $(document).ready(function () {
            $.ajax({
                url: 'data.php',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    var ctx = document.getElementById('grafico').getContext('2d');
                    var chart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Matrículas Convertidas', 'Matrículas Faltantes'],
                            datasets: [{
                                data: [data.convertidos, data.faltantes],
                                backgroundColor: ['rgb(75 192 192 / 69%)', 'rgb(255 99 132 / 53%)'],
                                borderColor: ['rgba(75, 192, 192, 1)', 'rgba(255, 99, 132, 1)'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            var label = context.label || '';
                                            var value = context.parsed || 0;
                                            return label + ': ' + ((value / context.dataset.data.reduce((a, b) => a + b, 0)) * 100).toFixed(2) + '%';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
        });
    </script>
</div>

    <!-- MATRICULAS FALTANTES -->
    <div class="container">
        <h3 style="margin: 0px 0;">Matrículas Faltantes - <?php echo count($numerosFaltantes); ?></h3>
            <h3 style="margin: 0px 0;">Intervalo verificado: <?php echo $minimo . ' - ' . $maximo; ?></h3>

            <table id="tabela-historico2" class="display">
            <thead>
                <tr>
                    <th>Matrícula Nº</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($numerosFaltantes as $numeroFaltante): ?>
                    <tr>
                        <td><?php echo $numeroFaltante; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
        $(document).ready(function() {
            $('#tabela-historico2').DataTable({
                "order": [[ 0, "asc" ]]
            });
        });
        </script>
</div>


<?php include_once("../rodape.php");?>

</body>
</html>