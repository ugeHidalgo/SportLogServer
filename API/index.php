<?php

require_once '../include/DbHandlers/UserDbHandler.php';
require_once '../include/DbHandlers/SportTypeDbHandler.php';
require_once '../include/DbHandlers/ActivityDbHandler.php';
require_once '../include/DbHandlers/MaterialDbHandler.php';
//require_once 'sportTypeInterface.php';
require_once '../include/DbHandlers/RememberMeDbHandler.php';


require '../libs/Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
// User id from db - Global Variable
$user_id = NULL;

// ---------------------------------------------------------------------
// ------ web services -------------------------------------------------
// ---------------------------------------------------------------------

// ------ User services ------------------------------------------------

// User Registration
$app->post('/register', function() use ($app){
            // check for required params
            verifyRequiredParams(array('first_name', 'second_name', 'user_name', 'email', 'birthdate','sex', 'password')); 
            
            $firstName = $_REQUEST['first_name'];
            $secondName = $_REQUEST['second_name'];
            $userName = $_REQUEST['user_name'];
            $email = $_REQUEST['email'];
            $birthdate = $_REQUEST['birthdate'];
            $sex = $_REQUEST['sex'];
            $password = $_REQUEST['password'];
 
            // validating email address
            validateEmail($email);
            $db = new UserDbHandler();
            $res = $db->createUser($firstName, $secondName, $userName, $email, $birthdate, $sex, $password);
 
            if ($res["message"] == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "Registrado correctamente";
                $response["api_key"] = $res["api_key"];
                echoResponse(201, $response);
            } else if ($res["message"] == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Ocurrió un error durante el proceso de registro. Vuelva a intentarlo.";
                echoResponse(200, $response);
            } else if ($res["message"] == USER_ALREADY_EXIST) {
                $response["error"] = true;
                $response["message"] = "Ya existe ese nombre de usuario en la base de datos.";
                echoResponse(200, $response);
            }
        });

// User login
$app->post('/login', function() use ($app) {
		// check for required params
		verifyRequiredParams(array('user_name', 'password'));
		$userName = $_REQUEST['user_name'];
		$password =$_REQUEST['password'];
	
		$response = array();
		$response["data"]= array();
	
		$db = new UserDbHandler();
		// check for correct user name and password
		if ($db->checkLogin($userName, $password)) {
			// get the user by user name
			$user = $db->getUserByUserName($userName);
	
			if ($user != NULL) {
				if ($user["status"]==1){
					$response["data"]=$user;
					$response["error"] = false;
					$response['message']="Login correct.";
	/*				$response["id"] = $user["id"];
					$response['first_name'] = $user['first_name'];
					$response['second_name'] = $user['second_name'];
					$response['user_name'] = $user['user_name'];
					$response['email'] = $user['email'];
					$response['apiKey'] = $user['api_key'];
					$response["status"] = $user["status"];
					$response["created_at"] = $user["created_at"];
					$response["birthdate"] = $user["birthdate"];
					$response["sex"] = $user["sex"];
					$response["admin"] = $user["admin"];*/
				} else {
					// user is not active
					$response['error'] = true;
					$response['message'] = "Su usuario no está activo. Contacte con el administrados de la aplicación.";
				}
			} else {
				// unknown error occurred
				$response['error'] = true;
				$response['message'] = "Se produjo algún error al acceder a la base de datos. Por favor, vuelva a intentarlo en unos segundos.";
			}
		} else {
			// user credentials are wrong
			$response['error'] = true;
			$response['message'] = 'Nombre de usuario o contraseña incorrectas.';
		}
	
		echoResponse(200, $response);
	});

//User update
$app->post('/users', 'authenticate' ,function() use ($app) {
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	
	$db = new UserDbHandler();
	$response["message"] = "No user updated.";
	$result = $db->updateUser($jsonData->data);
	if ($result) {
		$response["message"] = "User successfully updated.";
	}
	$response["error"] = !$result;
	$response["count"]= $result;
	$response["data"]=$jsonData->data;
	echoResponse(201, $response);
});

// Listing all users
$app->get('/users', 'authenticate', 'getUsers');


// ------ Sport Type services ------------------------------------------
// Creating new sporTypes in db
$app->post('/sportTypes', 'authenticate', 'createSportTypes');

// Listing all sport types
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

// ------ Activity services ------------------------------------------
// Creating activity in db
$app->post('/activities', 'authenticate', 'createActivities');

// Listing all activities
$app->get('/activities', 'authenticate', 'getAllActivities');

