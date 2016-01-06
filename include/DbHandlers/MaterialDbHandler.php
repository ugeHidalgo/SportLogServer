<?php
/**
 * Class to handle all materials db operations
 */
class MaterialDbHandler {

	private $conn;

	function __construct() {
		require_once dirname(__FILE__) . '/DbConnect.php';
		// opening db connection
		$db = new DbConnect();
		$this->conn = $db->connect();
	}
	
	// Creating new material
	public function createMaterial($material) {
		$stmt = $this->conn->prepare(
				"INSERT INTO materials ". 
				"(alias,name,brand,parent_id,status,purchase_date,max_time,max_distance,".
				"comment,initial_time,initial_distance,userId) ".
				"VALUES(?,?,?,?,?,FROM_UNIXTIME(?),?,?,?,?,?,?)");
		$stmt->bind_param("sssiissssssi", 
				$material->alias,
				$material->name,
				$material->brand,
				$material->parent_id,
				$material->status,
				$material->purchase_date,
				$material->max_time,
				$material->max_distance,
				$material->comment,
				$material->initial_time,
				$material->initial_distance,
				$material->userId);
		$result = $stmt->execute();
		$stmt->close();
	
		if ($result) {
			// task row created
			// now assign the task to user
			$new_material_id = $this->conn->insert_id;
			return $new_material_id;
		} else {
			// task failed to create
			return NULL;
		}
	}
	
	// Fetching all materials
	public function getMaterials($userId) {
		$stmt = $this->conn->prepare("SELECT * FROM materials WHERE userId=?");
		$stmt->bind_param("i", $userId);
		$stmt->execute();
		$materials = $stmt->get_result();
		$stmt->close();
		return $materials;
	}
	
	// Updating a material
	public function updateMaterial($material) {
		$stmt = $this->conn->prepare(
				"UPDATE materials ".
				"SET alias=? ,name=?, brand=?, parent_id=?, ".
				    "status=? ,purchase_date=FROM_UNIXTIME(?), max_time=?, max_distance=?, ".
					"comment=? ,initial_time=? ,initial_distance=?, userId=? ".
				"WHERE id=?");
		$stmt->bind_param("sssiissssssii", 
				$material->alias,
				$material->name,
				$material->brand,
				$material->parent_id,
				$material->status,
				$material->purchase_date,
				$material->max_time,
				$material->max_distance,
				$material->comment,
				$material->initial_time,
				$material->initial_distance,
				$material->userId,
				$material->id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0;
	}
	
	// Deleting a material
	public function deleteMaterial($id) {
		$stmt = $this->conn->prepare("DELETE FROM materials WHERE id = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$num_affected_rows = $stmt->affected_rows;
		$stmt->close();
		return $num_affected_rows > 0;
	}
}