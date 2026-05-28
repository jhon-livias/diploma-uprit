<?php
require __DIR__ . '/_lib.php';

sendJson(200, [
    'ok' => true,
    'nombre' => $result['record']['nombre'],
    'registro' => $result['code'],
]);
