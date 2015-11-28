<?php

/**
 * Class to handle all sportType db operations
 */
class SportTypeDbHandler {
 
    private $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
 
    // Creating new sportType
    public function createSportType($name) {        
        $stmt = $this->conn->prepare("INSERT INTO sportTypes(name) VALUES(?)");
        $stmt->bind_param("s", $name);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // task row created
            // now assign the task to user
            $new_sportType_id = $this->conn->insert_id;
            return $new_sportType_id;
            } else {
                // task failed to create
                return NULL;
            }
    }
 
    // Fetching single sportType
    public function getSportType($id) {
        $stmt = $this->conn->prepare("SELECT t.* from sportTypes t WHERE t.id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $sportType = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $sportType;
        } else {
            return NULL;
        }
//        $stmt->execute();
//        $sportType = $stmt->get_result();
//        $stmt->close();
//        return $sportType;
    }
 
    // Fetching all sportTypes
    public function getAllSportTypes() {
        $stmt = $this->conn->prepare("SELECT t.* FROM sportTypes t");
        $stmt->execute();
        $sportTypes = $stmt->get_result();
        $stmt->close();
        return $sportTypes;
    }
 
    // Updating SportType
    public function updateSportType($id, $name) {
        $stmt = $this->conn->prepare("UPDATE sportTypes t SET t.name = ? WHERE t.id = ?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
 
    // Deleting a sportType
    public function deleteSportType($id) {
        $stmt = $this->conn->prepare("DELETE t FROM sportTypes t WHERE t.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    } 
}

