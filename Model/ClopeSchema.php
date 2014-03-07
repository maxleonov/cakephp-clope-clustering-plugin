<?php

/**
 * Create & Remove Model Schema
 *
 * @author maxleonov <maks.leonov@gmail.com>
 *
 * @package ClopeClustering
 * @subpackage Model
 */
App::uses('ConnectionManager', 'Model');
App::uses('CakeSchema', 'Model');

/**
 * Create & Remove Model Schema
 * To create schema, call ->_createSchema($schemaId)
 * Schema will be removed when model object is destroyed.
 *
 * @package ClopeClustering
 * @subpackage Model
 */
class ClopeSchema extends AppModel {

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $actsAs = array('Containable');

	/**
	 * {@inheritdoc}
	 *
	 * @var int
	 */
	public $recursive = -1;

	/**
	 * {@inheritdoc}
	 * 
	 * @param int|string $id
	 * @param string $table
	 * @param string $ds
	 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->useTable = $this->useTable . '_' . $this->_tableId();
		$this->_createSchema();
	}
	
	/**
	 * Destructor
	 */
	public function __destruct() {
		$this->_dropSchema();
	}

	/**
	 * Create Schema.
	 * Must be called exactly once
	 *
	 * @param int|string $schemaId
	 */
	protected function _createSchema() {
		$this->_dropSchema();
		$db = ConnectionManager::getDataSource($this->useDbConfig);

		try {
			$db->execute($db->createSchema($this->_schema()));
		} catch (Exception $e) {
			$this->_dropSchema();
			$db->execute($db->createSchema($this->_schema()));
		}		
	}

	/**
	 * Drop Schema
	 */
	protected function _dropSchema() {
		$db = ConnectionManager::getDataSource($this->useDbConfig);
		$db->execute($db->dropSchema($this->_schema()));
	}

	/**
	 * Return CakeSchema object for given Schema name
	 *
	 * @return CakeSchema
	 */
	protected function _schema() {
		static $Schema = null;

		if ($Schema) {
			return $Schema;
		}

		$db = ConnectionManager::getDataSource($this->useDbConfig);
		$Schema = new CakeSchema(array('connection' => $db));
		$Schema->build(array($this->useTable => $this->_schema));
		Cache::drop('_cake_model_');
		$db->reconnect();
		return $Schema;
	}

	/**
	 * Generate random string for table name
	 *
	 * @return string
	 */
	protected function _tableId() {
		static $tableId = null;
		if (!$tableId) {
			$tableId = substr(md5(microtime()), rand(0, 26), 5);
		} 
		return $tableId;
	}
}
