<?php

require_once '../../include/DbHandlers/UserDbHandler.php';
require_once '../../include/DbHandlers/MaterialDbHandler.php';
require '../../libs/Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
// User id from db - Global Variable
$user_id = NULL;

// ---------------------------------------------------------------------
// ------ web services -------------------------------------------------
// ---------------------------------------------------------------------

// Creating a new material in db
$app->post('/materials', 'authenticate', 'createMaterials');

// Listing all materials
$app->get('/materials', 'authenticate', 'getAllMaterials');

// Updating all materials included in payload
$app->put('/materials', 'authenticate', 'updateMaterials');

// Deleting a set of materials
$app->delete('/materials', 'authenticate', 'deleteMaterials');


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


// ------ Materials auxiliar functions ----------------------------------
function createMaterials(){
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$itemsCreated = 0;

	$db = new MaterialDbHandler();
	if (count($jsonData->data)==1){
		$id = $db->createMaterial($jsonData->data);
		if ($id != NULL){
			$itemsCreated = 1;
		}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $material) {
			$id = $db->createMaterial($material);
			if ($id != NULL) {
				$itemsCreated++;
			}
		}
	}

	$response["error"] = $itemsCreated==count($jsonData->data) ? false : true;
	$response["message"] = "Total materials created: ".$itemsCreated;
	$response["data"]=$jsonData->data;
	$errorCode = 201;
	if ($response["error"]) {
		$errorCode = 500;
	}
	echoResponse($errorCode, $response);
}

function getAllMaterials() {
	global $user_id;
	$response = array();
	$db = new MaterialDbHandler();
	$result = $db->getMaterials();

	$response["error"] = false;
	$response["data"] = array();
	while ($material = $result->fetch_assoc()) {
		$tmp = array();
		$tmp["id"] = $material["id"];
		$tmp["alias"] = $material["alias"];
		$tmp["name"] = $material["name"];
		$tmp["brand"] = $material["brand"];
		$tmp["parent_id"] = $material["parent_id"];
		$tmp["total_time"] = $material["total_time"];
		$tmp["total_distance"] = $material["total_distance"];
		$tmp["status"] = $material["status"];
		$tmp["created_at"] = $material["created_at"];
		$tmp["purchase_date"] = $material["purchase_date"];
		$tmp["max_time"] = $material["max_time"];
		$tmp["max_distance"] = $material["max_distance"];
		$tmp["comment"] = $material["comment"];
		$tmp["initial_time"] = $material["initial_time"];
		$tmp["initial_distance"] = $material["initial_distance"];
		array_push($response["data"], $tmp);
	}
	echoResponse(200, $response);
}

function updateMaterials(){
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$result = false;
	$itemsUpdated = 0;

	$db = new MaterialDbHandler();
	if (count($jsonData->data)==1){
		$result = $db->updateMaterial($jsonData->data);
		if ($result) {
			$itemsUpdated = 1;
		}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $material) {
			$result = $db->updateMaterial($material);
			if ($result) {
				$itemsUpdated++;
			}
		}
	}

	$response["error"] = $itemsUpdated==count($jsonData->data) ? false : true;
	$response["message"] = "Total materials updated: ".$itemsUpdated;
	$response["data"]=$jsonData->data;
	$errorCode = 201;
	if ($response["error"]) {
		$errorCode = 500;
	}
	echoResponse($errorCode, $response);
}

function deleteMaterials () {
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$result = false;
	$itemsDeleted = 0;

	$db = new MaterialDbHandler();
	if (count($jsonData->data)==1){
		$result = $db->deleteMaterial($jsonData->data->id);
		if ($result) {
			$itemsDeleted = 1;
		}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $material) {
			$result = $db->deleteMaterial($material->id);
			if ($result) {
				$itemsDeleted++;
			}
		}
	}
}

$app->run();
?>
