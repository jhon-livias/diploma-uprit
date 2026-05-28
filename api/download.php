<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$body = file_get_contents('php://input');
$payload = json_decode($body, true);

if (!$payload || !isset($payload['code'], $payload['dni'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Solicitud inválida']);
    exit;
}

$code = strtoupper(trim($payload['code']));
$code = preg_replace('/\.PDF$/i', '', $code);
$dni  = trim($payload['dni']);

if (!preg_match('/^\d{3}-FJEI-2026$/', $code) || !preg_match('/^\d{7,12}$/', $dni)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

// Hashes SHA-256(dni:registro) — sin DNIs en texto plano
$hashes = [
    '001-FJEI-2026' => '11fae5a7ec3399aed43b807689006b803d05ee2da5a40ffc7bda13ccdeb68c4a',
    '002-FJEI-2026' => 'd9ac81a6a648ebb3da0f70dc95724cf43aa9a4f98eb54b6df21a24875ecf55c5',
    '003-FJEI-2026' => 'ac56e51c762701b72438e530496ba78c6a6bc70daffa68141e5c63890d8cc780',
    '004-FJEI-2026' => '381729ef25a35b2562d2aebfbb34f82b02522fca557f7dd21abb7fe404b21984',
    '005-FJEI-2026' => '99247bc4bc948640cf0a78aabf642b3da7571dcfb5808d408afd920178ca7744',
    '006-FJEI-2026' => '287f526b8746445d13a95746ad012f7d7ae14351ac49f792c53e0df6c2d38791',
    '007-FJEI-2026' => '6d1eded34d380d882e79f602a5acc9e8a45e13352d0755ee668b10c857a9ef69',
    '008-FJEI-2026' => '04253a79a7dee0e0b5ecc52ad98f5da7becba6f8cbcbcbf20b629016be01128c',
    '009-FJEI-2026' => '3fed59882eee7574ebfea506c70d1249a05066287e5eccee25cf737a8a07ce31',
    '010-FJEI-2026' => 'f2fad767c5f4a3429c6ca6718a97aad042dc1326f605305e26f78f81b9dd5913',
    '011-FJEI-2026' => 'ef8b45fb592b69af2e585467399266dd7b555446208203ee83d5187209e04158',
    '012-FJEI-2026' => '9738fa900d50505733d676b09f0d8253b5ae464755bdffa088a5522609391427',
    '013-FJEI-2026' => 'e264186f1e563154797983732b11f4d46f9fc867a688d1ce69fa326ec32ea3a9',
    '014-FJEI-2026' => '7aca83c29ac35ca11c06a1f99ef7a3e64aa0a9efc5da411f4cadc3aaa3e94408',
    '015-FJEI-2026' => '3f55fad5112346452db64c4080c898b183bf07c46773ca34ccfeebd38ad7bf4c',
    '016-FJEI-2026' => '209d86696b5a860398f86937cd18099fc0ddfc81fb8ed9f2ff92579ef54c6934',
    '017-FJEI-2026' => '4bbf79ef885f97d04fdc993822da04717c09fc395bc63b4e1bbeff4cea8026c7',
    '018-FJEI-2026' => 'fac6a83adfe4b6b509a981d67590dcdc3c418fa9ce4a97efc6db7ee7bc3c57c6',
    '019-FJEI-2026' => '27a39a204f4a5b431871329df2cf25125b248b6bd11e5ccc33b27412ee70c776',
    '020-FJEI-2026' => 'c428dac915b2ec5afb04894e1516f30a3949490ee2881f89978cbfcb79d955c8',
    '021-FJEI-2026' => '93f16b3091316d4823bc00ab9ec7d86c92ef210479155695c641be2c6e851a15',
    '022-FJEI-2026' => 'a76a7ce005083f604cff3bd6893684be4f0b2d8efb2b2e8fa790f0e2309151cc',
    '023-FJEI-2026' => '00ff539bca722367ff7acaa06256acdfa3ae1d2d3961a400a2ddadd495505b64',
    '024-FJEI-2026' => '2b78710ac85e81a2e5a0f996b448bc52c44bb5d1c5554087595beb42bf55e6d9',
];

$expectedHash = hash('sha256', $dni . ':' . $code);

if (!isset($hashes[$code]) || $hashes[$code] !== $expectedHash) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$pdfPath = __DIR__ . '/../private/diplomado/' . $code . '.pdf';

if (!file_exists($pdfPath)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'PDF no encontrado']);
    exit;
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($pdfPath));
header('Content-Disposition: attachment; filename="' . $code . '.pdf"');
header('Cache-Control: no-store');
readfile($pdfPath);
exit;
