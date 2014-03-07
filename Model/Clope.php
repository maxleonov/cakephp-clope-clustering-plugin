<?php

/**
 * Clope Clustering
 *
 * @link https://drive.google.com/file/d/1---LtHj1jLHCOcCUZChMRZQVirmt9nR6F8Zmw6bgNBBNyiBB6zmWsNuGIOfF Clope Clustering Algorithm
 *
 * @author maxleonov <maks.leonov@gmail.com>
 *
 * @package ClopeClustering
 * @subpackage Model
 */

/**
 * Clope Clustering
 *
 * @package ClopeClustering
 * @subpackage Model
 */
class Clope extends AppModel {

	/**
	 * {@inheritdoc}
	 *
	 * @var bool
	 */
	public $useTable = false;

	/**
	 * True if clustering is not finished
	 * 
	 * @var bool
	 */
	protected $_clusteringChanged = false;

	/**
	 * Clusters
	 *
	 * @var array 
	 */
	protected $_clusters = null;

	/**
	 * {@inheritdoc}
	 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct();

		$this->ClopeTransaction = ClassRegistry::init('ClopeClustering.ClopeTransaction');
		$this->ClopeCluster = ClassRegistry::init('ClopeClustering.ClopeCluster');
		$this->ClopeAttribute = ClassRegistry::init('ClopeClustering.ClopeAttribute');
	}

	/**
	 * Clusterize Transactions
	 *
	 * @param array $transactions array((int)ID => array((int|string)attr1, attr2, ...), ...)
	 * @param array $params array('repulsion' => (float)$number)
	 *
	 * @return array
	 */
	public function clusterize($transactions, $params) {
		$this->repulsion = $params['repulsion'];

		// Add transactions
		foreach ($transactions as $id => $transaction) {
			$data = array(
				'ClopeTransaction' => array(
					'custom_id' => $id
				),
				'ClopeAttribute' => Hash::map($transaction, '{n}', function($attr) {
					return array('attribute' => $attr);
				})
			);
			$this->ClopeTransaction->saveAssociated($data, array('deep' => true));
		}
		unset($transaction);

		// Clustering
		$this->_clusterize();

		// Result
		$groupedByCluster = array();
		foreach ($transactions as $id => &$transaction) {
			$groupedByCluster[$this->ClopeTransaction->clusterID($id)][$id] = $transaction;
			unset($transactions[$id]);
		}

		return $groupedByCluster;
	}

	/**
	 * Clustering algorithm
	 */
	protected function _clusterize() {
		$this->_resetClusters();
		$this->_setClusteringIncomplete();
		while (!$this->_ifClusteringComplete()) {
			$this->ClopeTransaction->resetPointer();
			$this->_setClusteringComplete();
			while ($transaction = $this->ClopeTransaction->getNext()) {
				$bestClusterID = $this->_bestClusterID($transaction, $this->repulsion);
				if ($this->ClopeTransaction->moveToCluster($transaction['ClopeTransaction']['id'], $transaction['ClopeTransaction']['cluster_id'], $bestClusterID)) {
					$this->_updateClusterFeatures($transaction['ClopeTransaction']['cluster_id'], $bestClusterID);
					$this->_setClusteringIncomplete();
					$this->_resetClusters(array($transaction['ClopeTransaction']['cluster_id'], $bestClusterID));
				}
			}
		}
	}

	/**
	 * Find Best Cluster for given Transaction
	 *
	 * @param array $transaction
	 * @param float $repulsion
	 *
	 * @return int
	 */
	protected function _bestClusterID($transaction, $repulsion) {
		$this->_clusterFeatures = array();
		$delta = 0;
		$bestClusterID = null;
		foreach ($this->_getClusters() as $cluster) {
			if ($transaction['ClopeTransaction']['cluster_id'] == $cluster['ClopeCluster']['id']) {
				$delta = $this->_deltaRemove($cluster, $transaction, $repulsion);
			} else {
				$delta = $this->_deltaAdd($cluster, $transaction, $repulsion);
			}
			if (!isset($maxDelta) || ($delta > $maxDelta || ($transaction['ClopeTransaction']['cluster_id'] == $cluster['ClopeCluster']['id'] && $delta == $maxDelta))) {
				$maxDelta = $delta;
				$bestClusterID = $cluster['ClopeCluster']['id'];
			}
		}
		return $bestClusterID;
	}

	/**
	 * Returns all clusters
	 * 
	 * @return array
	 */
	protected function _getClusters() {
		if (!$this->_clusters) {
			$clusters = $this->ClopeCluster->find('all', array(
				'contain' => array('ClopeTransaction' => array(
						'ClopeAttribute'
					))
			));
			$this->_clusters = array();
			foreach ($clusters as $cluster) {
				$this->_clusters[$cluster['ClopeCluster']['id']] = $cluster;
			}
		}
		return $this->_clusters;
	}

	/**
	 * Reset clusters. If specified $clustersIds then resets only this clusters.
	 * Also handle zero cluster
	 * 
	 * @param array $clustersIds
	 */
	protected function _resetClusters($clustersIds = null) {
		$zeroCreated = $this->_createZeroCluster();
		if (!$clustersIds) {
			$this->_clusters = null;
			return;
		}
		foreach (array_filter($clustersIds) as $clustersId) {
			$this->_clusters[$clustersId] = $this->ClopeCluster->find('first', array(
				'conditions' => array(
					'id' => $clustersId
				),
				'contain' => array('ClopeTransaction' => array(
						'ClopeAttribute'
					))
			));
		}

		if ($zeroCreated) {
			$zeroCluster = $this->ClopeCluster->find('first', array(
				'conditions' => array(
					'size' => 0
				),
				'contain' => array('ClopeTransaction' => array(
						'ClopeAttribute'
					))
			));
			$this->_clusters[$zeroCluster['ClopeCluster']['id']] = $zeroCluster;
		}
	}

