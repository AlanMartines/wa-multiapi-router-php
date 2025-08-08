<?php
//
require_once('../config.php');
require_once('../middlewares/checkParameter.php');
require_once('../handler/handler.php');
require_once('../inc/functions.php');
//
function sendFileBase64Massa($params, $body)
{
    checkParameter::sendFileBase64($params, $body);
    checkToken::validateToken($params, $body);
    $email = $body->email ?? null;
    $phonefull = $body->phonefull ?? null;
    $base64 = $body->base64 ?? null;
    $originalname = $body->originalname ?? null;
    $caption = $body->caption ?? null;
    $telefoneLimpo = preg_replace('/\D/', '', $phonefull);
    //
    $adv = adv::conectar(Conexao::conectar());
    $sendMassaToken = $adv->sendMassaToken($email);
    //
    if (!$sendMassaToken) {
        http_response_code(401);
        header('Content-type: application/json');
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => http_response_code(),
                "message" => "Todos os dispositivo estão desconectado"
            ]
        ]);
        exit;
    }
    //
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/message/sendbase64file?key=' . trim($sendMassaToken->token);
    $SessionName = $sendMassaToken->token;
    //
    $mimeType = checkParameter::mimeTypeFileName($params, $body);
    $mime = null;
    switch (true) {
        case str_starts_with($mimeType, 'audio/'):
            $mime = 'audio';
            break;

        case str_starts_with($mimeType, 'video/'):
            $mime = 'video';
            break;

        case str_starts_with($mimeType, 'image/'):
            $mime = 'image';
            break;

        default:
            $mime = 'document';
            break;
    }
    //
    $config = [
        "id" => trim($telefoneLimpo),
        "typeId" => "user",
        "type" => $mime,
        "base64string" => $base64,
        "filename" => $originalname,
        "options" => [
            "caption" => $caption,
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
//
$router->post('/sendFileBase64Massa', function ($params, $body) {
    sendFileBase64Massa($params, $body);
});

$router->post('/sendfilebase64massa', function ($params, $body) {
    sendFileBase64Massa($params, $body);
});
