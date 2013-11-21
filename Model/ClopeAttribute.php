<?php

/**
 * Attribute
 *
 * @author maxleonov <maks.leonov@gmail.com>
 *
 * @package ClopeClustering
 * @subpackage Model
 */

App::uses('ClopeSchema', 'ClopeClustering.Model');

/**
 * Attribute
 *
 * @package ClopeClustering
 * @subpackage Model
 */
class ClopeAttribute extends ClopeSchema {

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
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $_schema = array(
		'id' => array(
			'type' => 'integer',
			'null' => false,
			'default' => null,
			'length' => 10,
			#'unsigned' => true,
			'key' => 'primary'
		),
		'transaction_id' => array(
			'type' => 'integer',
			'null' => false,
			'default' => null,
			'key' => 'mul',
			'length' => 5
		),
		'attribute' => array(
			'type' => 'string',
			'null' => false,
			'default' => null,
			'length' => 255,
			'collate' => 'utf8_bin',
			'charset' => 'utf8'
		),
		'indexes' => array(
			'itransaction_id' => array(
				'column' => 'transaction_id',
				'unique' => false
			)
		)
	);

	/**
	 * {@inheritdoc}
	 *
	 * @var int
	 */
	public $recursive = -1;

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
