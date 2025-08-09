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
function sendFileBase64Mult($params, $body)
{
    checkParameter::sendFileBase64Mult($params, $body);
    checkToken::validateToken($params, $body);
    $SessionName = $body->SessionName ?? null;
    $base64 = $body->base64 ?? null;
    $originalname = $body->originalname ?? null;
    $caption = $body->caption ?? null;
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/message/sendbase64file?key=' . trim($SessionName);
    //
    $results = [];
    $index = 1;
    //
    foreach ($body->phonefull as $number) {
        $numberClean = preg_replace('/\D/', '', $number);

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
            "id" => trim($numberClean),
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
            $results[] = [
                "error" => true,
                "statusCode" => 400,
                "fromNumber" => $numberClean,
                "msgID" => null,
                "message" => "Erro ao enviar mensagem: {$error}",
                "countMsg" => $index++
            ];
            continue;
        }

        $responseData = json_decode($response, false);

        if (!empty($responseData->error)) {
            if (strpos($responseData->message, 'invalid key') !== false) {
                $httpStatus = 401;
                $results[] = [
                    "error" => true,
                    "fromNumber" => $numberClean,
                    "msgID" => null,
                    "message" => "Dispositivo desconectado",
                    "countMsg" => $index++
                ];
                //
                $adv = adv::conectar(Conexao::conectar());
                $validateToken = $adv->validateToken($SessionName);
                $whatsnotify = $validateToken->whatsnotify ?? $validateToken->userconnected ?? null;
                sendWhatsapp::sendWhatsappMsgErroSend($whatsnotify, $numberClean);
                //
            } else if (strpos($responseData->message, 'invalid key') !== false) {
                $httpStatus = 401;
                $results[] = [
                    "error" => true,
                    "fromNumber" => $numberClean,
                    "msgID" => null,
                    "message" => "Dispositivo desconectado",
                    "countMsg" => $index++
                ];
                //
                $adv = adv::conectar(Conexao::conectar());
                $validateToken = $adv->validateToken($SessionName);
                $whatsnotify = $validateToken->whatsnotify ?? $validateToken->userconnected ?? null;
                sendWhatsapp::sendWhatsappMsgErroSend($whatsnotify, $numberClean);
                //
            } else {
                $httpStatus = 401;
                $results[]  = [
                    "error" => true,
                    "msgID" => null,
                    "message" => $responseData->message,
                    "countMsg" => $index++
                ];
            }
        } else {
            $httpStatus = 200;
            $results[] = [
                "erro" => false,
                "fromNumber" => $numberClean,
                "msgID" => $responseData->data->key->id ?? null,
                "message" => "Mensagem enviada com sucesso.",
                "countMsg" => $index++
            ];
        }
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
}
//
$router->post('/sendFileBase64Mult', function ($params, $body) {
    sendFileBase64Mult($params, $body);
});

$router->post('/sendfilebase64mult', function ($params, $body) {
    sendFileBase64Mult($params, $body);
});
