<?php

namespace Mailserver\Model;

use Mailserver\Db\Connection;

abstract class ActiveRecord implements \JsonSerializable
{
	/**
	 * Must be set on each ActiveRecord child with the represented table name
	 * @var string
	 */
	public static $tableName;

	/**
	 * Can be set on ActiveRecord child if the primary key column name is different from 'id'
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * The internal ActiveRecord data values
	 * @var array
	 */
	protected $data;

	/**
	 * Database connection instance
	 * @var \PDOConnection
	 */
	protected $connection;

	public function __construct($id = null)
	{
		$this->connection = Connection::getInstance();

		if ($id) {
			// load data
			$this->data = $this->_get($id);
		}
	}

	/**
	 * Add a new row on database
	 * @return boolean
	 */
	public function add()
	{
		$fields = implode(',', array_keys($this->data));
		$placeholders = implode(',', array_fill(0, count($this->data), '?'));
		$values = array_values($this->data);

		$sql = 'INSERT INTO '.static::$tableName. "($fields) VALUES($placeholders)";

		$stmt = $this->connection->prepare($sql);
		$success = $stmt->execute($values);

		if ($success) {
			$this->{$this->primaryKey} = $this->connection->lastInsertId();
		}

		return $success;
	}

	/**
	 * Update a row
	 * @return boolean
	 */
	public function update()
	{
		$pk = $this->data[$this->primaryKey];

		// unset primary key from update fields
		$data = $this->data;
		unset($data[$this->primaryKey]);

		// updates = 'field1 = ?, field2 = ?, ..., fieldN = ?'
		$updates = implode('=?,', array_keys($data));
		$updates .= '=?'; // for the last field after implode
		// update values
		$values = array_values($data);

		$sql = 'UPDATE '.static::$tableName." SET $updates WHERE {$this->primaryKey} = $pk";

		$stmt = $this->connection->prepare($sql);
		return $stmt->execute($values);
	}

	/**
	 * Delete a row
	 * @return boolean
	 */
	public function delete()
	{
		$pk = $this->data[$this->primaryKey];
		$sql = 'DELETE FROM '.static::$tableName." WHERE {$this->primaryKey} = ?";
		$stmt = $this->connection->prepare($sql);
		$success = $stmt->execute(array($pk));
		if ($success) {
			$this->data = array();
		}
		return $success;
	}

	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}

	public function __get($name)
	{
		return isset($this->data[$name]) ? $this->data[$name] : null;
	}

	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	public function __unset($name)
	{
		unset($this->data[$name]);
	}

	public function jsonSerialize()
	{
		return $this->data;
	}

	/**
	 * Load data from database and populate the object instance
	 * @param  integer $id Object primary key value
	 * @return array Associative array with data to load in object instance
	 */
	private function _get($id)
	{
		$sql = 'SELECT * FROM '.static::$tableName." WHERE {$this->primaryKey} = ?";

		$stmt = $this->connection->prepare($sql);
		if (!$stmt->execute(array($id))) {
			return array();
		}

		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}
}
