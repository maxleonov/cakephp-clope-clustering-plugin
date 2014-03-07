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
 * To create schema, call ->createSchema($schemaId)
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
	 * Cached table name
	 *
	 * @var string 
	 */
	protected $_tableName = null;
	
	/**
	 * Model schema
	 *
	 * @var CakeSchema 
	 */
	protected $_Schema = null;
	
	/**
	 * Destructor
	 */
	public function __destruct() {
		$this->dropSchema();
	}

	/**
	 * Create Schema.
	 * Must be called exactly once
	 *
	 * @param int|string $schemaId
	 */
	public function createSchema() {
		$this->_tableName = null;
		$this->_Schema = null;
		
		$db = ConnectionManager::getDataSource($this->useDbConfig);
		try {
			$db->execute($db->createSchema($this->_schema()));
		} catch (Exception $e) {
			$this->dropSchema();
			$db->execute($db->createSchema($this->_schema()));
		}		
		$this->setSource($this->_tableName());
	}

	/**
	 * Drop Schema
	 */
	public function dropSchema() {
		$db = ConnectionManager::getDataSource($this->useDbConfig);
		$db->execute($db->dropSchema($this->_schema()));
	}

	/**
	 * Return CakeSchema object for given Schema name
	 *
	 * @return CakeSchema
	 */
	protected function _schema() {
		if ($this->_Schema) {
			return $this->_Schema;
		}

		$db = ConnectionManager::getDataSource($this->useDbConfig);
		$this->_Schema = new CakeSchema(array('connection' => $db));
		$this->_Schema->build(array($this->_tableName() => $this->_schema));
		Cache::drop('_cake_model_');
		$db->cacheSources = false;
		$db->reconnect();
		return $this->_Schema;
	}

	/**
	 * Generate random string for table name
	 *
	 * @return string
	 */
	protected function _tableName() {
		if (!$this->_tableName) {
			$this->_tableName = $this->useTablePattern . '_' . mt_rand(0, PHP_INT_MAX);
		} 
		return $this->_tableName;
	}
}
