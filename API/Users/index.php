<?php

require_once '../../include/DbHandlers/UserDbHandler.php';

require '../../libs/Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
// User id from db - Global Variable
$user_id = NULL;

// ---------------------------------------------------------------------
// ------ web services -------------------------------------------------
// ---------------------------------------------------------------------

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

$app->run();
?>
