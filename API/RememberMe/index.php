<?php

require_once '../../include/DbHandlers/UserDbHandler.php';
require_once '../../include/DbHandlers/RememberMeDbHandler.php';

require '../../libs/Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
// User id from db - Global Variable
$user_id = NULL;

// ---------------------------------------------------------------------
// ------ web services -------------------------------------------------
// ---------------------------------------------------------------------


// Creating new remember-me field in db
$app->post('/rememberMe', 'authenticate', 'createNewRememberMeField');

// Creating new remember-me field in db
$app->get('/rememberMe/:id', 'authenticate', 'getRememberMeField');

// Updating remember me field in db
$app->put('/rememberMe/:fieldName', 'authenticate', 'updateRememberMeField');

// Deleting sporttype
$app->delete('/rememberMe/:fieldName', 'authenticate', 'deleteRememberMeField');

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


// ------ Remember me auxiliar functions ----------------------------------
function createNewRememberMeField () {
	
	verifyRequiredParams(array('fieldName', 'fieldValue'));
	$fieldName = $_REQUEST['fieldName'];
	$fieldValue = $_REQUEST['fieldValue'];
	
	$response = array();
	
	$db = new RememberMeDbHandler();
	$result = $db->createField($fieldName,$fieldValue);

	if ($result["message"] == FIELD_CREATED_SUCCESSFULLY) {
		$response["error"] = false;
		$response["message"] = "Campo para recordar registrado correctamente";
		echoResponse(201, $response);
	} else if ($result["message"] == FIELD_CREATE_FAILED) {
		$response["error"] = true;
		$response["message"] = "Ocurrió un error durante el proceso de registro. Vuelva a intentarlo.";
		echoResponse(200, $response);
	} else if ($result["message"] == FIELD_ALREADY_EXIST) {
		$response["error"] = true;
		$response["message"] = "Ya existe ese campo para recordar en la base de datos.";
		echoResponse(200, $response);
	}
}
        
function getRememberMeField ($fieldName) {
	
	$response = array();	
	$db = new RememberMeDbHandler();
	$result = $db->getFieldByFieldName($fieldName);
	if ($result != NULL) {
		$response["error"] = false;
		$response["field"] = $result;
		echoResponse(200, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "Field with name ". $fieldName . " was NOT found.";
		echoResponse(404, $response);
	}
}

function updateRememberMeField ($fieldName){
	
	$response = array();
	global $app;
	verifyRequiredParams(array('fieldValue'));
	$fieldValue = $app->request->params('fieldValue');

	$db = new RememberMeDbHandler();	
	$result = $db->updateRememberMeField($fieldName, $fieldValue);
	 
	if ($result) {
		$response["error"] = false;
		$response["message"] = "RememberMe field : ". $fieldName ." updated successfully";
	} else {
		$response["error"] = true;
		$response["message"] = "RememberMe field : ". $fieldName ." was NOT updated because it was not found. Please try again!";
	}
	
	echoResponse(200, $response);
	
}

function deleteRememberMeField ($fieldName){
	
	$db = new RememberMeDbHandler();
	$response = array();
	$result = $db->deleteRememberMeField($fieldName);
	 
	if ($result) {
	
		$response["error"] = false;
		$response["message"] = "Remember me field : ". $fieldName ." deleted successfully";
	} else {
		$response["error"] = true;
		$response["message"] = "Remember me field : ". $fieldName ." was NOT deleted because it was not found. Please try again!";
	}
	echoResponse(200, $response);
}

$app->run();
?>

