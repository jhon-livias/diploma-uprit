<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$records = require __DIR__ . '/_data.php';

function normalizeCode(string $raw): string {
    $code = strtoupper(trim($raw));
    return preg_replace('/\.PDF$/i', '', $code);
}

function sendJson(int $status, array $data): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validatePayload(array $payload, array $records): array {
    if (!isset($payload['code'], $payload['dni'])) {
        sendJson(400, ['error' => 'Solicitud inválida']);
    }

    $code = normalizeCode($payload['code']);
    $dni  = trim($payload['dni']);

    if (!preg_match('/^\d{3}-FJEI-2026$/', $code) || !preg_match('/^\d{7,12}$/', $dni)) {
        sendJson(400, ['error' => 'Datos inválidos']);
    }

    $expectedHash = hash('sha256', $dni . ':' . $code);

    if (!isset($records[$code]) || $records[$code]['dniHash'] !== $expectedHash) {
        sendJson(403, ['error' => 'No autorizado']);
    }

    return ['code' => $code, 'record' => $records[$code]];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['error' => 'Método no permitido']);
}

$body = file_get_contents('php://input');
$payload = json_decode($body, true);
$result = validatePayload($payload ?? [], $records);
