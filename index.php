<?php
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
$url = APIURLBND;

// Validando
if (isSiteOnline($url)) {
    require_once("./online.php");
} else {
    require_once("./offline.php");
}
?>