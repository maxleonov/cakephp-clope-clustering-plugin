<?php

/**
 * Cluster

 * @author maxleonov <maks.leonov@gmail.com>
 */

/**
 * 
 */
class ClopeCluster extends AppModel {

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