// Updating all activities included in payload
$app->put('/activities', 'authenticate', 'updateActivities');

// Deleting a set of activities
$app->delete('/activities', 'authenticate', 'deleteActivities');

// ------ Materials services ------------------------------------------
// Creating a new material in db
$app->post('/materials', 'authenticate', 'createMaterials');

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
	echoResponse(201, $response);
}

// Listing all materials
$app->get('/materials', 'authenticate', 'getAllMaterials');

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

// Updating all materials included in payload
$app->put('/materials', 'authenticate', 'updateMaterials');

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

	echoResponse(201, $response);
}

// Deleting a set of materials
$app->delete('/materials', 'authenticate', 'deleteMaterials');

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

// ------ RememberMe services ------------------------------------------

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


// ------ User auxiliar functions ----------------------------------
function getUsers() {
            $response = array();
            $db = new USerDbHandler();
 
            // fetching all user tasks
            $result = $db->getAllUsers();
 
            $response["error"] = false;
            $response["data"] = array();
 
            // looping through result and preparing tasks array
            while ($user = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $user["id"];
                $tmp["user_name"] = $user["user_name"];
                $tmp["first_name"] = $user["first_name"];
                $tmp["second_name"] = $user["second_name"];
                $tmp["email"] = $user["email"];
                $tmp["status"] = $user["status"];
                $tmp["created_at"] = $user["created_at"];
                $tmp["birthdate"] = $user["birthdate"];
                $tmp["sex"] = $user["sex"];
                $tmp["admin"] = $user["admin"];
                array_push($response["data"], $tmp);
            }
            $response["count"] = count($response["data"]);
 
            echoResponse(200, $response);
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
            $result = $db->getAllSportTypes();
 
            $response["error"] = false;
            $response["data"] = array();
            while ($sportType = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $sportType["id"];
                $tmp["name"] = $sportType["name"];
                $tmp["comment"] = $sportType["comment"];
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


// ------ Activities auxiliar functions ----------------------------------
function createActivities(){
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$itemsCreated = 0;

	$db = new ActivityDbHandler();
	if (count($jsonData->data)==1){
		$id = $db->createActivity($jsonData->data);
		if ($id != NULL){
			$itemsCreated = 1;
		}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $activity) {
			$id = $db->createActivity($activity);
			if ($id != NULL) {
				$itemsCreated++;
			}
		}
	}

	$response["error"] = $itemsCreated==count($jsonData->data) ? false : true;
	$response["message"] = "Total activities created: ".$itemsCreated;
	$response["data"]=$jsonData->data;
	echoResponse(201, $response);
}

function getAllActivities() {
	global $user_id;
	$response = array();
	$db = new ActivityDbHandler();
	$result = $db->getActivities();
	
	$response["error"] = false;
	$response["data"] = array();
    while ($activity = $result->fetch_assoc()) {
    	$tmp = array();
        $tmp["id"] = $activity["id"];
        $tmp["name"] = $activity["name"];
        $tmp["sportType_id"] = $activity["sportType_id"];
        array_push($response["data"], $tmp);
    } 
	echoResponse(200, $response);
}

function updateActivities(){
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$result = false;
	$itemsUpdated = 0;

	$db = new ActivityDbHandler();
	if (count($jsonData->data)==1){
		$result = $db->updateActivity($jsonData->data);
		if ($result) {
			$itemsUpdated = 1;
		}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $ativity) {
			$result = $db->updateActivity($ativity);
			if ($result) {
				$itemsUpdated++;
			}
		}
	}

	$response["error"] = $itemsUpdated==count($jsonData->data) ? false : true;
	$response["message"] = "Total activities updated: ".$itemsUpdated;
	$response["data"]=$jsonData->data;

	echoResponse(201, $response);
}

function deleteActivities () {
	$request_body = file_get_contents('php://input');
	$jsonData = json_decode($request_body);
	$result = false;
	$itemsDeleted = 0;

	$db = new ActivityDbHandler();
	if (count($jsonData->data)==1){
		$result = $db->deleteActivity($jsonData->data->id);
		if ($result) {
			$itemsDeleted = 1;
		}
	} else if (count($jsonData->data)>1) {
		foreach ($jsonData->data as $activity) {
			$result = $db->deleteActivity($activity->id);
			if ($result) {
				$itemsDeleted++;
			}
		}
	}

	$response["error"] = $itemsDeleted==count($jsonData->data) ? false : true;
	$response["message"] = "Total activities deleted: ".$itemsDeleted;
	$response["data"]=$jsonData->data;

	echoResponse(201, $response);
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
