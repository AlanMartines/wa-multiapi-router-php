<?php
//
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
//
require_once('../config.php');
require_once('../middlewares/checkParameter.php');
require_once('../handler/handler.php');
require_once('../functions/request.php');
require_once('../functions/fnSockets.php');
//
function restoreAllToken($params, $body) {
    checkParameter::SessionName($params, $body);
    checkToken::validateToken($params, $body);
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl.'/instance/restore?admintoken='.trim(ADMINTOKEN);
    //
    $result = Request::CurlRequest($apiUrlRequest, 'GET', null);
    $response = $result['response'];
    $httpStatus = $result['httpStatus'];
    $error = $result['error'];
    //
    if ($response === false) {
        error_log("Erro na requisição: $error");

        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => http_response_code(),
                "message" => "Não foi possível restaurar as sessões"
            ]
        ]);
        exit;
    }

    $responseData = json_decode($response, false);

    if (!empty($responseData->error)) {
        $result = [
            "error" => true,
            "statusCode" => $httpStatus,
            "message" => "Erro ao restaurar as sessões"
        ];
    } else {
        $result = [
            "error" => false,
            "statusCode" => $httpStatus,
            "message" => "Sessões restauradas com sucesso",
            "data" => $responseData->data
        ];
    }
    http_response_code($httpStatus);
    header('Content-type: application/json');
    print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/restoreAllToken', function ($params, $body) {
    restoreAllToken($params, $body);
});

$router->post('/restorealltoken', function ($params, $body) {
    restoreAllToken($params, $body);
});