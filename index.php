<?php
session_start();

// Router basique
$request = $_SERVER['REQUEST_URI'];

// Nettoyer l'URL des paramètres de requête
$request = parse_url($request, PHP_URL_PATH);

// Définir le chemin de base en fonction du dossier du projet
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath === '/') $basePath = '';

// Retirer le chemin de base de la requête
$request = str_replace($basePath, '', $request);

switch ($request) {
    case '':
    case '/':
    case '/dashboard':
        require __DIR__ . '/src/views/dashboard.php';
        break;
    default:
        http_response_code(404);
        echo "Page non trouvée";
        break;
} 