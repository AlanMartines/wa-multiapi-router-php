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

require_once('../config.php');
require_once('../middlewares/checkParameter.php');
require_once('../functions/request.php');

function getAllGroups($params, $body) {
    checkToken::validateToken($params, $body);

    $SessionName = $body->SessionName ?? null;
    $getParticipants = $body->getParticipants ?? false; // default false
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/group/getallgroups?key=' . trim($SessionName);

    if (!isset($body->SessionName) || empty($body->SessionName)) {
        http_response_code(400);
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => 400,
                "message" => "Campo 'SessionName' é obrigatório."
            ]
        ]);
        exit;
    }
    /*
    if (!isset($body->getParticipants) || !is_bool($body->getParticipants)) {
        http_response_code(400);
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => 400,
                "message" => "Campo 'getParticipants' é obrigatório e deve ser booleano (true/false)."
            ]
        ]);
        exit;
    }
    */
    $result = Request::CurlRequest($apiUrlRequest, 'GET', null);
    $response = $result['response'];
    $httpStatus = $result['httpStatus'];
    $error = $result['error'];

    if ($response === false) {
        error_log("Erro na requisição: $error");
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => 400,
                "message" => "Não foi possível executar a ação"
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
        $groupsData = [];
        $resData = json_decode($response, true);
        if (isset($resData['data']) && is_array($resData['data'])) {
            foreach ($resData['data'] as $groupId => $groupInfo) {
                $groupEntry = [
                    "wuid" => explode('@', $groupInfo['id'])[0],
                    "gpname" => $groupInfo['subject'] ?? null,
                    "size" => $groupInfo['size'] ?? 0,
                    "creation" => isset($groupInfo['creation']) ? date('Y-m-d H:i:s', $groupInfo['creation']) : null,
                    "desc" => $groupInfo['desc'] ?? null,
                    "restrict" => $groupInfo['restrict'] ?? false,
                    "announce" => $groupInfo['announce'] ?? false
                ];

                if ($getParticipants && isset($groupInfo['participants'])) {
                    $groupEntry['participants'] = array_map(function($p) {
                        return [
                            'id' => $p['id'],
                            'phone' => explode('@', $p['id'])[0],
                            'admin' => $p['admin'] ?? null
                        ];
                    }, $groupInfo['participants']);
                }

                $groupsData[] = $groupEntry;
            }
        }

        $result = [
            "error" => false,
            "statusCode" => $httpStatus,
            "message" => "Lista de grupo(s) obtida com sucesso.",
            "getAllGroups" => $groupsData
        ];
    }

    http_response_code($httpStatus);
    header('Content-type: application/json');
    echo json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/getAllGroups', function ($params, $body) {
    getAllGroups($params, $body);
});

$router->post('/getallgroups', function ($params, $body) {
    getAllGroups($params, $body);
});