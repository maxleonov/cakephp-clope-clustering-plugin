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
 * Cluster
 *
 * @package ClopeClustering
 * @subpackage Model
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
	public $useTablePattern = 'clope_clusters';

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
		),
		'indexes' => array(
			'isize' => array(
				'column' => 'size',
				'unique' => false
			)
		)
	);

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
	 * {@inheritdoc}
	 * 
	 * @param array $results
	 * @param bool $primary
	 * @return array
	 */
	public function afterFind($results, $primary = false) {
		$results = parent::afterFind($results, $primary);
		foreach ($results as &$cluster) {
			if (!isset($cluster['ClopeTransaction'])) {
				continue;
			}
			$cluster[$this->alias]['attributesCounts'] = $this->_attributesCounts($cluster);
		}

		return $results;
	}

	/**
	 * Calculates attributes counts statistics
	 * 
	 * @param array $cluster
	 * @return array
	 */
	protected function _attributesCounts($cluster) {
		$attributesCounts = array();
		foreach ($cluster['ClopeTransaction'] as $transaction) {
			foreach ($transaction['ClopeAttribute'] as $attribute) {
				$name = $attribute['attribute'];
				$attributesCounts[$name] = isset($attributesCounts[$name]) ? $attributesCounts[$name] + 1: 1;
			}
		}
		return $attributesCounts;
	}

}
