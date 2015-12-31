<?php
/**
 * Class to handle all activity types db operations
 */
class ActivityTypeDbHandler {

	private $conn;

	function __construct() {
		require_once dirname(__FILE__) . '/DbConnect.php';
		// opening db connection
		$db = new DbConnect();
		$this->conn = $db->connect();
	}
	
	// Creating new activitytype
	public function createActivityType($activityType) {
		$stmt = $this->conn->prepare("INSERT INTO activityTypes(name,sportType_id,comment) VALUES(?,?,?)");
		$stmt->bind_param("sss", $activityType->name, $activityType->sportType_id, $activityType->comment);
		$result = $stmt->execute();
		$stmt->close();
	
		if ($result) {
			// task row created
			// now assign the task to user
			$new_activityType_id = $this->conn->insert_id;
			return $new_activityType_id;
		} else {
			// task failed to create
			return NULL;
		}
	}
	
	// Fetching all activityTypes
	public function getActivityTypes() {
		$stmt = $this->conn->prepare("SELECT * FROM activityTypes");
		$stmt->execute();
		$activityTypes = $stmt->get_result();
		$stmt->close();
		return $activityTypes;
	}
	
	// Updating an activityType
	public function updateActivityTypes($activityType) {
		$stmt = $this->conn->prepare("UPDATE activityTypes SET name=?, sportType_id=? comment=? WHERE id=?");
		$stmt->bind_param("sssi", $activityType->name, $activityType->sportType_id, $activityType->comment, $activityType->id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0;
	}
	
	// Deleting an activitytype
	public function deleteActivityType($id) {
		$stmt = $this->conn->prepare("DELETE FROM activityTypes WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0;
	}
}