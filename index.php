<?php

/**
 * Simple wrapper around PDO.
 * 
 * TODO: Maybe bind input parameters with PDO types? (http://php.net/manual/en/pdo.constants.php)
 * 
 * NOTE: The default php mysql driver always returns strings!
 *       There are ways to work around this, but this simple wrapper does not do anything.
 *       See http://stackoverflow.com/q/1197005/477513 for instructions.
 */

class DB {
	private $db;
	
	//$fetch_mode applies to the results returned by the `all` and `row` functions.
	// It can be `PDO::FETCH_OBJ` or `PDO::FETCH_ASSOC` or `PDO::FETCH_NUM`
	// (or, if nothing provided, defaults to `PDO::FETCH_BOTH`).
	public function __construct($host, $db, $user, $password, $fetch_mode = null) {
		$this->db = new PDO("mysql:host={$host};dbname={$db}", $user, $password);
				
		if ($fetch_mode) {
			$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $fetch_mode);
		}
	}
	
	public function all($sql, array $params = array()) {
		if (empty($params)) {
			$stmt = $this->db->query($sql);
		} else {
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);
		}
		return $stmt->fetchAll(); //returns results as specified by PDO::ATTR_DEFAULT_FETCH_MODE up above, or could be overridden on a per-call basis by providing one of the fetch_style constants (PDO::FETCH_OBJ, PDO::FETCH_ASSOC, or PDO::FETCH_NUM) to `$stmt->fetchAll()`
	}
	
	public function col($sql, array $params = array()) {
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}
	
	public function row($sql, array $params = array()) {
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetch(); //returns results as specified by PDO::ATTR_DEFAULT_FETCH_MODE up above, or could be overridden on a per-call basis by providing one of the fetch_style constants (PDO::FETCH_OBJ, PDO::FETCH_ASSOC, or PDO::FETCH_NUM) to `$stmt->fetch()`
	}
	
	public function one($sql, array $params = array()) {
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchColumn();
	}
	
	//Returns the last inserted record's id (as a string if using default mysql driver)
	public function insert($table, array $data = array()) {
		$sql = "INSERT INTO {$table}";
		$params = array();
		
		if ($data) {
			$column_names = implode(', ', array_keys($data)); //assumes $data array keys are trustworthy!
			$question_marks = implode(', ', array_fill(0, count($data), '?'));
			$sql .= " ({$column_names}) VALUES ({$question_marks})";
			$params = array_values($data);
		}
		
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		return $this->db->lastInsertId();
	}
	
	public function update($table, array $data, $where_sql, array $where_params = array()) {
		if (empty($data)) {
			return;
		}
		
		$where_sql = trim($where_sql);
		if (empty($where_sql)) {
			//avoid accidentally updating the entire table
			throw new Exception('You must provide a WHERE clause to the DB::update() function!');
		}
		
		$columns_and_placeholders = array();
		foreach (array_keys($data) as $column) {
			$columns_and_placeholders[] = "{$column} = ?"; //assumes $data array keys are trustworthy!
		}
		
		$sql = "UPDATE {$table} SET " . implode(', ', $columns_and_placeholders) . " WHERE {$where_sql}";
		$params = array_merge(array_values($data), $where_params);
		
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
	}
	
	public function execute($sql, array $params = array()) {
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
	}
	
}
