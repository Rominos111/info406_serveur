<?php

//DEBUG : Affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//Importation configuration
include_once('../../config/core.php');

//Headers API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


//Importation automatique des classes
require_once("../../config/Autoloader.php");
Autoloader::register();

//Acquisition des données de la requete POST
$requestData = (!empty($_POST)) ? $_POST : $_GET;

//Traitement

if (!empty($requestData['token']) && !empty($requestData['name'])) {

    if (PermissionManager::getInstance($jwtConfig['key'])->canCreateProject($requestData['token'])) {//Verification permission
        
        if (strlen($requestData['name']) >= 3 && strlen($requestData['name']) <= 128) {

            if ((!empty($requestData['deadline']) && is_int(intval($requestData['deadline']))) || empty($requestData['deadline'])) {

                if ((!empty($requestData['status']) && ($requestData['status'] == ProjectStatus::PENDING ||
                                                        $requestData['status'] == ProjectStatus::ONGOING ||
                                                        $requestData['status'] == ProjectStatus::FINISHED ||
                                                        $requestData['status'] == ProjectStatus::CANCELED)) || empty($requestData['status'])) {

                    $description = (!empty($requestData['description'])) ? $requestData['description'] : "";
                    $deadline = (!empty($requestData['deadline'])) ? $requestData['deadline'] : 0;
                    $status = (!empty($requestData['status'])) ? $requestData['status'] : ProjectStatus::PENDING;

                    $project = new Project(null, $requestData['name'], $description, $deadline, $status, array());

                    try {

                        $project->createProject();
        
                        $response = new Response(ResponseEnum::SUCCESS_PROJECT_CREATED, array(), ResponseType::JSON);
                        $response->addContent(array("project" => array("id" => $project->getId(),
                                                                        "name" => $project->getName(),
                                                                        "description" => $project->getDescription(),
                                                                        "deadline" => $project->getDeadline(),
                                                                        "status" => $project->getStatus())));
                        $response->sendResponse();

                    } catch (UniqueDuplicationException $e) {

                        $response = new Response(ResponseEnum::ERROR_NAME_USED, array(), ResponseType::JSON);
                        $response->sendResponse();

                    }

                } else {
                    $response = new Response(ResponseEnum::ERROR_INVALID_ARGUMENT, array("invalid" => array("status")), ResponseType::JSON);
                    $response->sendResponse();
                }

            } else {
                $response = new Response(ResponseEnum::ERROR_INVALID_ARGUMENT, array("invalid" => array("deadline")), ResponseType::JSON);
                $response->sendResponse();
            }

        } else {
            $response = new Response(ResponseEnum::ERROR_INVALID_ARGUMENT, array("invalid" => array("name")), ResponseType::JSON);
            $response->sendResponse();
        }

    } else {
        $response = new Response(ResponseEnum::ERROR_ACCESS_DENIED, array(), ResponseType::JSON);
        $response->sendResponse();
    }

} else {

    $response = new Response(ResponseEnum::ERROR_MISSING_ARGUMENT, array(), ResponseType::JSON);
    $response->addMissingArguments(array("token", "name"), $requestData);
    $response->sendResponse();

}