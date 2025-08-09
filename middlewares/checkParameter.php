<?php
//
require_once('../config.php');
require_once('../inc/functions.php');
//
function isValidBase64($string)
{
    // Remove prefixo MIME se existir (ex: data:audio/mp3;base64,...)
    if (strpos($string, ',') !== false) {
        $string = explode(',', $string)[1];
    }

    // Remove espaços e quebras de linha
    $string = str_replace([' ', "\n", "\r", "\t"], '', $string);

    // Checa se é composta apenas por caracteres base64 válidos
    if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) {
        return false;
    }

    // Decodifica com verificação de erros
    $decoded = base64_decode($string, true);

    // Se falhar, não é base64
    if ($decoded === false) {
        return false;
    }

    // Garante que recodificando volta à string original (segurança extra)
    return base64_encode($decoded) === $string;
}
//
function getMimeTypeUrl($url)
{
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    curl_exec($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return ($httpCode === 200 || $httpCode === 201 || $httpCode === 202) ? $contentType : false;
}
//
function isImageUrl($url)
{
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    curl_exec($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return ($httpCode === 200 || $httpCode === 201 || $httpCode === 202 && stripos($contentType, 'image/') === 0) ? $contentType : false;
}
//
class checkNumber
{
    public static function validate($SessionName, $phonefull)
    {
        //
        $telefoneLimpo = preg_replace('/\D/', '', $phonefull);
        $apiUrl = APIURLBND;
        $apiUrlRequest = $apiUrl . '/misc/onwhatsapp?key=' . trim($SessionName);
        //
        $config = [
            "id" => trim($telefoneLimpo)
        ];
        // Codificar em JSON
        $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        //
        // Requisição externa via cURL
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrlRequest,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonConfig,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
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
                http_response_code($httpStatus);
                header('Content-type: application/json');
                print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                exit;
            } else {
                $result = [
                    "error" => true,
                    "statusCode" => $httpStatus,
                    "number" => trim($telefoneLimpo),
                    "message" => "O número informado não é um Whatsapp Valido"
                ];
            }
        } else {
            if (isset($responseData->data)) {
                $result = [
                    "error" => false,
                    "statusCode" => $httpStatus,
                    "number" => preg_replace('/\D/', '', $responseData->data),
                    "message" => "O número informado é um Whatsapp Valido"
                ];
            } else {
                $result = [
                    "error" => true,
                    "statusCode" => $httpStatus,
                    "number" => trim($telefoneLimpo),
                    "message" => "Não foi possível validar se é um Whatsapp Valido"
                ];
                http_response_code($httpStatus);
                header('Content-type: application/json');
                print json_encode(["Status" => $result], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                exit;
            }
        }
        http_response_code($httpStatus);
        header('Content-type: application/json');
        return json_encode(["Status" => $result]);
        //
    }
}
//
class checkToken
{
    public static function validateToken($params, $body)
    {
        //
        $adv = adv::conectar(Conexao::conectar());
        $validateToken = $adv->validateToken($body->SessionName);

        if ($validateToken) {
            $tokenToken    = $validateToken->token ?? null;
            $tokenEndDate  = $validateToken->datafinal ?? null;
            $tokenActive   = $validateToken->active ?? null;

            if (!$tokenActive || $tokenActive === 'false') {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    "Status" => [
                        "error" => true,
                        "statusCode" => http_response_code(),
                        "message" => "Sistema bloqueado para uso. Favor entrar em contato com o suporte"
                    ]
                ]);
                exit;
            }

            $todayDate = date('Y-m-d');
            if ($todayDate > $tokenEndDate) {
                http_response_code(408);
                header('Content-Type: application/json');
                echo json_encode([
                    "Status" => [
                        "error" => true,
                        "statusCode" => http_response_code(),
                        "message" => "Token vencido. Favor entrar em contato com o suporte"
                    ]
                ]);
                exit;
            }
            // Continua o fluxo (não tem "next" como no Express, então apenas continua)
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => http_response_code(),
                    "message" => "Token invalido, verifique e tente novamente"
                ]
            ]);
            exit;
        }
        //
    }

    public static function getUserConnected($params, $body)
    {
        //
        $adv = adv::conectar(Conexao::conectar());
        $validateToken = $adv->validateToken($body->SessionName);

        if ($validateToken) {
            $userconnected  = $validateToken->userconnected ?? null;
        } else {
            $userconnected  = $validateToken->userconnected ?? null;
        }
        //
        return $userconnected;
    }
}
//
class checkParameter
{
    public static function mimeType($params, $body)
    {
        $mimeMap = json_decode(file_get_contents(__DIR__ . '/extension_mime.json'), true);
        $fileName = $body->originalname ?? null;
        $fileUrl = $body->url ?? null;
        if ($fileName) {
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $mimeType = $mimeMap[$ext] ?? null;
        } else {
            $mimeType = getMimeTypeUrl($fileUrl);
        }
        return $mimeType;
        // Tudo ok, continua para a rota
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function mimeTypeFileName($params, $body)
    {
        $mimeMap = json_decode(file_get_contents(__DIR__ . '/extension_mime.json'), true);
        $fileName = $body->originalname ?? null;
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = $mimeMap[$ext] ?? null;
        return $mimeType;
        // Tudo ok, continua para a rota
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function mimeTypeUrlFile($params, $body)
    {
        $fileUrl = $body->url ?? null;
        $mimeType = getMimeTypeUrl($fileUrl);
        return $mimeType;
        // Tudo ok, continua para a rota
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function SessionName($params, $body)
    {
        if (!isset($body->SessionName) || empty($body->SessionName)) {
            http_response_code(404);
            header('Content-type: application/json');
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => http_response_code(),
                    "message" => "Todos os valores devem ser preenchidos, corrija e tente novamente."
                ]
            ]);
            exit;
        }
        // Tudo ok, continua para a rota
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function Start($params, $body)
    {
        header('Content-type: application/json');

        // Função de erro reutilizável
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // Validar SessionName
        if (!isset($body->SessionName) || empty(trim($body->SessionName))) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Validação do objeto webhook
        if (isset($body->webhook)) {
            $webhook = $body->webhook;

            if (!isset($webhook->webhook_cli) || empty(trim($webhook->webhook_cli))) {
                $error("webhook_cli é obrigatório dentro de webhook.");
            }

            // Flags opcionais — você pode tratar conforme desejar, se quiser valores booleanos válidos:
            $flags = ['wh_status', 'wh_message', 'wh_qrcode', 'wh_connect'];

            foreach ($flags as $flag) {
                if (isset($webhook->$flag) && !is_bool($webhook->$flag)) {
                    $error("A flag '$flag' deve ser um valor booleano (true/false).");
                }
            }
        }

        // Tudo certo — segue o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function QRCode($params, $body)
    {
        header('Content-type: application/json');

        // Verifica se SessionName foi enviado
        if (!isset($body->SessionName) || empty($body->SessionName)) {
            http_response_code(404);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => http_response_code(),
                    "message" => "SessionName é obrigatório. Corrija e tente novamente."
                ]
            ]);
            exit;
        }

