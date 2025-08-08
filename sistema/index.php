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

require_once('../Router.php');

$router = new Router();

//$source = $_GET['source'] ?? 'connectzap';
$source = $_GET['source'] ?? 'lincoln';
//$source = $_GET['source'] ?? 'evolutionapi';

switch ($source) {
    case 'connectzap':

        break;
    case 'lincoln':
        // Instance - ✔️
        require_once('../routes/lincoln/Start.php');
        require_once('../routes/lincoln/Status.php');
        require_once('../routes/lincoln/Logout.php');
        //
        require_once('../routes/lincoln/restartToken.php');
        require_once('../routes/lincoln/restoreAllToken.php');
        require_once('../routes/lincoln/QRCode.php');
        require_once('../routes/lincoln/getCode.php');
        //
        // Basic Functions (usage) - ✔️
        require_once('../routes/lincoln/sendContactVcard.php');
        //
        require_once('../routes/lincoln/sendVoiceBase64.php');
        require_once('../routes/lincoln/sendVoiceFromBase64.php');
        //
        require_once('../routes/lincoln/sendText.php');
        require_once('../routes/lincoln/sendTextMult.php');
        require_once('../routes/lincoln/sendTextMassa.php');
        //
        require_once('../routes/lincoln/sendImageUrl.php');
        require_once('../routes/lincoln/sendImageBase64.php');
        require_once('../routes/lincoln/sendImageFromBase64.php');
        //
        require_once('../routes/lincoln/sendFileUrl.php');
        require_once('../routes/lincoln/sendFileBase64.php');
        require_once('../routes/lincoln/sendFileFromBase64.php');
        require_once('../routes/lincoln/sendFileBase64Mult.php');
        require_once('../routes/lincoln/sendFileBase64Massa.php');
        //
        require_once('../routes/lincoln/sendList.php');
        //
        require_once('../routes/lincoln/sendStickersUrl.php');
        require_once('../routes/lincoln/sendStickersBase64.php');
        require_once('../routes/lincoln/sendStickersFromBase64.php');
        //
        require_once('../routes/lincoln/sendPoll.php');
        //
        // Group Functions - ✔️
        require_once('../routes/lincoln/sendContactVcardGrupo.php');
        //
        require_once('../routes/lincoln/sendVoiceBase64Grupo.php');
        require_once('../routes/lincoln/sendVoiceFromBase64Grupo.php');
        //
        require_once('../routes/lincoln/sendTextGrupo.php');
        //
        require_once('../routes/lincoln/sendImageUrlGrupo.php');
        require_once('../routes/lincoln/sendImageBase64Grupo.php');
        require_once('../routes/lincoln/sendImageFromBase64Grupo.php');
        //
        require_once('../routes/lincoln/sendFileUrlGrupo.php');
        require_once('../routes/lincoln/sendFileBase64Grupo.php');
        require_once('../routes/lincoln/sendFileFromBase64Grupo.php');
        //
        require_once('../routes/lincoln/sendListGrupo.php');
        require_once('../routes/lincoln/sendPollGrupo.php');
        //
        // Group Options - ✔️
        require_once('../routes/lincoln/leaveGroup.php');
        require_once('../routes/lincoln/createGroup.php');
        require_once('../routes/lincoln/updateGroupTitle.php');
        require_once('../routes/lincoln/updateGroupDesc.php');
        require_once('../routes/lincoln/getGroupInviteLink.php');
        require_once('../routes/lincoln/getInfoGroup.php');
        require_once('../routes/lincoln/getGroupMembers.php');
        require_once('../routes/lincoln/settingGroup.php');
        require_once('../routes/lincoln/setPictureGroup.php');
        require_once('../routes/lincoln/addParticipant.php');
        require_once('../routes/lincoln/removeParticipant.php');
        require_once('../routes/lincoln/promoteParticipant.php');
        require_once('../routes/lincoln/demoteParticipant.php');
        //
        // Retrieving Data - ✔️
        require_once('../routes/lincoln/getAllContacts.php');
        require_once('../routes/lincoln/getAllGroups.php');
        require_once('../routes/lincoln/checkNumberStatus.php');
        //
        // Profile Functions - ✔️
        require_once('../routes/lincoln/getProfileStatus.php');
        require_once('../routes/lincoln/getProfilePicture.php');
        require_once('../routes/lincoln/setProfilePicture.php');
        require_once('../routes/lincoln/setProfileName.php');
        require_once('../routes/lincoln/updateMyStatusOnline.php');
        require_once('../routes/lincoln/setBlockUnblockContact.php');
        //
        // Getting webhook - ✔️
        require_once('../routes/lincoln/getConfigWh.php');
        require_once('../routes/lincoln/setConfigWh.php');


        //
        break;
    case 'evolutionapi':
        require_once('../routes/evolutionapi/Start.php');



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