	/**
	 * Create zero cluster if it not already created
	 * 
	 * @return bool True if created, false if it was created previously
	 */
	protected function _createZeroCluster() {
		$zeroClusterExists = (bool)$this->ClopeCluster->find('first', array(
					'conditions' => array(
						'size' => 0
					), 'fields' => 'id')
		);
		if (!$zeroClusterExists) {
			$this->ClopeCluster->create();
			$this->ClopeCluster->save(array('width' => 0, 'size' => 0, 'transactions' => 0));
		}
		return !$zeroClusterExists;
	}

	/**
	 * Update Cluster Features: size, width, transactions count
	 *
	 * @param int $fromClusterID
	 * @param int $toClusterID
	 */
	protected function _updateClusterFeatures($fromClusterID, $toClusterID) {
		if ($fromClusterID == $toClusterID) {
			return false;
		}

		// Update cluster FROM which Transaction was moved
		if (!is_null($fromClusterID)) {
			$this->ClopeCluster->updateAll(
					array(
				'size' => $this->_clusterFeatures[$fromClusterID]['size'],
				'width' => $this->_clusterFeatures[$fromClusterID]['width'],
				'transactions' => 'transactions - 1'
					), array('id' => $fromClusterID)
			);
		}

		// Update cluster TO which Transaction was moved
		$this->ClopeCluster->updateAll(
				array(
			'size' => $this->_clusterFeatures[$toClusterID]['size'],
			'width' => $this->_clusterFeatures[$toClusterID]['width'],
			'transactions' => 'transactions + 1'
				), array('id' => $toClusterID)
		);
	}

	/**
	 * Computes Profit function
	 * Finds out how "good" it is to Put given Transaction into given Cluster.
	 *
	 * @param array $cluster
	 * @param array $transaction
	 * @param float $repulsion
	 *
	 * @return float
	 */
	protected function _deltaAdd($cluster, $transaction, $repulsion) {
		$clusterID = $cluster['ClopeCluster']['id'];
		$sizeNew = $cluster['ClopeCluster']['size'] + count($transaction['ClopeAttribute']);
		$widthNew = $cluster['ClopeCluster']['width'];
		foreach ($transaction['ClopeAttribute'] as $attribute) {
			if ($cluster['ClopeCluster']['transactions'] == 0 || $this->ClopeAttribute->countInCluster($attribute['attribute'], $cluster) == 0) {
				$widthNew += 1;
			}
		}
		$this->_clusterFeatures[$clusterID]['size'] = $sizeNew;
		$this->_clusterFeatures[$clusterID]['width'] = $widthNew;

		$exp1 = ($sizeNew * ($cluster['ClopeCluster']['transactions'] + 1)) / pow($widthNew, $repulsion);

		if ($cluster['ClopeCluster']['width'] == 0) {
			$exp2 = 0;
		} else {
			$exp2 = $cluster['ClopeCluster']['size'] * $cluster['ClopeCluster']['transactions'];
			$exp2 /= pow($cluster['ClopeCluster']['width'], $repulsion);
		}

		return $exp1 - $exp2;
	}

	/**
	 * Computes Profit function
	 * Finds out how "good" it is to Remove given Transaction from given Cluster.
	 *
	 * @param array $cluster
	 * @param array $transaction
	 * @param float $repulsion
	 *
	 * @return float
	 */
	protected function _deltaRemove($cluster, $transaction, $repulsion) {
		$clusterID = $cluster['ClopeCluster']['id'];
		$sizeNew = $cluster['ClopeCluster']['size'] - count($transaction['ClopeAttribute']);
		$widthNew = $cluster['ClopeCluster']['width'];
		foreach ($transaction['ClopeAttribute'] as $attribute) {
			if ($this->ClopeAttribute->countInCluster($attribute['attribute'], $cluster) == 1) {
				$widthNew -= 1;
			}
		}
		$this->_clusterFeatures[$clusterID]['size'] = $sizeNew;
		$this->_clusterFeatures[$clusterID]['width'] = $widthNew;

		$exp1 = $cluster['ClopeCluster']['size'] * $cluster['ClopeCluster']['transactions'];
		$exp1 /= pow($cluster['ClopeCluster']['width'], $repulsion);

		if ($widthNew == 0) {
			$exp2 = 0;
		} else {
			$exp2 = ($sizeNew * ($cluster['ClopeCluster']['transactions'] - 1)) / pow($widthNew, $repulsion);
		}

		return $exp1 - $exp2;
	}

	/**
	 * Returns if current Clustering procedure is complete
	 *
	 * @return bool
	 */
	protected function _ifClusteringComplete() {
		return !(bool)$this->_clusteringChanged;
	}

	/**
	 * Mark current Clustering session as Complete
	 */
	protected function _setClusteringComplete() {
		$this->_clusteringChanged = false;
	}

	/**
	 * Mark current Clustering session as Incomplete
	 */
	protected function _setClusteringIncomplete() {
		$this->_clusteringChanged = true;
	}

}
