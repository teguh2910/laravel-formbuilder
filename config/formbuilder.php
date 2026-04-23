<?php

$basePath = trim((string) env('FORMBUILDER_BASE_PATH', ''), '/');
$routePrefix = trim((string) env('FORMBUILDER_ROUTE_PREFIX', 'formbuilder'), '/');
$apiPrefix = trim((string) env('FORMBUILDER_API_PREFIX', $routePrefix . '/api'), '/');

if ($basePath !== '') {
    // If route_prefix already has a slash, treat it as full custom path and do not auto-prepend base_path.
    $routePrefixIsFullPath = str_contains($routePrefix, '/');
    $apiPrefixIsFullPath = str_contains($apiPrefix, '/');

    if (!$routePrefixIsFullPath && !str_starts_with($routePrefix, $basePath . '/')) {
        $routePrefix = trim($basePath . '/' . $routePrefix, '/');
    }
    if (!$apiPrefixIsFullPath && !str_starts_with($apiPrefix, $basePath . '/')) {
        $apiPrefix = trim($basePath . '/' . $apiPrefix, '/');
    }
}

return [
    // Optional app sub-path, e.g. "ais-v4" so package URLs become "/ais-v4/formbuilder/...".
    'base_path' => $basePath,
    'route_prefix' => $routePrefix,
    'api_prefix' => $apiPrefix,
];
