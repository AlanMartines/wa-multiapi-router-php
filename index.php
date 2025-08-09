<?php
//
// Não exibir erros na tela
ini_set('display_errors', 0);
// Ativar o log de erros
ini_set('log_errors', 1);
// Definir onde o log será salvo
ini_set('error_log', dirname(__FILE__) . '/error_log.txt');
// Definir o nível de erros que serão reportados
error_reporting(E_ALL);
//
require_once('./config.php');
// Função para checar se o site está online
function isSiteOnline($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true); // Não buscar conteúdo, só testar conexão
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Tempo limite
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignora SSL inválido
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno) {
        return false;
    }

    return $httpCode > 0; // Se tiver qualquer código HTTP, está online
}

// URL que queremos testar
$url = APIURL;

// Validando
if (isSiteOnline($url)) {
    require_once("./online.php");
} else {
    require_once("./offline.php");
}
?>