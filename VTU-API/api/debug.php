<?php
header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uriParts = explode('/api/', $uri, 2);
$endpoint = isset($uriParts[1]) ? trim($uriParts[1], '/') : '';

$uriSegments = explode('/', $endpoint);
$endpoint = $uriSegments[0] ?? '';
$subEndpoint = $uriSegments[1] ?? null;

echo json_encode([
    'REQUEST_URI' => $_SERVER['REQUEST_URI'],
    'parsed_uri' => $uri,
    'uriParts' => $uriParts,
    'uriSegments' => $uriSegments,
    'endpoint' => $endpoint,
    'subEndpoint' => $subEndpoint,
], JSON_PRETTY_PRINT);
