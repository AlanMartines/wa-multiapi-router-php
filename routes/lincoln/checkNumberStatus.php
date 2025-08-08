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
function checkNumberStatus($params, $body) {
    checkParameter::SessionName($params, $body);
    checkToken::validateToken($params, $body);
    $SessionName = $body->SessionName ?? null;
    $phonefull = $body->phonefull ?? null;
    $telefoneLimpo = preg_replace('/\D/', '', $phonefull);
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/misc/onwhatsapp?key=' . trim($SessionName);
    //
    // Validação específica para sections
    if (!isset($body->phonefull) || empty(trim($body->phonefull))) {
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => http_response_code(),
                "message" => "É necessário informar 'phonefull'. Corrija e tente novamente."
            ]
        ]);
        exit;
    }
    //
    $config = [
        "id" => trim($telefoneLimpo)
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
                "message" => "Não foi possível execultar a ação"
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
                "number" => trim($telefoneLimpo),
                "message" => "O número informado não é um Whatsapp Valido"
            ];
        }
    } else {
        if(isset($responseData->data)){
            $result = [
                "error" => false,
                "statusCode" => $httpStatus,
                "number" => preg_replace('/\D/', '', $responseData->data),
                "message" => "O número informado é um Whatsapp Valido"
            ];
        }else{
            $result = [
                "error" => true,
                "statusCode" => $httpStatus,
                "number" => trim($telefoneLimpo),
                "message" => "Não foi possível validar se é um Whatsapp Valido"
            ];
        }
    }
    http_response_code($httpStatus);
    header('Content-type: application/json');
    print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/checkNumberStatus', function ($params, $body) {
    checkNumberStatus($params, $body);
});

$router->post('/checknumberstatus', function ($params, $body) {
    checkNumberStatus($params, $body);
});