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
function getConfigWh($params, $body)
{
    checkParameter::SessionName($params, $body);
    checkToken::validateToken($params, $body);
    $SessionName = $body->SessionName ?? null;
    $apiUrl = APIURLBND;
    $apiUrlRequest = "$apiUrl/instance/info?key=" . trim($SessionName);

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
                "message" => "Não foi possível iniciar a sessão"
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
                "message" => $responseData->message
            ];
        }
    } else {
        $instanceData = $responseData->instance_data ?? null;

        if ($instanceData) {
            $webhookUrl = $instanceData->webhookUrl ?? null;
            $webhookEvents = $instanceData->webhookEvents ?? [];

            $wh_connect = in_array('connection.update', $webhookEvents);
            $wh_qrcode = in_array('qrCode.update', $webhookEvents);
            $wh_status = in_array('messages.update', $webhookEvents) || in_array('groups.update', $webhookEvents);
            $wh_message = in_array('messages.upsert', $webhookEvents) || in_array('groups.upsert', $webhookEvents);

            $result = [
                "error" => false,
                "statusCode" => $httpStatus,
                "message" => "Sessão atualizada com sucesso",
                "configWh" => [
                    "webhook_api" => $webhookUrl,
                    "wh_connect" => $wh_connect,
                    "wh_status" => $wh_status,
                    "wh_message" => $wh_message,
                    "wh_qrcode" => $wh_qrcode
                ]
            ];
        } else {
            $result = [
                "error" => true,
                "statusCode" => $httpStatus,
                "message" => "Não foi possível obter as configurações da instância"
            ];
        }
    }

    //
    http_response_code($httpStatus);
    header('Content-type: application/json');
    print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/getConfigWh', function ($params, $body) {
    getConfigWh($params, $body);
});

$router->post('/getconfigwh', function ($params, $body) {
    getConfigWh($params, $body);
});
