<?php
/**
 * Class to handle all sessions db operations
 */
class SessionDbHandler {

	private $conn;

	function __construct() {
		require_once dirname(__FILE__) . '/DbConnect.php';
		// opening db connection
		$db = new DbConnect();
		$this->conn = $db->connect();
	}
	
	// Creating new session
	public function createSession($session) {
		$stmt = $this->conn->prepare(
				"INSERT INTO sessions ". 
				"(userId,name,date,sessionTime,sessionDist) ".
				"VALUES(?,?,?,?,?)");
		$stmt->bind_param("sssss", 
				$session->userId,
				$session->name,
				$session->date,
				$session->sessionTime,
				$session->sessionDist);
		$result = $stmt->execute();
		$stmt->close();
	
		if ($result) {
			// task row created
			// now assign the task to user
			$new_session_id = $this->conn->insert_id;
			return $new_session_id;
		} else {
			// task failed to create
			return NULL;
		}
	}
	
	// Fetching all sessions
	public function getSessions() {
		$stmt = $this->conn->prepare("SELECT * FROM sessions"); //WHERE userId=?");
		//$stmt->bind_param("i", $userId);
		$stmt->execute();
		$sessions = $stmt->get_result();
		$stmt->close();
		return $sessions;
	}
	
	// Updating a session
	public function updateSession($session) {
		$stmt = $this->conn->prepare(
				"UPDATE sessions ".
				"SET name=?, date=?, userId=?, ".
				    "sessionDist=? ,sessionTime=?".
				"WHERE id=?");
		$stmt->bind_param("sssssi",
				$session->name,
				$session->date,
				$session->userId,
				$session->sessionDist,
				$session->sessionTime,
				$session->id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0; 
	}
	
	// Deleting a session
	public function deleteSession($id) {
		$stmt = $this->conn->prepare("DELETE FROM sessions WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0;
	}
}