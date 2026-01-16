<!DOCTYPE html>
<html>
<head>
    <title>Histórico de Conversões</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container" style="margin-top: -38px;margin-bottom: 14%;">
    <h1>Histórico de Conversões</h1>
    <ul>
        <?php
            $conversoesPorDia = [];

            if (empty($arquivosZIP)) : ?>
                <li>Nenhuma conversão encontrada no histórico.</li>
        <?php else : ?>
            <?php foreach ($arquivosZIP as $arquivoZIP) :
                $dataHora = getDataHoraArquivoZIP($arquivoZIP);
                $data = $dataHora->format('Y-m-d');

                if (!isset($conversoesPorDia[$data])) {
                    $conversoesPorDia[$data] = 0;
                }

                $conversoesPorDia[$data]++;
            ?>
                <?php
                    $meses = [
                        1 => 'janeiro',
                        2 => 'fevereiro',
                        3 => 'março',
                        4 => 'abril',
                        5 => 'maio',
                        6 => 'junho',
                        7 => 'julho',
                        8 => 'agosto',
                        9 => 'setembro',
                        10 => 'outubro',
                        11 => 'novembro',
                        12 => 'dezembro'
                    ];

                    $dia = $dataHora->format('d');
                    $mes = $meses[(int)$dataHora->format('m')];
                    $ano = $dataHora->format('Y');
                    $hora = $dataHora->format('H:i:s');

                    $dataHoraFormatada = $dia . ' de ' . $mes . ' de ' . $ano . ', às ' . $hora;
                ?>
                <li>
                    <span>Conversão realizada em <?= $dataHoraFormatada ?></span>
                    <a href="<?= 'http://' . $_SERVER['HTTP_HOST'] . '/docmark/pdf-para-tiff/arquivos/' . basename($arquivoZIP) ?>" class="btn-gradient" style="padding:5px 25px!important;">Download</a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

<div>
    <canvas id="conversoesChart"></canvas>
</div>

<script>
    var ctx = document.getElementById('conversoesChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($conversoesPorDia)); ?>,
            datasets: [{
                label: 'Conversões por dia',
                data: <?php echo json_encode(array_values($conversoesPorDia)); ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.5)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Conversões por dia'
                }
            }
        }
    });
</script>

</body>
</html>
