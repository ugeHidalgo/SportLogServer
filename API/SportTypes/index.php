<?php

require_once '../../include/DbHandlers/UserDbHandler.php';
require_once '../../include/DbHandlers/SportTypeDbHandler.php';

require '../../libs/Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
// User id from db - Global Variable
$user_id = NULL;

// ---------------------------------------------------------------------
// ------ web services -------------------------------------------------
// ---------------------------------------------------------------------


// Creating new sporTypes in db
$app->post('/sportTypes', 'authenticate', 'createSportTypes');

// Listing all sport types for a given user
$app->get('/sportTypes', 'authenticate', 'getAllSportTypes');

// Find a Sport type by id
$app->get('/sportType/:id', 'authenticate', 'getSportTypeById');

// Updating all sport types included in payload
$app->put('/sportTypes', 'authenticate', 'updateSportTypes');

// Updating sport type by id
$app->put('/sportType/:id', 'authenticate', 'updateSportTypeById');

// Deleting a set of sport types
$app->delete('/sportTypes', 'authenticate', 'deleteSportTypes');

// Deleting sporttype
$app->delete('/sportType/:id', 'authenticate', 'deleteSportTypeById');


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


// ------ SportTypes auxiliar functions ----------------------------------
function createSportTypes(){
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$itemsCreated = 0;

	$db = new SportTypeDbHandler();
	if (count($jsonData->data)==1){
		$id = $db->createSportType($jsonData->data);
		if ($id != NULL){
			$itemsCreated = 1;
		}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $sportType) {
			$id = $db->createSportType($sportType);
			if ($id != NULL) {
				$itemsCreated++;
			}	
		}
	}

	$response["error"] = $itemsCreated==count($jsonData->data) ? false : true;
	$response["message"] = "Total sport types created: ".$itemsCreated;
	$response["data"]=$jsonData->data;
	echoResponse(201, $response);
}

function getAllSportTypes() {
			global $user_id;

            $response = array();
            $db = new SportTypeDbHandler();
            $result = $db->getAllSportTypes($user_id);
 
            $response["error"] = false;
            $response["data"] = array();
            while ($sportType = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $sportType["id"];
                $tmp["name"] = $sportType["name"];
                $tmp["comment"] = $sportType["comment"];
                $tmp["userId"] = $sportType["userId"];
                array_push($response["data"], $tmp);
            }
 			$response["count"] = count($response["data"]);
            echoResponse(200, $response);
}
        
function getSportTypeById($id) {
            $response = array();
            $response["data"] = array();
            
            $db = new SportTypeDbHandler();
            $result = $db->getSportType($id);
            if ($result != NULL) {
                $response["error"] = false;
                $response["message"] = "Sport type with id ".$id." found.";
                $response["data"] = $result;
            /*    $response["id"] = $result["id"];
                $response["name"] = $result["name"]; */
                echoResponse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Sport type with id ". $id . " was NOT found.";
                echoResponse(404, $response);
            }
}
        
function updateSportTypes(){
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$result = false;
	$itemsUpdated = 0;
	
	$db = new SportTypeDbHandler();
	if (count($jsonData->data)==1){
		$result = $db->updateSportType($jsonData->data);
		if ($result) {
				$itemsUpdated = 1;
			}	
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $sportType) {
			$result = $db->updateSportType($sportType);
			if ($result) {
				$itemsUpdated++;
			}	
		}
	}

	$response["error"] = $itemsUpdated==count($jsonData->data) ? false : true;
	$response["message"] = "Total sport types updated: ".$itemsUpdated;
	$response["data"]=$jsonData->data;

	echoResponse(201, $response);
}

function updateSportTypeById ($id) {
            
	verifyRequiredParams(array('name'));
	global $app;        
	$name = $app->request->params('name');
 
    $db = new SportTypeDbHandler();        
    $response = array();
    $user = array();
    $user["id"]=$id;
    $user["name"]=$name;
    
    $result = $db->updateSportType($user);
           
    if ($result) {           
    	$response["error"] = false;
        $response["message"] = "Sport type with id: ". $id ." was updated successfully";
    } else {
    	$response["error"] = true;
        $response["message"] = "Sport type with id: ". $id ."was NOT updated because it was not found. Please try again!";
    }
    
    echoResponse(200, $response);
}
        
function deleteSportTypes () {
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$result = false;
	$itemsDeleted = 0;
	
	$db = new SportTypeDbHandler();
	if (count($jsonData->data)==1){
		$result = $db->deleteSportType($jsonData->data->id);
		if ($result) {
				$itemsDeleted = 1;
			}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $sportType) {
			$result = $db->deleteSportType($sportType->id);
			if ($result) {
				$itemsDeleted++;
			}	
		}
	}

	$response["error"] = $itemsDeleted==count($jsonData->data) ? false : true;
	$response["message"] = "Total sport types deleted: ".$itemsDeleted;
	$response["data"]=$jsonData->data;

	echoResponse(201, $response);
}

function deleteSportTypeById ($id) {
 
	$db = new SportTypeDbHandler();
    $response = array();        
    $result = $db->deleteSportType($id);
           
    if ($result) {
                
    	$response["error"] = false;       
    	$response["message"] = "Sport type with id: ". $id ." deleted successfully";
    } else {
    	$response["error"] = true;
    	$response["message"] = "Sport type with id: ". $id ." was NOT deleted because it was not found. Please try again!";
    }        
    echoResponse(200, $response);
}

$app->run();
?>
