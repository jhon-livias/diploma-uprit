<?php
require __DIR__ . '/_lib.php';

sendJson(200, [
    'ok' => true,
    'nombre' => $result['nombre'],
    'registro' => $result['code'],
    'hashValidacion' => $result['hashValidacion'],
]);
