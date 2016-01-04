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
	public function createActivity($activity) {
		$stmt = $this->conn->prepare(
				"INSERT INTO activities ". 
				"(userId, sessionId, activityTypeId, ".
				"activityTime, activityDist, ".
				"avgHRate, maxHRate, minHRate, ".
				"avgPower, maxPower, minPower, ".
				"value, comment) ".
				"VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
		$stmt->bind_param("iiissiiiiiiis", 
				$activity->userId,
				$activity->sessionId,
				$activity->activityTypeId,
				$activity->activityTime,
				$activity->activityDist,
				$activity->avgHRate,
				$activity->maxHRate,
				$activity->minHRate,
				$activity->avgPower,
				$activity->maxPower,
				$activity->minPower,
				$activity->value,
				$activity->comment);
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
		$stmt = $this->conn->prepare("SELECT * FROM activities"); //WHERE userId=?");
		//$stmt->bind_param("i", $userId);
		$stmt->execute();
		$activities = $stmt->get_result();
		$stmt->close();
		return $activities;
	}
	
	// Updating a activity
	public function updateActivity($activity) {
		$stmt = $this->conn->prepare(
				"UPDATE sessions ".
				"SET userId=?, sessionId=?, activityTypeId=?, ".
				"activityTime=?, activityDist=?, ".
				"avgHRate=?, maxHRate=?, minHRate=?, ".
				"avgPower=?, maxPower=?, minPower=?, ".
				"value=?, comment=? ".
				"WHERE id=?");
		$stmt->bind_param("iiissiiiiiiisi", 
				$activity->userId,
				$activity->sessionId,
				$activity->activityTypeId,
				$activity->activityTime,
				$activity->activityDist,
				$activity->avgHRate,
				$activity->maxHRate,
				$activity->minHRate,
				$activity->avgPower,
				$activity->maxPower,
				$activity->minPower,
				$activity->value,
				$activity->comment,
				$activity->id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0; 
	}
	
	// Deleting a activity
	public function deleteActivity($id) {
		$stmt = $this->conn->prepare("DELETE FROM activities WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0;
	}
}