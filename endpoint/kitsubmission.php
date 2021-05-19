<?php
namespace Stanford\ProjCaFacts2;
/** @var \Stanford\ProjCaFacts2\ProjCaFacts2 $module */

//https://www.ca-facts.org/
header("Access-Control-Allow-Origin: *");

$module->emDebug("Incoming Request - Get Blood Collection Survey", $_POST);
$phpinput_json = file_get_contents('php://input');
if(!empty($phpinput_json)){
    $post = json_decode($phpinput_json, true);
    if(is_array($post)){
        $_POST = $post;
    }
}

$household_id     = isset($_POST[$module::HOUSEHOLD_ID])     ? strtoupper(trim(filter_var($_POST[$module::HOUSEHOLD_ID], FILTER_SANITIZE_STRING))) : NULL ;
$participant_id   = isset($_POST[$module::SURVEY_ID])   ? trim(filter_var($_POST[$module::SURVEY_ID], FILTER_SANITIZE_STRING)) : NULL ;
$valid            = (is_null($household_id) || is_null($participant_id)) ? false : true;

try{
    if($valid){
        $module->KitSubmitHandler($household_id, $participant_id);
    }else{
        $module->returnError("Invalid Request Parameters - check your syntax", $_REQUEST);
    }
}catch(\Exception $e){
    $module->emError($e->getMessage(), $_POST);
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));

    \REDCap::logEvent($module->getModuleName() . " : Get Blood Collection Survey ");
}
