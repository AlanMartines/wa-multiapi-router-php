<?php
//
require_once('../config.php');
require_once('../inc/functions.php');
require_once('../functions/request.php');
//
class updateStatistics
{
    public static function updateSuccess($SessionName)
    {
        //
        $adv = adv::conectar(Conexao::conectar());
        return $adv->updateStatisticsSuccess($SessionName);
        //
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
    public static function updateError($SessionName)
    {
        //
        $adv = adv::conectar(Conexao::conectar());
        return $adv->updateStatisticsError($SessionName);
        //
    }
    //
    //----------------------------------------------------------------------------------------------------//
    //
}

class sendWhatsapp
{
    public static function sendWhatsappMsgErroSend($to, $phonefull)
    {
        if ($to) {
            $telefoneLimpo = preg_replace('/\D/', '', $phonefull);
            $msg = "*Connect Zap*\nOlá! Não foi possível enviar sua mensagem para o número {$telefoneLimpo}.\nSua conta do WhatsApp não está conectada à plataforma Connect Zap.\n\nPor favor, acesse https://painel.connectzap.com.br, faça login e conclua a conexão da sua conta.\n";

            $toLimpo = preg_replace('/\D/', '', $to);
            $config = [
                "SessionName" => trim(APITOKEN),
                "phonefull" => trim($toLimpo),
                "msg" => $msg
            ];

            $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $result = Request::CurlRequest(APIURL . '/sistema/sendText', 'POST', $jsonConfig);
        }
    }

    public static function sendWhatsappMsgErroSendGoup($to, $groupId)
    {
        if ($to) {
            $msg = "*Connect Zap*\nOlá! Não foi possível enviar sua mensagem para o grupo {$groupId}.\nSua conta do WhatsApp não está conectada à plataforma Connect Zap.\n\nPor favor, acesse https://painel.connectzap.com.br, faça login e conclua a conexão da sua conta.\n";

            $toLimpo = preg_replace('/\D/', '', $to);
            $config = [
                "SessionName" => trim(APITOKEN),
                "phonefull" => trim($toLimpo),
                "msg" => $msg
            ];

            $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $result = Request::CurlRequest(APIURL . '/sistema/sendText', 'POST', $jsonConfig);
        }
    }

    public static function sendWhatsappMsgErroSendInfo($to)
    {
        if ($to) {
            $msg = "*Connect Zap*\nOlá! Não foi possível executar a ação solicitada.\nSua conta do WhatsApp não está conectada à plataforma Connect Zap.\n\nPor favor, acesse https://painel.connectzap.com.br, faça login e conclua a conexão da sua conta.\n";

            $toLimpo = preg_replace('/\D/', '', $to);
            $config = [
                "SessionName" => trim(APITOKEN),
                "phonefull" => trim($toLimpo),
                "msg" => $msg
            ];

            $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $result = Request::CurlRequest(APIURL . '/sistema/sendText', 'POST', $jsonConfig);
        }
    }
}
