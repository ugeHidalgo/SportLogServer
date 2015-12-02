<?php
/**
 * Class to handle all activities db operations
 */
class ActivityDbHandler {

	private $conn;

	function __construct() {
		require_once dirname(__FILE__) . '/DbConnect.php';
		// opening db connection
		$db = new DbConnect();
		$this->conn = $db->connect();
	}
	
	// Creating new activity
	public function createActivity($name, $sportTypeId) {
		$stmt = $this->conn->prepare("INSERT INTO activities(name,sportType_id) VALUES(?,?)");
		$stmt->bind_param("ss", $name, $sportTypeId);
		$result = $stmt->execute();
		$stmt->close();
	
		if ($result) {
			// task row created
			// now assign the task to user
			$new_activity_id = $this->conn->insert_id;
			return $new_activity_id;
		} else {
			// task failed to create
			return NULL;
		}
	}
	
	// Fetching all activities
	public function getActivities() {
		$stmt = $this->conn->prepare("SELECT * FROM activities");
		$stmt->execute();
		$activities = $stmt->get_result();
		$stmt->close();
		return $activities;
	}
	
	// Deleting an activity
	public function deleteActivity($id) {
		$stmt = $this->conn->prepare("DELETE FROM activities WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0;
	}
}