        // Verifica se o campo View foi enviado e é booleano
        if (!isset($body->View) || !is_bool($body->View)) {
            http_response_code(400);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => http_response_code(),
                    "message" => "Campo 'View' é obrigatório e deve ser booleano (true/false)."
                ]
            ]);
            exit;
        }

        // Tudo ok, segue com o fluxo principal
        // Exemplo de resposta de sucesso
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function getCode($params, $body)
    {
        header('Content-type: application/json');

        // Função para facilitar a resposta de erro
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty($body->SessionName)) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Verifica se ambos estão vazios
        $semPhonefull = !isset($body->phonefull) || empty(trim($body->phonefull));
        $semGroupId   = !isset($body->groupId)   || empty(trim($body->groupId));

        // Se ambos estiverem ausentes, retorna erro específico
        if ($semPhonefull && $semGroupId) {
            $error("É necessário informar 'phonefull' ou 'groupId'. Corrija e tente novamente.");
        }

        // Se apenas um estiver ausente, e você quiser mensagem distinta:
        if ($semPhonefull && !$semGroupId) {
            // ok: está usando groupId
        } elseif (!$semPhonefull && $semGroupId) {
            // ok: está usando phonefull
        } elseif ($semPhonefull) {
            $error("É necessário informar 'phonefull'. Corrija e tente novamente.");
        } elseif ($semGroupId) {
            $error("É necessário informar 'groupId'. Corrija e tente novamente.");
        }

        // Tudo ok, segue com o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendContactVcard($params, $body)
    {
        header('Content-type: application/json');

        // Função para facilitar a resposta de erro
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty($body->SessionName)) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Verifica se ambos estão vazios
        $semPhonefull = !isset($body->phonefull) || empty(trim($body->phonefull));
        $semGroupId   = !isset($body->groupId)   || empty(trim($body->groupId));

        // Se ambos estiverem ausentes, retorna erro específico
        if ($semPhonefull && $semGroupId) {
            $error("É necessário informar 'phonefull' ou 'groupId'. Corrija e tente novamente.");
        }

        // Se apenas um estiver ausente, e você quiser mensagem distinta:
        if ($semPhonefull && !$semGroupId) {
            // ok: está usando groupId
        } elseif (!$semPhonefull && $semGroupId) {
            // ok: está usando phonefull
        } elseif ($semPhonefull) {
            $error("É necessário informar 'phonefull'. Corrija e tente novamente.");
        } elseif ($semGroupId) {
            $error("É necessário informar 'groupId'. Corrija e tente novamente.");
        }

        if (!isset($body->namecontact) || empty($body->namecontact)) {
            $error("Campo 'namecontact' é obrigatório. Corrija e tente novamente.");
        }

        /*
        if (!isset($body->organization) || empty($body->organization)) {
            $error("Campo 'organization' é obrigatório. Corrija e tente novamente.");
        }
        */

        if (!isset($body->contact) || empty($body->contact)) {
            $error("Campo 'contact' é obrigatório. Corrija e tente novamente.");
        }

        // Tudo ok — continua o fluxo
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendVoiceBase64($params, $body)
    {
        header('Content-type: application/json');

        // Função para facilitar a resposta de erro
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty($body->SessionName)) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Verifica se ambos estão vazios
        $semPhonefull = !isset($body->phonefull) || empty(trim($body->phonefull));
        $semGroupId   = !isset($body->groupId)   || empty(trim($body->groupId));

        // Se ambos estiverem ausentes, retorna erro específico
        if ($semPhonefull && $semGroupId) {
            $error("É necessário informar 'phonefull' ou 'groupId'. Corrija e tente novamente.");
        }

        // Se apenas um estiver ausente, e você quiser mensagem distinta:
        if ($semPhonefull && !$semGroupId) {
            // ok: está usando groupId
        } elseif (!$semPhonefull && $semGroupId) {
            // ok: está usando phonefull
        } elseif ($semPhonefull) {
            $error("É necessário informar 'phonefull'. Corrija e tente novamente.");
        } elseif ($semGroupId) {
            $error("É necessário informar 'groupId'. Corrija e tente novamente.");
        }

        if (!isset($body->base64) || empty($body->base64)) {
            $error("Campo 'base64' com o conteúdo de áudio é obrigatório.");
        }

        if (!isset($body->originalname) || empty($body->originalname)) {
            $error("Campo 'originalname' é obrigatório (ex: 'record_voice.mp3').");
        }

        $mimeMap = json_decode(file_get_contents(__DIR__ . '/extension_mime.json'), true);
        $ext = strtolower(pathinfo($body->originalname, PATHINFO_EXTENSION));
        $mimeType = $mimeMap[$ext] ?? null;

        if (!$mimeType || !str_starts_with($mimeType, 'audio/')) {
            // Arquivo inválido: não é áudio ou extensão não reconhecida
            $error("Arquivo selecionado não permitido, apenas arquivo de audio", 400);
        }

        if (!isValidBase64($body->base64)) {
            $error("Base64 informado é inválido", 400);
        }

        // Tudo validado com sucesso — pode continuar o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendText($params, $body)
    {
        header('Content-type: application/json');

        // Função de resposta de erro
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty(trim($body->SessionName))) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Verifica se ambos estão vazios
        $semPhonefull = !isset($body->phonefull) || empty(trim($body->phonefull));
        $semGroupId   = !isset($body->groupId)   || empty(trim($body->groupId));

        // Se ambos estiverem ausentes, retorna erro específico
        if ($semPhonefull && $semGroupId) {
            $error("É necessário informar 'phonefull' ou 'groupId'. Corrija e tente novamente.");
        }

        // Se apenas um estiver ausente, e você quiser mensagem distinta:
        if ($semPhonefull && !$semGroupId) {
            // ok: está usando groupId
        } elseif (!$semPhonefull && $semGroupId) {
            // ok: está usando phonefull
        } elseif ($semPhonefull) {
            $error("É necessário informar 'phonefull'. Corrija e tente novamente.");
        } elseif ($semGroupId) {
            $error("É necessário informar 'groupId'. Corrija e tente novamente.");
        }

        if (!isset($body->msg) || empty(trim($body->msg))) {
            $error("Campo 'msg' é obrigatório. Corrija e tente novamente.");
        }

        // Tudo validado com sucesso — pode continuar o fluxo principal

    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendTextMult($params, $body)
    {
        header('Content-type: application/json');

        // Função de resposta de erro
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty(trim($body->SessionName))) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Validação de phonefull (array de números)
        if (!isset($body->phonefull) || !is_array($body->phonefull) || count($body->phonefull) === 0) {
            $error("O campo 'phonefull' é obrigatório e deve conter um ou mais números no formato de array.");
        }

        foreach ($body->phonefull as $index => $numero) {
            if (!is_string($numero) || empty(trim($numero))) {
                $error("O número no índice #{$index} do array 'phonefull' está vazio ou inválido. Corrija e tente novamente.");
            }
        }

        // Validação da mensagem
        if (!isset($body->msg) || !is_string($body->msg) || empty(trim($body->msg))) {
            $error("Campo 'msg' é obrigatório e deve ser uma string. Corrija e tente novamente.");
        }

        // Tudo validado com sucesso — pode continuar o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendImageUrl($params, $body)
    {
        header('Content-type: application/json');

        // Função de resposta de erro
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty(trim($body->SessionName))) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Verifica se ambos estão vazios
        $semPhonefull = !isset($body->phonefull) || empty(trim($body->phonefull));
        $semGroupId   = !isset($body->groupId)   || empty(trim($body->groupId));

        if ($semPhonefull && $semGroupId) {
            $error("É necessário informar 'phonefull' ou 'groupId'. Corrija e tente novamente.");
        }

        if ($semPhonefull && !$semGroupId) {
            // ok
        } elseif (!$semPhonefull && $semGroupId) {
            // ok
        } elseif ($semPhonefull) {
            $error("É necessário informar 'phonefull'. Corrija e tente novamente.");
        } elseif ($semGroupId) {
            $error("É necessário informar 'groupId'. Corrija e tente novamente.");
        }

        // Valida URL
        if (!isset($body->url) || empty(trim($body->url))) {
            $error("Campo 'url' da imagem é obrigatório.");
        }

        if (!filter_var($body->url, FILTER_VALIDATE_URL)) {
            $error("Campo 'url' não é um link válido.");
        }

        // Verifica se a URL aponta para uma imagem (por headers HTTP)
        $contentType = isImageUrl($body->url);
        if (!$contentType) {
            $error("A URL fornecida não contém uma imagem válida ou não pôde ser acessada.");
        }

        /*
        // Validação do caption não é obrigatória, mas se quiser validar o tamanho:
        if (isset($body->caption) && strlen(trim($body->caption)) > 1024) {
            $error("Legenda ('caption') excede o limite de 1024 caracteres.");
        }
        */

        // Tudo validado com sucesso — pode continuar o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendImageBase64($params, $body)
    {
        header('Content-type: application/json');

        // Função para facilitar a resposta de erro
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty($body->SessionName)) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Verifica se ambos estão vazios
        $semPhonefull = !isset($body->phonefull) || empty(trim($body->phonefull));
        $semGroupId   = !isset($body->groupId)   || empty(trim($body->groupId));

        // Se ambos estiverem ausentes, retorna erro específico
        if ($semPhonefull && $semGroupId) {
            $error("É necessário informar 'phonefull' ou 'groupId'. Corrija e tente novamente.");
        }

        // Se apenas um estiver ausente, e você quiser mensagem distinta:
        if ($semPhonefull && !$semGroupId) {
            // ok: está usando groupId
        } elseif (!$semPhonefull && $semGroupId) {
            // ok: está usando phonefull
        } elseif ($semPhonefull) {
            $error("É necessário informar 'phonefull'. Corrija e tente novamente.");
        } elseif ($semGroupId) {
            $error("É necessário informar 'groupId'. Corrija e tente novamente.");
        }

        if (!isset($body->base64) || empty($body->base64)) {
            $error("Campo 'base64' com o conteúdo de áudio é obrigatório.");
        }

        if (!isset($body->originalname) || empty($body->originalname)) {
            $error("Campo 'originalname' é obrigatório (ex: 'onlinepngtools.png').");
        }

        $mimeMap = json_decode(file_get_contents(__DIR__ . '/extension_mime.json'), true);
        $ext = strtolower(pathinfo($body->originalname, PATHINFO_EXTENSION));
        $mimeType = $mimeMap[$ext] ?? null;

        if (!$mimeType || !str_starts_with($mimeType, 'image/')) {
            // Arquivo inválido: não é áudio ou extensão não reconhecida
            $error("Arquivo selecionado não permitido, apenas arquivo de image", 400);
        }

        if (!isValidBase64($body->base64)) {
            $error("Base64 informado é inválido", 400);
        }

        // Tudo validado com sucesso — pode continuar o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendFileUrl($params, $body)
    {
        header('Content-type: application/json');

        // Função de resposta de erro
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty(trim($body->SessionName))) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Verifica se ambos estão vazios
        $semPhonefull = !isset($body->phonefull) || empty(trim($body->phonefull));
        $semGroupId   = !isset($body->groupId)   || empty(trim($body->groupId));

        if ($semPhonefull && $semGroupId) {
            $error("É necessário informar 'phonefull' ou 'groupId'. Corrija e tente novamente.");
        }

        if ($semPhonefull && !$semGroupId) {
            // ok
        } elseif (!$semPhonefull && $semGroupId) {
            // ok
        } elseif ($semPhonefull) {
            $error("É necessário informar 'phonefull'. Corrija e tente novamente.");
        } elseif ($semGroupId) {
            $error("É necessário informar 'groupId'. Corrija e tente novamente.");
        }

        // Valida URL
        if (!isset($body->url) || empty(trim($body->url))) {
            $error("Campo 'url' do documento é obrigatório.");
        }

        if (!filter_var($body->url, FILTER_VALIDATE_URL)) {
            $error("Campo 'url' não é um link válido.");
        }

        /*
        // Validação do caption não é obrigatória, mas se quiser validar o tamanho:
        if (isset($body->caption) && strlen(trim($body->caption)) > 1024) {
            $error("Legenda ('caption') excede o limite de 1024 caracteres.");
        }
        */

        // Tudo validado com sucesso — pode continuar o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendFileBase64($params, $body)
    {
        header('Content-type: application/json');

        // Função para facilitar a resposta de erro
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty($body->SessionName)) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Verifica se ambos estão vazios
        $semPhonefull = !isset($body->phonefull) || empty(trim($body->phonefull));
        $semGroupId   = !isset($body->groupId)   || empty(trim($body->groupId));

        // Se ambos estiverem ausentes, retorna erro específico
        if ($semPhonefull && $semGroupId) {
            $error("É necessário informar 'phonefull' ou 'groupId'. Corrija e tente novamente.");
        }

        // Se apenas um estiver ausente, e você quiser mensagem distinta:
        if ($semPhonefull && !$semGroupId) {
            // ok: está usando groupId
        } elseif (!$semPhonefull && $semGroupId) {
            // ok: está usando phonefull
        } elseif ($semPhonefull) {
            $error("É necessário informar 'phonefull'. Corrija e tente novamente.");
        } elseif ($semGroupId) {
            $error("É necessário informar 'groupId'. Corrija e tente novamente.");
        }

        if (!isset($body->base64) || empty($body->base64)) {
            $error("Campo 'base64' com o conteúdo de áudio é obrigatório.");
        }

        if (!isset($body->originalname) || empty($body->originalname)) {
            $error("Campo 'originalname' é obrigatório (ex: 'onlinepngtools.png').");
        }

        if (!isValidBase64($body->base64)) {
            $error("Base64 informado é inválido", 400);
        }

        // Tudo validado com sucesso — pode continuar o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendFileBase64Mult($params, $body)
    {
        header('Content-type: application/json');

        // Função para facilitar a resposta de erro
        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty($body->SessionName)) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Validação de phonefull (array de números)
        if (!isset($body->phonefull) || !is_array($body->phonefull) || count($body->phonefull) === 0) {
            $error("O campo 'phonefull' é obrigatório e deve conter um ou mais números no formato de array.");
        }

        if (!isset($body->base64) || empty($body->base64)) {
            $error("Campo 'base64' com o conteúdo de áudio é obrigatório.");
        }

        if (!isset($body->originalname) || empty($body->originalname)) {
            $error("Campo 'originalname' é obrigatório (ex: 'onlinepngtools.png').");
        }

        if (!isValidBase64($body->base64)) {
            $error("Base64 informado é inválido", 400);
        }

        // Tudo validado com sucesso — pode continuar o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendList($params, $body)
    {
        header('Content-type: application/json');

        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // -------------------------------
        // Validações obrigatórias
        // -------------------------------

        if (!isset($body->SessionName) || empty(trim($body->SessionName))) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Verifica se ambos estão vazios
        $semPhonefull = !isset($body->phonefull) || empty(trim($body->phonefull));
        $semGroupId   = !isset($body->groupId)   || empty(trim($body->groupId));

        if ($semPhonefull && $semGroupId) {
            $error("É necessário informar 'phonefull' ou 'groupId'. Corrija e tente novamente.");
        }

        // Validação da estrutura listMessage
        if (!isset($body->listMessage)) {
            $error("O campo 'listMessage' é obrigatório.");
        }

        $list = $body->listMessage;
        /*
        // Campos obrigatórios do tipo string
        $stringFields = ['title', 'description', 'buttonText', 'listType'];
        foreach ($stringFields as $field) {
            if (!isset($list->$field) || !is_string($list->$field) || empty(trim($list->$field))) {
                $error("O campo '$field' é obrigatório e deve ser uma string dentro de 'listMessage'.");
            }
        }

        // Validação específica para sections
        if (!isset($list->sections) || !is_array($list->sections) || count($list->sections) === 0) {
            $error("O campo 'sections' deve ser um array com pelo menos uma seção.");
        }

        // Validação das seções e linhas
        foreach ($list->sections as $index => $section) {
            if (!isset($section->title) || !is_string($section->title) || empty(trim($section->title))) {
                $error("A seção #" . ($index + 1) . " está sem o campo 'title'.");
            }

            if (!isset($section->rows) || !is_array($section->rows) || count($section->rows) === 0) {
                $error("A seção '" . $section->title . "' deve conter um array 'rows' com pelo menos um item.");
            }

            foreach ($section->rows as $i => $row) {
                if (
                    !isset($row->title) || !is_string($row->title) || empty(trim($row->title)) ||
                    !isset($row->description) || !is_string($row->description) || empty(trim($row->description)) ||
                    !isset($row->rowId) || !is_string($row->rowId) || empty(trim($row->rowId))
                ) {
                    $error("Linha #" . ($i + 1) . " da seção '" . $section->title . "' está incompleta. 'title', 'description' e 'rowId' são obrigatórios.");
                }
            }
        }
        */
        // Tudo validado com sucesso — pode continuar o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function sendPoll($params, $body)
    {
        header('Content-type: application/json');

        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // SessionName
        if (!isset($body->SessionName) || !is_string($body->SessionName) || empty(trim($body->SessionName))) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // Verificação de destino: phonefull ou groupId
        $temPhone = isset($body->phonefull) && is_string($body->phonefull) && !empty(trim($body->phonefull));
        $temGrupo = isset($body->groupId) && is_string($body->groupId) && !empty(trim($body->groupId));

        if (!$temPhone && !$temGrupo) {
            $error("É necessário informar 'phonefull' ou 'groupId'. Corrija e tente novamente.");
        }

        // name (texto principal da enquete)
        if (!isset($body->name) || !is_string($body->name) || empty(trim($body->name))) {
            $error("O campo 'name' é obrigatório e deve ser uma string.");
        }

        // selectableCount (quantidade de opções selecionáveis)
        if (!isset($body->selectableCount) || !is_int($body->selectableCount) || $body->selectableCount < 1) {
            $error("O campo 'selectableCount' é obrigatório e deve ser um número inteiro maior ou igual a 1.");
        }

        // values (array de opções da enquete)
        if (!isset($body->values) || !is_array($body->values) || count($body->values) === 0) {
            $error("O campo 'values' é obrigatório e deve ser um array com pelo menos uma opção.");
        }

        foreach ($body->values as $index => $value) {
            if (!is_string($value) || empty(trim($value))) {
                $error("A opção #" . ($index + 1) . " em 'values' deve ser uma string não vazia.");
            }
        }

        // Tudo validado com sucesso — continua o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function validateGroup($params, $body)
    {
        header('Content-type: application/json');

        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // SessionName
        if (!isset($body->SessionName) || !is_string($body->SessionName) || empty(trim($body->SessionName))) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // name (texto principal da enquete)
        if (!isset($body->groupId) || !is_string($body->groupId) || empty(trim($body->groupId))) {
            $error("O campo 'groupId' é obrigatório e deve ser uma string.");
        }

        // Tudo validado com sucesso — continua o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function createGroup($params, $body)
    {
        header('Content-type: application/json');

        $error = function ($message, $code = 400) {
            http_response_code($code);
            echo json_encode([
                "Status" => [
                    "error" => true,
                    "statusCode" => $code,
                    "message" => $message
                ]
            ]);
            exit;
        };

        // SessionName
        if (!isset($body->SessionName) || !is_string($body->SessionName) || empty(trim($body->SessionName))) {
            $error("SessionName é obrigatório. Corrija e tente novamente.", 404);
        }

        // title
        if (!isset($body->title) || !is_string($body->title) || empty(trim($body->title))) {
            $error("O campo 'title' é obrigatório e deve ser uma string.");
        }
        /*
        // description
        if (!isset($body->description) || !is_string($body->description) || empty(trim($body->description))) {
            $error("O campo 'description' é obrigatório e deve ser uma string.");
        }
        */
        // participants
        if (!isset($body->participants) || !is_array($body->participants) || count($body->participants) === 0) {
            $error("O campo 'participants' é obrigatório e deve ser um array com pelo menos um número.");
        }

        // Valida cada participante
        foreach ($body->participants as $participant) {
            if (!is_string($participant) || empty(trim($participant))) {
                $error("Todos os participantes devem ser strings não vazias (ex: número com DDI e DDD).");
            }
        }

        // Tudo validado com sucesso — continua o fluxo principal
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
}
