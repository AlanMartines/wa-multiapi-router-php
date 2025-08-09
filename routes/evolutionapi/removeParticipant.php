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
function removeParticipant($params, $body)
{
    checkParameter::validateGroup($params, $body);
    checkToken::validateToken($params, $body);
    $SessionName = $body->SessionName ?? null;
    $groupId = $body->groupId ?? null;
    $participants = $body->participants ?? null;
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/group/removeuser?key=' . trim($SessionName);
    //
    // Validação específica para sections
    if (!isset($body->participants) || !is_array($body->participants) || count($body->participants) === 0) {
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => http_response_code(),
                "message" => "O campo 'participants' deve ser um array com pelo menos um numero."
            ]
        ]);
        exit;
    }
    //
    $config = [
        "id" => trim($groupId) . '@g.us',
        "users" => $participants
    ];
    // Codificar em JSON
    $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
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
                "message" => $responseData->message
            ];
        }
    } else {
        $resData = json_decode($response, true);
        $results = [];
        if (isset($resData['data'])) {
            foreach ($resData['data'] as $item) {
                $jidRaw = $item['jid'] ?? '';
                $jid = preg_replace('/\D/', '', $jidRaw);

                if ($item['status'] !== "200" && $item['status'] !== "201" && $item['status'] !== "202") {
                    $results[] = [
                        "error" => true,
                        "participant" => $jid,
                        "message" => "Não foi possível remover participante do grupo"
                    ];
                } else {
                    $results[] = [
                        "error" => false,
                        "participant" => $jid,
                        "message" => "Participante removido do grupo com sucesso"
                    ];
                }
            }
        } else {
            $results[] = [
                "error" => true,
                "participant" => null,
                "message" => "Não foi possível remover participante do grupo"
            ];
        }
    }
    http_response_code($httpStatus);
    header('Content-type: application/json');
    $res = [
        "error" => true,
        "statusCode" => http_response_code(),
        "results" => $results
    ];
    print json_encode(["Status" => $res], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/removeParticipant', function ($params, $body) {
    removeParticipant($params, $body);
});

$router->post('/removeparticipant', function ($params, $body) {
    removeParticipant($params, $body);
});
