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

function getAllContacts($params, $body) {
    checkParameter::SessionName($params, $body);
    checkToken::validateToken($params, $body);

    $SessionName = $body->SessionName ?? null;
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/misc/contacts?key=' . trim($SessionName);

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
        $contactsList = [];

        if (isset($responseData->data->contacts) && is_array($responseData->data->contacts)) {
            foreach ($responseData->data->contacts as $contact) {
                $contactsList[] = [
                    "wuid" => $contact->id ?? null,
                    "phone" => isset($contact->id) ? explode('@', $contact->id)[0] : null,
                    "name" => $contact->name ?? null
                ];
            }
        }

        $result = [
            "error" => false,
            "statusCode" => $httpStatus,
            "message" => "Lista de contato(s) obtida com sucesso.",
            "getAllContacts" => $contactsList
        ];
    }


    http_response_code($httpStatus);
    header('Content-type: application/json');
    echo json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/getAllContacts', function ($params, $body) {
    getAllContacts($params, $body);
});

$router->post('/getallcontacts', function ($params, $body) {
    getAllContacts($params, $body);
});
