<?php

require_once '../../include/DbHandlers/UserDbHandler.php';
require_once '../../include/DbHandlers/ActivityTypeDbHandler.php';

require '../../libs/Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
// User id from db - Global Variable
$user_id = NULL;

// ---------------------------------------------------------------------
// ------ web services -------------------------------------------------
// ---------------------------------------------------------------------

// Creating activity types in db
$app->post('/activityTypes', 'authenticate', 'createActivityTypes');

// Listing all activity types
$app->get('/activityTypes', 'authenticate', 'getAllActivityTypes');

// Updating all activityTypes included in payload
$app->put('/activityTypes', 'authenticate', 'updateActivityTypes');

// Deleting a set of activity types
$app->delete('/activityTypes', 'authenticate', 'deleteActivityTypes');


// ---------------------------------------------------------------------
// ------ Auxiliar methods ---------------------------------------------
// ---------------------------------------------------------------------

// Verifying required params posted or not
function verifyRequiredParams($required_fields){
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}
 
// Validating email address
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid : '.$email;
        echoResponse(400, $response);
        $app->stop();
    }
}
 
// Echoing json response to client
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}
         
// Adding Middle Layer to authenticate every request
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new UserDbHandler();
 
        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoResponse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user = $db->getUserId($api_key);
            if ($user != NULL)
                $user_id = $user["id"];
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoResponse(400, $response);
        $app->stop();
    }
}


// ------ Activity types auxiliar functions ----------------------------------
function createActivityTypes(){
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$itemsCreated = 0;

	$db = new ActivityTypeDbHandler();
	if (count($jsonData->data)==1){
		$id = $db->createActivityType($jsonData->data);
		if ($id != NULL){
			$itemsCreated = 1;
		}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $activityType) {
			$id = $db->createActivityType($activityType);
			if ($id != NULL) {
				$itemsCreated++;
			}
		}
	}

	$response["error"] = $itemsCreated==count($jsonData->data) ? false : true;
	$response["message"] = "Total activity types created: ".$itemsCreated;
	$response["data"]=$jsonData->data;
	echoResponse(201, $response);
}

function getAllActivityTypes() {
	global $user_id;
	$response = array();
	$db = new ActivityTypeDbHandler();
	$result = $db->getActivityTypes();
	
	$response["error"] = false;
	$response["data"] = array();
    while ($activityType = $result->fetch_assoc()) {
    	$tmp = array();
        $tmp["id"] = $activityType["id"];
        $tmp["name"] = $activityType["name"];
        $tmp["sportType_id"] = $activityType["sportType_id"];
        array_push($response["data"], $tmp);
    } 
	echoResponse(200, $response);
}

function updateActivityTypes(){
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$result = false;
	$itemsUpdated = 0;

	$db = new ActivityTypeDbHandler();
	if (count($jsonData->data)==1){
		$result = $db->updateActivityType($jsonData->data);
		if ($result) {
			$itemsUpdated = 1;
		}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $activityType) {
			$result = $db->updateActivityType($activityType);
			if ($result) {
				$itemsUpdated++;
			}
		}
	}

	$response["error"] = $itemsUpdated==count($jsonData->data) ? false : true;
	$response["message"] = "Total activity types updated: ".$itemsUpdated;
	$response["data"]=$jsonData->data;

	echoResponse(201, $response);
}

function deleteActivityTypes () {
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$result = false;
	$itemsDeleted = 0;

	$db = new ActivityTypeDbHandler();
	if (count($jsonData->data)==1){
		$result = $db->deleteActivityType($jsonData->data->id);
		if ($result) {
			$itemsDeleted = 1;
		}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $activityType) {
			$result = $db->deleteActivity($activityType->id);
			if ($result) {
				$itemsDeleted++;
			}
		}
	}

	$response["error"] = $itemsDeleted==count($jsonData->data) ? false : true;
	$response["message"] = "Total activity types deleted: ".$itemsDeleted;
	$response["data"]=$jsonData->data;

	echoResponse(201, $response);
}

$app->run();
?>

