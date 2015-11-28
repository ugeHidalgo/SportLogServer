<?php
/**
 * Class to handle all db operations
 * This class will have CRUD methods for RememberMe database tables
 *
 */
class RememberMeDbHandler {
 
    private $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
 
// ------------- RememberMe table methods ------------------ 
 
    /**
     * Creating new data to remember
     * @param String $fieldName The name of the field to remember.
     * @param String $fieldValue The value of the field to remember.
     */
    public function createField($fieldName, $fieldValue) {
        $response = array();
 
        // First check if field already existed in db
        if (!$this->isFieldExists($fieldName)) {
            // insert query
            $stmt = $this->conn->prepare(
            		"INSERT INTO rememberMe(fieldName, fieldValue) 
            		values(?, ?)");
            $stmt->bind_param("ss", $fieldName, $fieldValue);
 
            $result = $stmt->execute();
 
            $stmt->close();
 
            // Check for successful insertion
            if ($result) {
                // Field successfully inserted
                $response['message'] = FIELD_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create Field
                $response['message'] = FIELD_CREATE_FAILED;
            }
        } else {
            // Field with same name already existed in the db
            $response['message'] = FIELD_ALREADY_EXIST;
        }
 
        return $response;
    }
 
    /**
     * Checking for duplicate field 
     * @param String fieldName The field name to check in db
     * @return boolean
     */
    private function isFieldExists($fieldName) {
        $stmt = $this->conn->prepare("SELECT fieldName from rememberMe WHERE fieldName = ?");
        $stmt->bind_param("s", $fieldName);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
 
    /**
     * Get field by field name
     * @param String fieldName
     */
    public function getFieldByFieldName($fieldName) {
        $stmt = $this->conn->prepare("SELECT * FROM rememberMe WHERE fieldName = ?");
        $stmt->bind_param("s", $fieldName);
        if ($stmt->execute()) {
            $field = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $field;
        } else {
            return NULL;
        }
    }

   /**
    * Updating field
    * @param String $fieldName
    * @param String $fieldValue
    */
    public function updateRememberMeField($fieldName, $fieldValue) {
       $stmt = $this->conn->prepare("UPDATE rememberMe t SET t.fieldValue = ? WHERE t.fieldName = ?");
       $stmt->bind_param("ss", $fieldValue, $fieldName);
       $stmt->execute();
       $num_affected_rows = $stmt->affected_rows;
       $stmt->close();
       return $num_affected_rows > 0;
   }
 
    /**
     * Deleting a field
     * @param String $fieldName
     */
    public function deleteRememberMeField($fieldName) {
       $stmt = $this->conn->prepare("DELETE t FROM rememberMe t WHERE t.fieldName = ?");
       $stmt->bind_param("s", $fieldName);
       $stmt->execute();
       $num_affected_rows = $stmt->affected_rows;
       $stmt->close();
       return $num_affected_rows > 0;
   } 
}
 
?>