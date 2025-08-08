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
function settingGroup($params, $body)
{
    checkParameter::validateGroup($params, $body);
    checkToken::validateToken($params, $body);
    $SessionName = $body->SessionName ?? null;
    $groupId = $body->groupId ?? null;
    $action = $body->action ?? null;
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/group/settingsupdate?key=' . trim($SessionName);
    //
    if (!isset($body->action) || empty(trim($body->action))) {
        // Arquivo inválido: não é áudio ou extensão não reconhecida
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => http_response_code(),
                "message" => "É necessário informar 'action'. Corrija e tente novamente."
            ]
        ]);
        exit;
    }
    /*
    not_announcement = Todos enviam mensagem
    announcement = Somente admins enviam mensagem
    unlocked = Todos podem alterar foto e descrição do grupo
    locked = Apenas admins podem alterar foto e descrição do grupo
    */
    $config = [
        "id" => trim($groupId) . '@g.us',
        "action" => trim($action),
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
                "error" => false,
                "statusCode" => $httpStatus,
                "message" => $responseData->message
            ];
        }
    } else {
        if (!empty($responseData->data->error)) {
            $httpStatus = 401;
            $result = [
                "error" => true,
                "statusCode" => $httpStatus,
                "message" => $responseData->data->message
            ];
        } else {
            $result = [
                "error" => false,
                "statusCode" => $httpStatus,
                "message" => "Configuração alterada com sucesso."
            ];
        }
    }
    http_response_code($httpStatus);
    header('Content-type: application/json');
    print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/settingGroup', function ($params, $body) {
    settingGroup($params, $body);
});

$router->post('/settinggroup', function ($params, $body) {
    settingGroup($params, $body);
});
