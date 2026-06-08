<?php
require_once 'funcoes.php';
verificar_sessao_ativa();

require_once '../db_connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

// Incluir TCPDF
require_once '../carimbo-digital/tcpdf/tcpdf.php';

// Classe personalizada para adicionar timbrado e paginação
class PDF_Certidao extends TCPDF {
    protected $usarTimbrado = false;
    protected $timbradoPath = '';
    protected $mostrarPaginacao = false;
    protected $paginaInicial = 1;
    protected $totalPaginas = 1;
    
    public function setTimbrado($path, $usar = true) {
        $this->timbradoPath = $path;
        $this->usarTimbrado = $usar;
    }
    
    public function setPaginacao($mostrar = true, $paginaInicial = 1, $totalPaginas = 1) {
        $this->mostrarPaginacao = $mostrar;
        $this->paginaInicial = $paginaInicial;
        $this->totalPaginas = $totalPaginas;
    }
    
    public function Header() {
        if ($this->usarTimbrado && !empty($this->timbradoPath) && file_exists($this->timbradoPath)) {
            // Salvar configurações atuais
            $this->SetAutoPageBreak(false, 0);
            
            // Adicionar imagem de timbrado ocupando toda a página
            $this->Image($this->timbradoPath, 0, 0, 210, 297, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
            
            // Restaurar AutoPageBreak
            $this->SetAutoPageBreak(true, 25);
        }
    }
    
    public function Footer() {
        if ($this->mostrarPaginacao) {
            // Posição a 15mm do final
            $this->SetY(-13);
            // Fonte preta
            $this->SetFont('helvetica', '', 9);
            $this->SetTextColor(0, 0, 0); // Cor preta
            
            // Página atual é a página do PDF (getPage) que já inclui as páginas de imagem
            $paginaAtual = $this->getPage();
            
            // Número da página alinhado à direita com margem de 20mm (2cm)
            $this->SetX(-30); // 2cm da margem direita
            $this->Cell(0, 10, 'Página ' . $paginaAtual . ' de ' . $this->totalPaginas, 0, false, 'L', 0, '', 0, false, 'T', 'M');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: historico.php');
    exit;
}

$certidaoPath = $_POST['certidao_path'] ?? '';
$matricula = $_POST['matricula'] ?? '';
$codigoCertidao = $_POST['codigo'] ?? '';
$imagensSelecionadas = $_POST['imagens_selecionadas'] ?? [];
$imagensOrdem = $_POST['imagens_ordem'] ?? '';
$tipoCertidao = $_POST['tipo_certidao'] ?? 'eletronica'; // 'eletronica' ou 'fisica'

if (empty($certidaoPath) || empty($matricula) || empty($imagensSelecionadas)) {
    echo '<script>alert("Dados inválidos!"); window.location.href = "historico.php";</script>';
    exit;
}

$certidaoDir = __DIR__ . '/certidao/' . $certidaoPath;

if (!is_dir($certidaoDir)) {
    echo '<script>alert("Diretório da certidão não encontrado!"); window.location.href = "historico.php";</script>';
    exit;
}

// Ordenar imagens conforme a ordem definida pelo usuário
if (!empty($imagensOrdem)) {
    $ordemArray = explode(',', $imagensOrdem);
    $imagensOrdenadas = [];
    foreach ($ordemArray as $img) {
        if (in_array($img, $imagensSelecionadas)) {
            $imagensOrdenadas[] = $img;
        }
    }
    foreach ($imagensSelecionadas as $img) {
        if (!in_array($img, $imagensOrdenadas)) {
            $imagensOrdenadas[] = $img;
        }
    }
    $imagensSelecionadas = $imagensOrdenadas;
}

// Número da matrícula para exibição
$matriculaNumero = ltrim($matricula, '0');
if (empty($matriculaNumero)) $matriculaNumero = '0';

// Carregar selos da certidão (precisamos antes para calcular páginas)
$selosFile = $certidaoDir . '/selos.json';
$selos = [];
if (file_exists($selosFile)) {
    $selos = json_decode(file_get_contents($selosFile), true) ?? [];
}

// Calcular quantidade total de páginas/folhas
// Páginas de imagem + 1 página de texto/certificação + páginas extras de selo (estimativa)
$qtdPaginasImagem = count($imagensSelecionadas);
$qtdPaginasTextoSelo = 1; // Pelo menos 1 página de texto

// Estimar páginas extras de selo (cada selo ocupa aproximadamente 40mm, página útil ~220mm)
if (!empty($selos)) {
    $alturaUtilPagina = 220; // mm disponíveis para conteúdo
    $alturaTextoInicial = 80; // mm usados pelo texto da certidão
    $alturaPorSelo = 45; // mm por selo aproximadamente
    
    $espacoRestante = $alturaUtilPagina - $alturaTextoInicial;
    $selosNaPrimeiraPagina = floor($espacoRestante / $alturaPorSelo);
    $selosRestantes = count($selos) - $selosNaPrimeiraPagina;
    
    if ($selosRestantes > 0) {
        $selosPorPagina = floor($alturaUtilPagina / $alturaPorSelo);
        $paginasExtras = ceil($selosRestantes / $selosPorPagina);
        $qtdPaginasTextoSelo += $paginasExtras;
    }
}

$qtdFolhas = $qtdPaginasImagem + $qtdPaginasTextoSelo;
$folhaInicial = '01';
$folhaFinal = str_pad($qtdFolhas, 2, '0', STR_PAD_LEFT);

// Data atual formatada
$meses = [
    1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
    5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
    9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
];
$dataAtual = date('d') . ' de ' . $meses[(int)date('m')] . ' de ' . date('Y');

// Carregar informações da certidão
$certidaoInfoFile = $certidaoDir . '/certidao_info.json';
$certidaoInfo = [];
if (file_exists($certidaoInfoFile)) {
    $certidaoInfo = json_decode(file_get_contents($certidaoInfoFile), true);
}

// Selos já foram carregados acima para cálculo de páginas

// Obter dados do usuário da sessão (escrevente)
$nomeEscrevente  = trim((string)($_SESSION['user']['nome_completo'] ?? ''));
$cargoEscrevente = trim((string)($_SESSION['user']['cargo'] ?? ''));

// Garantir dados do usuário logado
if ($nomeEscrevente === '' || $cargoEscrevente === '') {
    session_destroy();
    header('Location: login.php?error=1');
    exit;
}

// Cidade/UF: tenta sessão; se não tiver, busca no banco (cadastro_serventia.cidade)
$cidade = trim((string)($_SESSION['cidade'] ?? ''));
$estado = trim((string)($_SESSION['estado'] ?? ''));

if ($cidade === '' || $estado === '') {
    $empresaId = (int)($_SESSION['user']['empresa_id'] ?? 0);

    if ($empresaId > 0) {
        $stmt = $conn->prepare("
            SELECT cidade
            FROM cadastro_serventia
            WHERE id = 1
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("i", $empresaId);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $cidadeUF = trim((string)($row['cidade'] ?? '')); // Ex: "Zé Doca-MA"

                // Parse "Cidade-UF"
                $partes = array_map('trim', explode('-', $cidadeUF, 2));
                $cidade = $partes[0] ?? '';
                $estado = $partes[1] ?? '';

                // Gravar na sessão para reaproveitar
                $_SESSION['cidade'] = $cidade;
                $_SESSION['estado'] = $estado;
            }

            $stmt->close();
        }
    }

    // Fallback final (se não achou no banco)
    if ($cidade === '') $cidade = 'Santa Inês';
    if ($estado === '') $estado = 'MA';
}

// Nome do arquivo PDF final
$pdfFileName = 'certidao_' . $matricula . '_' . $codigoCertidao . '.pdf';
$pdfFilePath = $certidaoDir . '/' . $pdfFileName;

// ============================================
// GERAR PDF COM TCPDF
// ============================================

// Caminho do timbrado
$timbradoPath = __DIR__ . '/images/timbrado.png';

// Criar nova instância do PDF personalizado
$pdf = new PDF_Certidao('P', 'mm', 'A4', true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('DocMark');
$pdf->SetAuthor('Cartório de Registro de Imóveis');
$pdf->SetTitle('Certidão - Matrícula ' . $matriculaNumero);
$pdf->SetSubject('Certidão de Matrícula');

// Remover cabeçalho e rodapé padrão (controlados pela classe)
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Margens ZERO para as páginas de imagem
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);

// Adicionar cada imagem como uma página (100% sem margens, SEM timbrado)
$pdf->setTimbrado($timbradoPath, false); // Desativar timbrado para páginas de imagem
$pdf->setPaginacao(false); // Sem paginação nas páginas de imagem
foreach ($imagensSelecionadas as $imagem) {
    $imagemPath = $certidaoDir . '/' . $imagem;
    if (file_exists($imagemPath)) {
        $pdf->AddPage();
        
        // Imagem ocupa 100% da página A4 (210x297mm) sem margens
        $pdf->Image($imagemPath, 0, 0, 210, 297, 'JPG', '', '', false, 300, '', false, false, 0, false, false, false);
    }
}

// Calcular total de páginas para paginação
// Páginas de imagem + 1 página de texto + páginas extras de selo (se houver)
$paginaTextoInicial = count($imagensSelecionadas) + 1;
$totalPaginasTextoSelo = 1; // Pelo menos 1 página de texto/selo

// Adicionar página final com texto da certidão (COM timbrado e paginação)
$pdf->setTimbrado($timbradoPath, true); // Ativar timbrado
$pdf->setPrintHeader(true); // Ativar header para usar o timbrado
$pdf->setPrintFooter(true); // Ativar footer para paginação
$pdf->setPaginacao(true, $paginaTextoInicial, $totalPaginasTextoSelo); // Paginação
$pdf->AddPage();

// Configurar margens para a página de texto (ajustadas para o timbrado)
$pdf->SetMargins(25, 30, 25); // Margem superior de 30mm (3cm)
$pdf->SetAutoPageBreak(true, 25);

// Configurar fonte
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(0, 0, 0); // Cor preta
$pdf->SetY(30); // Posição Y a 3cm da margem superior

// Título centralizado e sublinhado
$pdf->Cell(0, 10, 'CERTIDÃO', 0, 1, 'C');
$pdf->Ln(5);

// Texto do corpo - parágrafo único com partes em negrito usando HTML
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(0, 0, 0); // Cor preta

// Construir o texto HTML com formatação
// Diferença entre certidão eletrônica e física: presença ou não da linha "_________"
if ($tipoCertidao === 'fisica') {
    // Certidão física: com linha de assinatura
    $assinatura = 'Eu, _________, ' . $nomeEscrevente . ', ' . $cargoEscrevente . ', dou fé e assino.';
} else {
    // Certidão eletrônica: sem linha de assinatura
    $assinatura = 'Eu, ' . $nomeEscrevente . ', ' . $cargoEscrevente . ', dou fé e assino.';
}

$textoHTML = '<p style="text-align: justify; text-indent: 50px; line-height: 1.2;">';
$textoHTML .= 'Certifico e dou fé, que as folhas numeradas de nº ' . $folhaInicial . ' a ' . $folhaFinal . ', ';
$textoHTML .= 'extraídas nos termos do Art. 19, § 1º da Lei 6.015/73, é reprodução fiel da Matrícula nº ' . $matriculaNumero . ', ';
$textoHTML .= 'Livro 2-Registro Geral, desta Serventia. ';
$textoHTML .= 'Adverte-se que a presente certidão é válida por <b><u>30 (trinta) dias</u></b>, a contar da data de sua emissão, ';
$textoHTML .= 'conforme art. 553, Código de Normas da CGJ/MA e inciso IV do artigo 1° do Decreto Federal n° 93.240/1986, ';
$textoHTML .= 'sem reserva de prioridade. ';
$textoHTML .= $assinatura . ' ';
$textoHTML .= $cidade . '/' . $estado . ', ' . $dataAtual . '.';
$textoHTML .= '</p>';

// Escrever o texto HTML
$pdf->writeHTML($textoHTML, true, false, true, false, '');

// ============================================
// ADICIONAR SELOS AO PDF
// ============================================
if (!empty($selos)) {
    $pdf->Ln(5);
    
    $totalSelos = count($selos);
    $qrCodeSize = 70; // Tamanho do QR Code em mm
    
    // Organizar selos um abaixo do outro
    $pdf->SetFont('helvetica', '', 8);
    
    foreach ($selos as $selo) {
        // Verificar se há espaço suficiente na página (mínimo 45mm)
        if ($pdf->GetY() > 245) {
            $totalPaginasTextoSelo++; // Incrementar total de páginas
            $pdf->setPaginacao(true, $paginaTextoInicial, $totalPaginasTextoSelo);
            $pdf->AddPage(); // Nova página com timbrado (já está ativo)
            $pdf->SetMargins(25, 30, 25);
            $pdf->SetY(30);
        }
        
        $numeroSelo = htmlspecialchars($selo['numero_selo'] ?? '');
        $textoSelo = htmlspecialchars($selo['texto_selo'] ?? '');
        $qrCode = $selo['qr_code'] ?? '';
        $escrevente = htmlspecialchars($selo['escrevente'] ?? '');
        
        $html = '<table border="0" cellpadding="2" style="font-size: 8pt;" width="100%">';
        
        // Linha com QR Code e Texto (2 colunas: 20% e 80%)
        $html .= '<tr>';
        
        // Coluna 1: QR Code (20% da largura)
        $html .= '<td width="15%" align="center" valign="bottom">';
        if (!empty($qrCode)) {
            $html .= '<p></p><img src="@' . $qrCode . '" width="' . $qrCodeSize . '" height="' . $qrCodeSize . '"/>';
        }
        $html .= '</td>';
        
        // Coluna 2: Texto do selo (80% da largura)
        $html .= '<td width="85%" valign="top" style="font-size: 10pt;">';
        $html .= '<b style="font-size: 10pt;text-align: center">Poder Judiciário – TJMA</b><br/>';
        $html .= '<b style="font-size: 10pt;text-align: center">Selo: ' . $numeroSelo . '</b><br/>';
        $html .= '<span style="font-size: 10pt;text-align: justify">' . $textoSelo . '</span>';
        $html .= '</td>';
        
        $html .= '</tr>';
        $html .= '</table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(2);
    }
}

// Atualizar paginação final (recalcular total de páginas de texto/selo)
// Isso é necessário porque o TCPDF já gerou as páginas
// Vamos regerar o PDF com a contagem correta

// Contar páginas totais do PDF
$totalPaginasPDF = $pdf->getNumPages();
$paginasTextoSelo = $totalPaginasPDF - count($imagensSelecionadas);

// Precisamos regerar o PDF para ter a paginação correta
// Criar novo PDF com contagem correta
$pdf2 = new PDF_Certidao('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf2->SetCreator('DocMark');
$pdf2->SetAuthor('Cartório de Registro de Imóveis');
$pdf2->SetTitle('Certidão - Matrícula ' . $matriculaNumero);
$pdf2->SetSubject('Certidão de Matrícula');
$pdf2->setPrintHeader(false);
$pdf2->setPrintFooter(false);
$pdf2->SetMargins(0, 0, 0);
$pdf2->SetAutoPageBreak(false, 0);

// Páginas de imagem (sem timbrado, sem paginação)
$pdf2->setTimbrado($timbradoPath, false);
$pdf2->setPaginacao(false);
foreach ($imagensSelecionadas as $imagem) {
    $imagemPath = $certidaoDir . '/' . $imagem;
    if (file_exists($imagemPath)) {
        $pdf2->AddPage();
        $pdf2->Image($imagemPath, 0, 0, 210, 297, 'JPG', '', '', false, 300, '', false, false, 0, false, false, false);
    }
}

// Páginas de texto/selo (com timbrado e paginação)
$pdf2->setTimbrado($timbradoPath, true);
$pdf2->setPrintHeader(true);
$pdf2->setPrintFooter(true);
// Paginação: página atual começa após as imagens, total é o total de todas as páginas
$paginaInicialTexto = $qtdPaginasImagem + 1; // Primeira página de texto
$pdf2->setPaginacao(true, $paginaInicialTexto, $qtdFolhas); // Total inclui imagens + texto + selos
$pdf2->AddPage();
$pdf2->SetMargins(25, 30, 25); // Margem superior de 30mm (3cm)
$pdf2->SetAutoPageBreak(true, 25);
$pdf2->SetFont('helvetica', 'B', 14);
$pdf2->SetTextColor(0, 0, 0); // Cor preta
$pdf2->SetY(30); // 3cm da margem superior
$pdf2->Cell(0, 10, 'CERTIDÃO', 0, 1, 'C');
$pdf2->Ln(5);
$pdf2->SetFont('helvetica', '', 12);
$pdf2->SetTextColor(0, 0, 0); // Cor preta
$pdf2->writeHTML($textoHTML, true, false, true, false, '');

// Selos
if (!empty($selos)) {
    $pdf2->Ln(5);
    $pdf2->SetFont('helvetica', '', 8);
    $paginaAtualSelo = 1;
    
    foreach ($selos as $selo) {
        if ($pdf2->GetY() > 245) {
            $paginaAtualSelo++;
            $pdf2->AddPage();
            $pdf2->SetMargins(25, 30, 25);
            $pdf2->SetY(30);
        }
        
        $numeroSelo = htmlspecialchars($selo['numero_selo'] ?? '');
        $textoSelo = htmlspecialchars($selo['texto_selo'] ?? '');
        $qrCode = $selo['qr_code'] ?? '';
        
        $html = '<table border="0" cellpadding="2" style="font-size: 8pt;" width="100%">';
        $html .= '<tr>';
        $html .= '<td width="15%" align="center" valign="bottom">';
        if (!empty($qrCode)) {
            $html .= '<p></p><img src="@' . $qrCode . '" width="70" height="70"/>';
        }
        $html .= '</td>';
        $html .= '<td width="85%" valign="top" style="font-size: 10pt;">';
        $html .= '<b style="font-size: 10pt;text-align: center">Poder Judiciário – TJMA</b><br/>';
        $html .= '<b style="font-size: 10pt;text-align: center">Selo: ' . $numeroSelo . '</b><br/>';
        $html .= '<span style="font-size: 10pt;text-align: justify">' . $textoSelo . '</span>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        
        $pdf2->writeHTML($html, true, false, true, false, '');
        $pdf2->Ln(2);
    }
}

// Salvar PDF
$pdf2->Output($pdfFilePath, 'F');

// Verificar se o PDF foi gerado
if (!file_exists($pdfFilePath)) {
    echo '<script>alert("Erro ao gerar o PDF da certidão!"); window.location.href = "historico.php";</script>';
    exit;
}

// Atualizar informações da certidão
$certidaoInfo['status'] = 'gerada';
$certidaoInfo['pdf_gerado'] = $pdfFileName;
$certidaoInfo['data_geracao'] = date('Y-m-d H:i:s');
$certidaoInfo['imagens_utilizadas'] = $imagensSelecionadas;
$certidaoInfo['qtd_paginas_imagem'] = $qtdPaginasImagem;
$certidaoInfo['qtd_paginas_texto_selo'] = $qtdPaginasTextoSelo;
$certidaoInfo['qtd_folhas_total'] = $qtdFolhas;
$certidaoInfo['tipo_certidao'] = $tipoCertidao;

file_put_contents($certidaoInfoFile, json_encode($certidaoInfo, JSON_PRETTY_PRINT));

// Redirecionar para página de sucesso/download
header('Location: certidao-gerada.php?path=' . urlencode($certidaoPath) . '&pdf=' . urlencode($pdfFileName));
exit;
?>