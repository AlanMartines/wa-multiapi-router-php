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
function QRCode($params, $body)
{
    checkParameter::QRCode($params, $body);
    checkToken::validateToken($params, $body);
    $SessionName = $body->SessionName ?? null;
    $View = $body->View ?? null;
    $apiUrl = APIURLBND;
    $apiUrlRequest = $apiUrl . '/instance/qrbase64?key=' . trim($SessionName);
    //
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
                "message" => "Não foi possível obter o qrcode para leitura"
            ]
        ]);
        exit;
    }

    $responseData = json_decode($response, false);

    if (!empty($responseData->error)) {
        if (strpos($responseData->message, 'já conectado') !== false) {
            $result = [
                "error" => false,
                "statusCode" => $httpStatus,
                "message" => "Dispositivo já conectado"
            ];
        } else if (strpos($responseData->message, 'invalid key') !== false) {
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
        if ($View) {
            if (isset($responseData->qrcode) && !empty($responseData->qrcode)) {
                $qrcodeBase64 = $responseData->qrcode;
                // Remove o prefixo 'data:image/png;base64,' se existir
                $qrcodeBase64 = str_replace('data:image/png;base64,', '', $qrcodeBase64);
                // Converte para binário (imagem)
                $imageBuffer = base64_decode($qrcodeBase64);
                // Envia a imagem como resposta HTTP
                header('Content-Type: image/png');
                header('Content-Length: ' . strlen($imageBuffer));
                http_response_code(200);
                echo $imageBuffer;
                exit;
            } else {
                // Define o link da imagem externa
                $loadingImageUrl = '../public/imagens/loading.gif';
                // Faz o download da imagem para obter os dados binários
                $imageBuffer = file_get_contents($loadingImageUrl);
                // Envia a imagem como resposta HTTP
                header('Content-Type: image/gif');
                header('Content-Length: ' . strlen($imageBuffer));
                http_response_code(200);
                echo $imageBuffer;
                exit;
            }
        } else {
            if (isset($responseData->qrcode) && !empty($responseData->qrcode)) {
                $result = [
                    "error" => false,
                    "statusCode" => $httpStatus,
                    "qrcode" => $responseData->qrcode,
                    "message" => "Aguardando leitura do QR-Code"
                ];
            } else {
                $result = [
                    "error" => false,
                    "statusCode" => $httpStatus,
                    "qrcode" => null,
                    "message" => "Aguardando QR-Code ser gerado"
                ];
            }
        }
    }
    http_response_code($httpStatus);
    header('Content-type: application/json');
    print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
//
$router->post('/QRCode', function ($params, $body) {
    QRCode($params, $body);
});

$router->post('/qrCode', function ($params, $body) {
    QRCode($params, $body);
});
