<?php
//
require_once('../vendor/autoload.php');
//
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
//
class Request   
{
    public static function CurlRequest($apiUrlRequest, $method, $config = null)
    {
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
         CURLOPT_CUSTOMREQUEST => $method,
         CURLOPT_POSTFIELDS => $config,
         CURLOPT_HTTPHEADER => array(
             'Content-Type: application/json',
             'Accept: application/json'
         ),
     ));
 
     $response = curl_exec($curl);
     $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
     $error = curl_error($curl);
     curl_close($curl);
 
     return [
         'response' => $response,
         'httpStatus' => $httpStatus,
         'error' => $error,
         'meta' => [
             'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '',
             'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
             'requestUri' => $_SERVER['REQUEST_URI'] ?? '',
             'requestMethod' => $_SERVER['REQUEST_METHOD'] ?? '',
             'timestamp' => date('c')
         ]
     ];
    }

    public static function GuzzleRequest($apiUrlRequest, $method, $config = null)
    {
        $client = new Client([
            'verify' => false // Desabilita verificação SSL (como no cURL que você usava)
        ]);
    
        try {
            $response = $client->request($method, $apiUrlRequest, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'json' => $config,
                'timeout' => 30
            ]);
    
            $body = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();
    
            return [
                'response' => $body,
                'httpStatus' => $statusCode,
                'error' => null
            ];
    
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $body = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
    
            return [
                'response' => $body,
                'httpStatus' => $statusCode,
                'error' => $errorMessage
            ];
        }
    }

    public static function HttpRequest2($apiUrlRequest, $method, $config = null)
    {
        try {
            $request = new HTTP_Request2();
            $request->setUrl($apiUrlRequest);
    
            // Define o método corretamente baseado no tipo passado
            switch (strtoupper($method)) {
                case 'POST':
                    $request->setMethod(HTTP_Request2::METHOD_POST);
                    break;
                case 'GET':
                    $request->setMethod(HTTP_Request2::METHOD_GET);
                    break;
                case 'PUT':
                    $request->setMethod(HTTP_Request2::METHOD_PUT);
                    break;
                case 'DELETE':
                    $request->setMethod(HTTP_Request2::METHOD_DELETE);
                    break;
                case 'OPTIONS':
                    $request->setMethod(HTTP_Request2::METHOD_OPTIONS);
                    break;
                case 'HEAD':
                    $request->setMethod(HTTP_Request2::METHOD_HEAD);
                    break;
                default:
                    error_log('Método HTTP inválido fornecido: ' . $method);
            }
    
            $request->setHeader([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);
    
            $request->setConfig([
                'ssl_verify_peer' => false,
                'ssl_verify_host' => false,
                'follow_redirects' => true,
                'timeout' => 30
            ]);
    
            // Se tiver corpo, adiciona no body
            if (!empty($config)) {
                $request->setBody(json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            }
    
            $response = $request->send();
    
            return [
                'response' => $response->getBody(),
                'httpStatus' => $response->getStatus(),
                'error' => null
            ];
        } catch (HTTP_Request2_Exception $e) {
            return [
                'response' => null,
                'httpStatus' => 500,
                'error' => $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'response' => null,
                'httpStatus' => 400,
                'error' => $e->getMessage()
            ];
        }
    }
}
//