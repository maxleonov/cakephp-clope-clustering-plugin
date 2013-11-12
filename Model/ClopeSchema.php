<?php

/**
 * Create & Remove Model Schema

 * @author maxleonov <maks.leonov@gmail.com>
 */

App::uses('ConnectionManager', 'Model');
App::uses('CakeSchema', 'Model');

/**
 * 
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
	 * @param int|string $id
	 */
	public function createSchema($id) {
		if (isset($this->Schema)) {
			throw new Exception(__CLASS__.'::createSchema() must be called exactly once');
		}

		$useTableNew = $this->useTable.'_'.$id;
		$db = ConnectionManager::getDataSource($this->useDbConfig);
		$this->Schema = new CakeSchema(array('connection' => $db));
		$this->Schema->build(array($useTableNew => $this->_schema));
		$db->execute($db->createSchema($this->Schema));
		$this->useTable = $useTableNew;
	}

	/**
	 * Drop Schema
	 */
	public function dropSchema() {
		if (!isset($this->Schema)) {
			return;
		}

		$db = ConnectionManager::getDataSource($this->useDbConfig);
		$db->execute($db->dropSchema($this->Schema));
	}
}
