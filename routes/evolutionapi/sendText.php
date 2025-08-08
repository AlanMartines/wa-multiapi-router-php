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
function sendText($params, $body)
{
    checkParameter::sendText($params, $body);
    checkToken::validateToken($params, $body);
    $SessionName = $body->SessionName ?? null;
    $phonefull = $body->phonefull ?? null;
    $msg = $body->msg ?? null;
    $telefoneLimpo = preg_replace('/\D/', '', $phonefull);
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/message/text?key=' . trim($SessionName);
    //
    $isMultiMessage = str_contains($msg, '[NOVAMSG]');
    $messages = $isMultiMessage ? explode('[NOVAMSG]', $msg) : [$msg];
    //
    if ($isMultiMessage) {
        //
        $results = [];
        $index = 1;
        //
        foreach ($messages as $partMsg) {
            //
            $config = [
                "id" => trim($telefoneLimpo),
                "typeId" => "user",
                "message" => trim($partMsg),
                "options" => [
                    "presence" => "composing",
                    "replyFrom" => "",
                    "delay" => 0
                ]
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
                $results[] = [
                    "error" => true,
                    "statusCode" => 400,
                    "fromNumber" => $telefoneLimpo,
                    "msgID" => null,
                    "message" => "Erro ao enviar mensagem: {$error}",
                    "countMsg" => $index++
                ];
                continue;
            }

            $responseData = json_decode($response, false);

            if (!empty($responseData->error)) {
                if (strpos($responseData->message, 'invalid key') !== false) {
                    $results[] = [
                        "error" => true,
                        "fromNumber" => $telefoneLimpo,
                        "msgID" => null,
                        "message" => "Dispositivo desconectado",
                        "countMsg" => $index++
                    ];
                    //
                    $adv = adv::conectar(Conexao::conectar());
                    $validateToken = $adv->validateToken($SessionName);
                    $whatsnotify = $validateToken->whatsnotify ?? $validateToken->userconnected ?? null;
                    sendWhatsapp::sendWhatsappMsgErroSend($whatsnotify, $telefoneLimpo);
                    //
                } else if (strpos($responseData->message, "phone isn't connected") !== false) {
                    $results[] = [
                        "error" => true,
                        "fromNumber" => $telefoneLimpo,
                        "msgID" => null,
                        "message" => "Dispositivo desconectado",
                        "countMsg" => $index++
                    ];
                    //
                    $adv = adv::conectar(Conexao::conectar());
                    $validateToken = $adv->validateToken($SessionName);
                    $whatsnotify = $validateToken->whatsnotify ?? $validateToken->userconnected ?? null;
                    sendWhatsapp::sendWhatsappMsgErroSend($whatsnotify, $telefoneLimpo);
                    //
                } else {
                    $results[]  = [
                        "error" => true,
                        "msgID" => null,
                        "message" => $responseData->message,
                        "countMsg" => $index++
                    ];
                }
            } else {
                $results[] = [
                    "erro" => false,
                    "fromNumber" => $telefoneLimpo,
                    "msgID" => $responseData->data->key->id ?? null,
                    "message" => "Mensagem enviada com sucesso.",
                    "countMsg" => $index++
                ];
            }
            //
            if ($httpStatus == 200 || $httpStatus == 201 || $httpStatus == 202) {
                $successStatistics = updateStatistics::updateSuccess($SessionName);
            } else {
                $errorStatistics = updateStatistics::updateError($SessionName);
            }
            //
            $sock = new Sockets(WEBSOCKET, 443, '/ws/');
            //
            $dataSocket = [
                'sources' => 'logSend',
                'request' => $body,
                'result' => $result,
                'error' => $error,
                'meta' => $meta,
            ];
            //
            $sock->messagesent($SessionName, $dataSocket);
            //
        }
        http_response_code(201);
        header('Content-type: application/json');
        echo json_encode([
            "Status" => [
                "error" => false,
                "statusCode" => http_response_code(),
                "result" => $results
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    } else {
        $config = [
            "id" => trim($telefoneLimpo),
            "typeId" => "user",
            "message" => $msg,
            "options" => [
                "presence" => "composing",
                "replyFrom" => "",
                "delay" => 0
            ]
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
                sendWhatsapp::sendWhatsappMsgErroSend($whatsnotify, $telefoneLimpo);
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
                sendWhatsapp::sendWhatsappMsgErroSend($whatsnotify, $telefoneLimpo);
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
                "msgID" => $responseData->data->key->id ?? null,
                "message" => "Mensagem enviada com sucesso."
            ];
        }
        //
        if ($httpStatus == 200 || $httpStatus == 201 || $httpStatus == 202) {
            $successStatistics = updateStatistics::updateSuccess($SessionName);
        } else {
            $errorStatistics = updateStatistics::updateError($SessionName);
        }
        //
        $sock = new Sockets(WEBSOCKET, 443, '/ws/');
        //
        $dataSocket = [
            'sources' => 'logSend',
            'request' => $body,
            'result' => $result,
            'error' => $error,
            'meta' => $meta,
        ];
        //
        $sock->messagesent($SessionName, $dataSocket);
        //
        http_response_code($httpStatus);
        header('Content-type: application/json');
        print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}
//
$router->post('/SendText', function ($params, $body) {
    sendText($params, $body);
});
//
$router->post('/sendText', function ($params, $body) {
    sendText($params, $body);
});
//
$router->post('/sendtext', function ($params, $body) {
    sendText($params, $body);
});
