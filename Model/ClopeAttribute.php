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
	protected $_schema = array(
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
			),
			'itransaction_attribute' => array(
				'column' => array('transaction_id', 'attribute'),
				'unique' => false
			),
		)
	);

	/**
	 * Count of given Attribute in given Cluster
	 *
	 * @param string $attributeName
	 * @param array $cluster
	 *
	 * @return int
	 */
	public function countInCluster($attributeName, $cluster) {
		$attributesCounts = $cluster['ClopeCluster']['attributesCounts'];
		return empty($attributesCounts[$attributeName]) ? 0 : $attributesCounts[$attributeName];
	}

}
