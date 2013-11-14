<?php

/**
 * Cluster
 *
 * @author maxleonov <maks.leonov@gmail.com>
 *
 * @package ClopeClustering
 * @subpackage Model
 */

App::uses('ClopeSchema', 'ClopeClustering.Model');

/**
 * 
 */
class ClopeCluster extends ClopeSchema {

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $hasMany = array(
		'ClopeTransaction' => array(
			'className' => 'ClopeClustering.ClopeTransaction',
			'foreignKey' => 'cluster_id'
		)
	);

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	public $useTable = 'clope_clusters';

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
		'width' => array(
			'type' => 'integer',
			'null' => true,
			'default' => null,
			'length' => 5
		),
		'size' => array(
			'type' => 'integer',
			'null' => true,
			'default' => null,
			'length' => 5
		),
		'transactions' => array(
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
	 * Cluster size
	 * 
	 * @param int $clusterID
	 *
	 * @return int
	 */
	public function size($clusterID) {
		$this->id = $clusterID;
		return $this->field('size');
	}

	/**
	 * Cluser width
	 * 
	 * @param int $clusterID
	 *
	 * @return int
	 */
	public function width($clusterID) {
		$this->id = $clusterID;
		return $this->field('width');
	}

	/**
	 * Count of Transactions in given Cluster
	 * 
	 * @param int $clusterID
	 *
	 * @return int
	 */
	public function countOfTransactions($clusterID) {
		$this->id = $clusterID;
		return $this->field('transactions');
	}

	/**
	 * 
	 */
	public function getInfo($id) {
		$cluster = $this->find('first', array(
			'conditions' => array('id' => $id),
			'fields' => array('size', 'width', 'transactions')
		));
		return $cluster['ClopeCluster'];
	}

 }
