<?php

/**
 * Transaction

 * @author maxleonov <maks.leonov@gmail.com>
 *
 * @package ClopeClustering
 * @subpackage Model
 */

App::uses('ClopeSchema', 'ClopeClustering.Model');

/**
 * 
 */
class ClopeTransaction extends ClopeSchema {

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'ClopeCluster' => array(
			'className' => 'ClopeClustering.ClopeCluster',
			'foreignKey' => 'id'
		)
	);

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $hasMany = array(
		'ClopeAttribute' => array(
			'className' => 'ClopeClustering.ClopeAttribute',
			'foreignKey' => 'transaction_id'
		)
	);

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	public $useTable = 'clope_transactions';

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $actsAs = array('Containable');

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $_schema = array(
		'id' => array(
			'type' => 'integer',
			'null' => false,
			'default' => null,
			'length' => 5,
			'key' => 'primary'
		),
		'custom_id' => array(
			'type' => 'string',
			'null' => false,
			'default' => null,
			'length' => 255,
			'collate' => 'utf8_bin',
			'charset' => 'utf8'
		),
		'cluster_id' => array(
			'type' => 'integer',
			'null' => true,
			'default' => null,
			'length' => 5
		)
	);

	/**
	 * {@inheritdoc}
	 *
	 * @var int
	 */
	public $recursive = -1;

	/**
	 * @var int
	 */
	private $pointer = -1;

	/**
	 * Reset internal pointer so that next getNext() call will return the very first transaction
	 */
	public function reset_pointer() {
		$this->pointer = -1;
	}

	/**
	 * Return next transaction
	 *
	 * @return array
	 */
	public function getNext() {
		$this->pointer += 1;
		$this->contain('ClopeAttribute');
		return $this->find('first', array(
			'limit' => 1,
			'offset' => $this->pointer
		));
	}

	/**
	 * Move Transaction from one Cluster to another
	 * 
	 * @param int $transactionID
	 * @param int $fromClusterID
	 * @param int $toClusterID
	 *
	 * @return bool
	 */
	public function moveToCluster($transactionID, $fromClusterID, $toClusterID) {
		if ($fromClusterID == $toClusterID) {
			return false;
		}

		$this->updateAll(
			array('cluster_id' => $toClusterID),
			array('ClopeTransaction.id' => $transactionID)
		);

		return true;
	}

	/**
	 * Cluster ID for given Transaction
	 * 
	 * @param int $transactionCustomID
	 *
	 * @return int
	 */
	public function clusterID ($transactionCustomID) {
		return $this->field('cluster_id', array(
			'custom_id' => $transactionCustomID
		));
	}

}
