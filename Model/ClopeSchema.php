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
	public function createSchema($schemaId) {
		if (!isset($this->_initialUseTable)) {
			$this->_initialUseTable = $this->useTable;
		}

		if (isset($this->schemaId)) {
			$this->dropSchema();
		}

		$this->schemaId = $schemaId;

		$useTableNew = $this->_initialUseTable.'_'.$this->schemaId;
		$db = ConnectionManager::getDataSource($this->useDbConfig);

		try {
			$db->execute($db->createSchema($this->_schema($useTableNew)));
		} catch(Exception $e) {
			$this->dropSchema();
			$db->execute($db->createSchema($this->_schema($useTableNew)));
		}

		$this->useTable = $useTableNew;
	}

	/**
	 * Drop Schema
	 */
	protected function dropSchema() {
		if (!isset($this->schemaId)) {
			return;
		}

		$db = ConnectionManager::getDataSource($this->useDbConfig);
		$db->execute($db->dropSchema($this->_schema($this->useTable)));
	}

	/**
	 * Return CakeSchema object for given Schema name
	 *
	 * @param string $name
	 *
	 * @return CakeSchema
	 */
	protected function _schema($name) {
		static $Schema = null;
		static $prev_name = null;

		if (!is_null($Schema) && $prev_name == $name) {
			return $Schema;
		} else {
			$db = ConnectionManager::getDataSource($this->useDbConfig);
			$Schema = new CakeSchema(array('connection' => $db));
			$Schema->build(array($name => $this->_schema));
			Cache::drop('_cake_model_');
			$db->reconnect();
			return $Schema;
		}
	}

}
