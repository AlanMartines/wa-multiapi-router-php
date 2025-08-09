<?php
// Time zone
setlocale(LC_TIME, 'pt_BR.utf8');
date_default_timezone_set('America/Sao_Paulo');

// Carregar variáveis de ambiente (.env)
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/** API_TOKEN */
if (!defined('ADMINTOKEN'))
    define('ADMINTOKEN', $_ENV['ADMINTOKEN'] ?? null);

if (!defined('APIURL'))
    define('APIURL', $_ENV['APIURL'] ?? null);

if (!defined('SOURCES'))
    define('SOURCES', $_ENV['SOURCES'] ?? null);
