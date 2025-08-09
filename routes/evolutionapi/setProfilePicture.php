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
function setProfilePicture($params, $body)
{
    checkParameter::SessionName($params, $body);
    checkToken::validateToken($params, $body);
    $SessionName = $body->SessionName ?? null;
    $url = $body->profilePicture ?? null;
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/misc/mystatus?key=' . trim($SessionName);
    //
    if (!isset($body->profilePicture) || empty(trim($body->profilePicture))) {
        // Arquivo inválido: não é áudio ou extensão não reconhecida
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => http_response_code(),
                "message" => "É necessário informar 'profilePicture'. Corrija e tente novamente."
            ]
        ]);
        exit;
    }
    //
    $getUserConnected = checkToken::getUserConnected($params, $body);
    //
    if (!$getUserConnected) {
        // Arquivo inválido: não é áudio ou extensão não reconhecida
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => http_response_code(),
                "message" => "Dispositivo desconectado"
            ]
        ]);
        exit;
    }
    //
    $config = [
        "id" => trim($getUserConnected),
        "url" => trim($url),
        "type" => "user"
    ];
    // Codificar em JSON
    $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    //
    $result = Request::CurlRequest($apiUrlRequest, 'POST', $jsonConfig);
    $response = $result['response'];
    $httpStatus = $result['httpStatus'];
    $error = $result['error'];
    $meta = $result['meta'];
    //
    if ($response === false) {
        error_log("Erro na requisição: $error");

        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => http_response_code(),
                "message" => "Não foi possível atualizr perfil"
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
                "message" => "Dispositivo desconectado"
            ];
            //
            $adv = adv::conectar(Conexao::conectar());
            $validateToken = $adv->validateToken($SessionName);
            $whatsnotify = $validateToken->whatsnotify ?? $validateToken->userconnected ?? null;
            sendWhatsapp::sendWhatsappMsgErroSendInfo($whatsnotify);
            //
        } else if (strpos($responseData->message, "phone isn't connected") !== false) {
            $result = [
                "error" => true,
                "statusCode" => $httpStatus,
                "message" => "Dispositivo desconectado"
            ];
            //
            $adv = adv::conectar(Conexao::conectar());
            $validateToken = $adv->validateToken($SessionName);
            $whatsnotify = $validateToken->whatsnotify ?? $validateToken->userconnected ?? null;
            sendWhatsapp::sendWhatsappMsgErroSendInfo($whatsnotify);
            //
        } else {
            $result = [
                "error" => true,
                "statusCode" => $httpStatus,
                "message" => $responseData->message ?? null
            ];
        }
    } else {
        if (!empty($responseData->data->error)) {
            $httpStatus = 401;
            $result = [
                "error" => true,
                "statusCode" => $httpStatus,
                "message" => "Dispositivo desconectado"
            ];
        } else {
            $result = [
                "error" => false,
                "statusCode" => $httpStatus,
                "message" => $responseData->data->message ?? null
            ];
        }
    }
    http_response_code($httpStatus);
    header('Content-type: application/json');
    print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/SetProfilePicture', function ($params, $body) {
    setProfilePicture($params, $body);
});
//
$router->post('/setProfilePicture', function ($params, $body) {
    setProfilePicture($params, $body);
});
//
$router->post('/setprofilepicture', function ($params, $body) {
    setProfilePicture($params, $body);
});
