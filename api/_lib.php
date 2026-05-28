<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Mapa registro => { hashValidacion, nombre } (generado por npm run generate-data)
$records = require __DIR__ . '/_data.php';

// ── Normalización ─────────────────────────────────────────────────────────────
// DEBE ser idéntica a normalize() en DiplomaView.jsx y en el script Node.
// 1. trim + lowercase  2. NFD + elimina diacríticos U+0300-U+036F  3. colapsa espacios
function normalizeStr(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    // Descomposición NFD para aislar las marcas diacríticas
    if (class_exists('Normalizer')) {
        $d = Normalizer::normalize($s, Normalizer::FORM_D);
        if ($d !== false) $s = $d;
    }
    // Eliminar marcas diacríticas combinadas U+0300–U+036F (tildes, acentos, diéresis…)
    $s = preg_replace('/[\x{0300}-\x{036f}]/u', '', $s);
    // Colapsar espacios múltiples
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
}

// ── Hash maestro ──────────────────────────────────────────────────────────────
// SHA-256( norm(dni) + "|" + norm(registro) + "|" + norm(nombre) )
function masterHash(string $dni, string $registro, string $nombre): string {
    $input = normalizeStr($dni) . '|' . normalizeStr($registro) . '|' . normalizeStr($nombre);
    return hash('sha256', $input);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
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

// ── Validación del request ────────────────────────────────────────────────────
function validatePayload(array $payload, array $records): array {
    if (!isset($payload['code'], $payload['dni'])) {
        sendJson(400, ['error' => 'Solicitud inválida']);
    }

    $code = normalizeCode($payload['code']);
    $dni  = trim($payload['dni']);

    if (!preg_match('/^\d{3}-FJEI-2026$/', $code) || !preg_match('/^\d{7,12}$/', $dni)) {
        sendJson(400, ['error' => 'Datos inválidos']);
    }

    if (!isset($records[$code])) {
        sendJson(403, ['error' => 'No autorizado']);
    }

    $record   = $records[$code];
    $expected = masterHash($dni, $code, $record['nombre']);

    if ($record['hashValidacion'] !== $expected) {
        sendJson(403, ['error' => 'No autorizado']);
    }

    return ['code' => $code, 'dni' => $dni, 'nombre' => $record['nombre'], 'hashValidacion' => $record['hashValidacion']];
}

// ── Entry point ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['error' => 'Método no permitido']);
}

$body    = file_get_contents('php://input');
$payload = json_decode($body, true);
$result  = validatePayload($payload ?? [], $records);
