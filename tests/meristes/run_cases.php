<?php
// Casos dos eventos que o Meristes envia na EFD-Reinf 2.1.2 (R-1000 de igreja imune, R-4010 de aluguel
// pago a pessoa física, natureza 13002, e R-4099). Cada caso é montado pela lib, assinado com o
// certificado de teste da lib e validado no XSD que a lib escolhe (o pacote oficial, sufixo _D).
// Com um argumento, valida também nos XSDs dessa pasta.
// Uso: php tests/meristes/run_cases.php [dir_xsd_oficial]
require __DIR__ . '/../../bootstrap.php';
use NFePHP\Common\Certificate;
use NFePHP\EFDReinf\Event;
libxml_use_internal_errors(true);
$cert = Certificate::readPfx(
    file_get_contents(__DIR__ . '/../../examples/Fake/v2_01_02/expired_certificate.pfx'),
    'associacao'
);
$falhas = 0;
foreach (glob(__DIR__ . '/cases/*.json') as $file) {
    $case = json_decode(file_get_contents($file));
    $nome = basename($file, '.json');
    try {
        $xml = Event::{$case->event}(json_encode($case->config), $case->std, $cert)->toXml();
        echo "OK    $nome (lib + XSD da lib)\n";
    } catch (\Throwable $e) {
        $falhas++;
        echo "FALHA $nome: " . str_replace("\n", ' | ', $e->getMessage()) . "\n";
        continue;
    }
    if (!empty($argv[1])) {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $ok = $dom->schemaValidate($argv[1] . "/{$case->xsd}.xsd");
        $erros = array_map(fn($e) => trim($e->message), libxml_get_errors());
        libxml_clear_errors();
        if (!$ok) {
            $falhas++;
        }
        echo ($ok ? 'OK   ' : 'FALHA') . " $nome XSD oficial" . ($ok ? '' : ': ' . implode(' | ', array_slice($erros, 0, 3))) . "\n";
    }
}
exit($falhas > 0 ? 1 : 0);
