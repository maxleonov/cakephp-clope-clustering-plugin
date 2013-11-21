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
		if (isset($this->schemaId)) {
			throw new Exception(__CLASS__ . '::createSchema() must be called exactly once');
		}

		$this->schemaId = $schemaId;

		$useTableNew = $this->useTable.'_'.$this->schemaId;
		$db = ConnectionManager::getDataSource($this->useDbConfig);
		$db->execute($db->createSchema($this->_schema($useTableNew)));
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

		if (!is_null($Schema)) {
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
