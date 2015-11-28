<?php
class USerDbHandler {
 
    private $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
 
    /* ------------- users table methods ------------------ */
 	//User sex: 0-Undefined, 1:Female, 2: Male

    public function createUser($firstName, $secondName, $userName, $eMail, $birthDate, $sex, $password) {
    	require_once 'PassHash.php';
        $response = array();
 
        // First check if user already existed in db
        if (!$this->isUserExists($userName)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);
 
            // Generating API key
            $api_key = $this->generateApiKey();
 
            // insert query
            $stmt = $this->conn->prepare(
            		"INSERT INTO users(first_name, second_name, user_name, email, password_hash, api_key, birthdate, sex) 
            		values(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssi", $firstName, $secondName, $userName, $eMail, $password_hash, $api_key, $birthDate, $sex);
 
            $result = $stmt->execute();
 
            $stmt->close();
 
            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                $response['message'] = USER_CREATED_SUCCESSFULLY;
                $response['api_key'] = $api_key; 
            } else {
                // Failed to create user
                $response['message'] = USER_CREATE_FAILED;
                $response['api_key'] = 'no key';
            }
        } else {
            // User with same username already existed in the db
            $response['message'] = USER_ALREADY_EXIST;
            $response['api_key'] = 'no key';
        }
 
        return $response;
    }
 
    public function updateUser($user) {
    	$query = "UPDATE users ". 
    			 "SET status=?, admin=?, first_name=?, second_name=?, user_name=?, email=?, birthdate=?, sex=? ".
    			 "WHERE id=?";
    	$stmt = $this->conn->prepare($query);
    	$stmt->bind_param("iissssssi", $user->status, $user->admin, $user->first_name, $user->second_name, $user->user_name, 
    								   $user->email, $user->birthdate, $user->sex, $user->id);
    	$stmt->execute();
    	$num_affected_rows = $stmt->affected_rows;
    	$stmt->close();
    	return $num_affected_rows > 0;
    }
    
    public function checkLogin($userName, $password) {
    	require_once 'PassHash.php';
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE user_name = ?");
 
        $stmt->bind_param("s", $userName);
 
        $stmt->execute();
 
        $stmt->bind_result($password_hash);
 
        $stmt->store_result();
 
        if ($stmt->num_rows > 0) {
            // Found user with the user name.
            // Now verify the password
 
            $stmt->fetch();
 
            $stmt->close();
 
            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();
 
            // user not existed with the user name
            return FALSE;
        }
    }
 
    private function isUserExists($userName) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE user_name = ?");
        $stmt->bind_param("s", $userName);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    public function getUserByUserName($userName) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_name = ?");
        $stmt->bind_param("s", $userName);
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
 
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $api_key = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }
 
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }
    
    public function getAllUsers() {
        $stmt = $this->conn->prepare("SELECT * FROM users");
        if ($stmt->execute()) {
            $data = $stmt->get_result();
            $stmt->close();
            return $data;
        } else {
            return NULL;
        }
    }
 
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
 
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }
}
 
?>
