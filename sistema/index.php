<?php
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
require_once('../Router.php');

$router = new Router();

$source = SOURCES;

switch ($source) {
    case 'evolutionapi':
        // Instance - ✔️
        require_once('../routes/evolutionapi/Start.php');
        require_once('../routes/evolutionapi/Status.php');
        require_once('../routes/evolutionapi/Logout.php');
        //
        require_once('../routes/evolutionapi/restartToken.php');
        require_once('../routes/evolutionapi/restoreAllToken.php');
        require_once('../routes/evolutionapi/QRCode.php');
        require_once('../routes/evolutionapi/getCode.php');
        //
        // Basic Functions (usage) - ✔️
        require_once('../routes/evolutionapi/sendContactVcard.php');
        //
        require_once('../routes/evolutionapi/sendVoiceBase64.php');
        require_once('../routes/evolutionapi/sendVoiceFromBase64.php');
        //
        require_once('../routes/evolutionapi/sendText.php');
        require_once('../routes/evolutionapi/sendTextMult.php');
        require_once('../routes/evolutionapi/sendTextMassa.php');
        //
        require_once('../routes/evolutionapi/sendImageUrl.php');
        require_once('../routes/evolutionapi/sendImageBase64.php');
        require_once('../routes/evolutionapi/sendImageFromBase64.php');
        //
        require_once('../routes/evolutionapi/sendFileUrl.php');
        require_once('../routes/evolutionapi/sendFileBase64.php');
        require_once('../routes/evolutionapi/sendFileFromBase64.php');
        require_once('../routes/evolutionapi/sendFileBase64Mult.php');
        require_once('../routes/evolutionapi/sendFileBase64Massa.php');
        //
        require_once('../routes/evolutionapi/sendList.php');
        //
        require_once('../routes/evolutionapi/sendStickersUrl.php');
        require_once('../routes/evolutionapi/sendStickersBase64.php');
        require_once('../routes/evolutionapi/sendStickersFromBase64.php');
        //
        require_once('../routes/evolutionapi/sendPoll.php');
        //
        // Group Functions - ✔️
        require_once('../routes/evolutionapi/sendContactVcardGrupo.php');
        //
        require_once('../routes/evolutionapi/sendVoiceBase64Grupo.php');
        require_once('../routes/evolutionapi/sendVoiceFromBase64Grupo.php');
        //
        require_once('../routes/evolutionapi/sendTextGrupo.php');
        //
        require_once('../routes/evolutionapi/sendImageUrlGrupo.php');
        require_once('../routes/evolutionapi/sendImageBase64Grupo.php');
        require_once('../routes/evolutionapi/sendImageFromBase64Grupo.php');
        //
        require_once('../routes/evolutionapi/sendFileUrlGrupo.php');
        require_once('../routes/evolutionapi/sendFileBase64Grupo.php');
        require_once('../routes/evolutionapi/sendFileFromBase64Grupo.php');
        //
        require_once('../routes/evolutionapi/sendListGrupo.php');
        require_once('../routes/evolutionapi/sendPollGrupo.php');
        //
        // Group Options - ✔️
        require_once('../routes/evolutionapi/leaveGroup.php');
        require_once('../routes/evolutionapi/createGroup.php');
        require_once('../routes/evolutionapi/updateGroupTitle.php');
        require_once('../routes/evolutionapi/updateGroupDesc.php');
        require_once('../routes/evolutionapi/getGroupInviteLink.php');
        require_once('../routes/evolutionapi/getInfoGroup.php');
        require_once('../routes/evolutionapi/getGroupMembers.php');
        require_once('../routes/evolutionapi/settingGroup.php');
        require_once('../routes/evolutionapi/setPictureGroup.php');
        require_once('../routes/evolutionapi/addParticipant.php');
        require_once('../routes/evolutionapi/removeParticipant.php');
        require_once('../routes/evolutionapi/promoteParticipant.php');
        require_once('../routes/evolutionapi/demoteParticipant.php');
        //
        // Retrieving Data - ✔️
        require_once('../routes/evolutionapi/getAllContacts.php');
        require_once('../routes/evolutionapi/getAllGroups.php');
        require_once('../routes/evolutionapi/checkNumberStatus.php');
        //
        // Profile Functions - ✔️
        require_once('../routes/evolutionapi/getProfileStatus.php');
        require_once('../routes/evolutionapi/getProfilePicture.php');
        require_once('../routes/evolutionapi/setProfilePicture.php');
        require_once('../routes/evolutionapi/setProfileName.php');
        require_once('../routes/evolutionapi/updateMyStatusOnline.php');
        require_once('../routes/evolutionapi/setBlockUnblockContact.php');
        //
        // Getting webhook - ✔️
        require_once('../routes/evolutionapi/getConfigWh.php');
        require_once('../routes/evolutionapi/setConfigWh.php');
        //
        break;
    default:
        http_response_code(400);
        echo json_encode([
            "Status" => [
                "error" => true,
                "statusCode" => http_response_code(),
                'message' => "Não foi possivel executar a ação, verifique a url informada."
            ]
        ]);
        exit;
}

$router->run();