<?php
// Time zone
setlocale(LC_TIME, 'pt_BR.utf8');
date_default_timezone_set('America/Sao_Paulo');

// Carregar variáveis de ambiente (.env)
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/** pasta absoluta do sistema **/
if (!defined('ABSPAST'))
    define('ABSPAST', basename(__DIR__));

/** caminho absoluto para a pasta do sistema **/
if (!defined('ABSPATH'))
    define('ABSPATH', dirname(__FILE__) . '/');

/** API_TOKEN */
if (!defined('ADMINTOKEN'))
    define('ADMINTOKEN', $_ENV['ADMINTOKEN'] ?? '');

if (!defined('APIURL'))
    define('APIURL', $_ENV['APIURL'] ?? '');

if (!defined('SOURCES'))
    define('SOURCES', $_ENV['SOURCES'] ?? 'evolutionapi');
