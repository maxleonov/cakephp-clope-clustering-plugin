<?php

/**
 * Attribute

 * @author maxleonov <maks.leonov@gmail.com>
 */

/**
 * 
 */
class ClopeAttribute extends AppModel {

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'ClopeTransaction' => array(
			'className' => 'ClopeClustering.ClopeTransaction',
			'foreignKey' => 'id'
		)
	);

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	public $useTable = 'clope_attributes';

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $actsAs = array('Containable');

	/**
	 * Count of given Attribute in given Cluster
	 * 
	 * @param string $attribute
	 * @param int $clusterId
	 *
	 * @return int
	 */
	public function countInCluster($attribute, $clusterId) {
		$transaction_ids = $this->ClopeTransaction->find('list', array(
			'conditions' => array('cluster_id' => $clusterId),
			'fields' => array('ClopeTransaction.id')
		));

		if (empty($transaction_ids)) {
			return 0;
		}

		return $this->find('count', array(
			'conditions' => array(
				'transaction_id' => $transaction_ids, 
				'attribute' => $attribute
		)));
	}

 }
