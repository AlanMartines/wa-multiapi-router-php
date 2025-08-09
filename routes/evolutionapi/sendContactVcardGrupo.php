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
function sendContactVcardGrupo($params, $body)
{
    checkParameter::sendContactVcard($params, $body);
    checkToken::validateToken($params, $body);
    $SessionName = $body->SessionName ?? null;
    $groupId = $body->groupId ?? null;
    $namecontact = $body->namecontact ?? null;
    $organization = $body->organization ?? null;
    $contact = $body->contact ?? null;
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/message/contact?key=' . trim($SessionName);
    //
    //
    $config = [
        "id" => trim($groupId) . '@g.us',
        "typeId" => "group",
        "vcard" => [
            "fullName" => $namecontact,
            "displayName" => $namecontact,
            "organization" => $organization ? $organization : $namecontact,
            "phoneNumber" => $contact
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
            sendWhatsapp::sendWhatsappMsgErroSendGoup($whatsnotify, $groupId);
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
            sendWhatsapp::sendWhatsappMsgErroSendGoup($whatsnotify, $groupId);
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
$router->post('/sendContactVcardGrupo', function ($params, $body) {
    sendContactVcardGrupo($params, $body);
});

$router->post('/sendcontactvcardgrupo', function ($params, $body) {
    sendContactVcardGrupo($params, $body);
});
