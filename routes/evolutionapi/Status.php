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
//
require_once('../config.php');
require_once('../middlewares/checkParameter.php');
require_once('../handler/handler.php');
require_once('../functions/request.php');
require_once('../functions/fnSockets.php');
//
function Status($params, $body) {
    checkParameter::SessionName($params, $body);
    checkToken::validateToken($params, $body);
    $SessionName = $body->SessionName ?? null;
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl.'/instance/info?key='.trim($SessionName);
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
                "state" => "DISCONNECTED",
                "status" => "notLogged",
                "message" => "Não foi possível verificar o status"
            ]
        ]);
        exit;
    }

    $responseData = json_decode($response, false);

    if (!empty($responseData->error)) {
        if (strpos($responseData->message, 'invalid key') !== false) {
            $result = [
                "error" => true,
                "statusCode" => $httpStatus,
                "state" => "DISCONNECTED",
                "status" => "notLogged",
                "phone" => null,
                "name" => null,
                "message" => "Sessão não iniciada"
            ];
        } else {
            $result = [
                "error" => false,
                "statusCode" => $httpStatus,
                "phone" => null,
                "name" => null,
                "message" => "Dispositivo desconectado"
            ];
        }
    } else {
        $connected = $responseData->instance_data->phone_connected ?? null;
        $phoneConnection = isset($responseData->instance_data->user->id) ? explode(':', $responseData->instance_data->user->id)[0] : null;
        $name = $responseData->instance_data->user->name ?? null;
        switch ($connected) {
            case true:
                $result = [
                    "error" => false,
                    "statusCode" => $httpStatus,
                    "state" => "CONNECTED",
                    "status" => "inChat",
                    "phone" => $phoneConnection,
                    "name" => $name,
                    "message" => "Sistema iniciado e disponível para uso"
                ];
                break;
            case false:
                $result = [
                    "error" => false,
                    "statusCode" => $httpStatus,
                    "state" => "DISCONNECTED",
                    "status" => "notLogged",
                    "phone" => null,
                    "name" => null,
                    "message" => "Dispositivo desconectado"
                ];
                break;
            default:
                $result = [
                    "error" => false,
                    "statusCode" => $httpStatus,
                    "state" => "STARTING",
                    "status" => "notLogged",
                    "phone" => null,
                    "name" => null,
                    "message" => "Sistema iniciando, aquarde por favor"
                ];
                break;
        }
    }
    http_response_code($httpStatus);
    header('Content-type: application/json');
    print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/Status', function ($params, $body) {
    Status($params, $body);
});

$router->post('/status', function ($params, $body) {
    Status($params, $body);
});