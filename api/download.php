<?php
require __DIR__ . '/_lib.php';

$pdfPath = __DIR__ . '/../private/diplomado/' . $result['hashValidacion'] . '.pdf';

if (!file_exists($pdfPath)) {
    sendJson(404, ['error' => 'PDF no encontrado']);
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($pdfPath));
header('Content-Disposition: attachment; filename="' . $result['code'] . '.pdf"');
header('Cache-Control: no-store');
readfile($pdfPath);
exit;
