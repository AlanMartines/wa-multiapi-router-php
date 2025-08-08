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
function setConfigWh($params, $body) {
    checkParameter::Start($params, $body);
    checkToken::validateToken($params, $body);

    $SessionName = $body->SessionName ?? null;
    $webhook_cli = $body->webhook->webhook_cli ?? null;
    $wh_status = $body->webhook->wh_status ?? false;
    $wh_message = $body->webhook->wh_message ?? false;
    $wh_qrcode = $body->webhook->wh_qrcode ?? false;
    $wh_connect = $body->webhook->wh_connect ?? false;
    $apiUrl = APIURLBND;
    $apiUrlRequest = "$apiUrl/instance/editar";

    //
    $webhookEvents = ["call.events"];
    if ($wh_connect) { $webhookEvents[] = "connection.update"; }
    if ($wh_qrcode) { $webhookEvents[] = "qrCode.update"; }
    if ($wh_status) {
        $webhookEvents[] = "messages.update";
        $webhookEvents[] = "groups.update";
    }
    if ($wh_message) {
        $webhookEvents[] = "messages.upsert";
        $webhookEvents[] = "groups.upsert";
    }
    //
    $config = [
        "key" => trim($SessionName),
        "browser" => "Ubuntu",
        "webhook" => true,
        "base64" => true,
        "webhookUrl" => $webhook_cli,
        "webhookEvents" => $webhookEvents,
        "ignoreGroups" => false,
        "messagesRead" => false
    ];

    // Codificar em JSON
    $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    //
    $result = Request::CurlRequest($apiUrlRequest, 'POST', $jsonConfig);
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
        $result = [
            "error" => false,
            "statusCode" => $httpStatus,
            "message" => "Sessão atualizada com sucesso",
            "configWh" => [
                "webhook_api" => $webhook_cli,
                "wh_connect" => $wh_connect ? true : false,
                "wh_status" => $wh_status ? true : false,
                "wh_message" => $wh_message ? true : false,
                "wh_qrcode" => $wh_qrcode ? true : false
            ]
        ];
    }

    //
    http_response_code($httpStatus);
    header('Content-type: application/json');
    print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/setConfigWh', function ($params, $body) {
    setConfigWh($params, $body);
});

$router->post('/setconfigwh', function ($params, $body) {
    setConfigWh($params, $body);
});