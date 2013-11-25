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
	 * @var bool
	 */
	private $clustering_changed = false;

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
		$this->ClopeCluster->createSchema($this->clusteringID());
		$this->ClopeTransaction->createSchema($this->clusteringID());
		$this->ClopeAttribute->createSchema($this->clusteringID());

		$this->repulsion = $params['repulsion'];

		// Add transactions
		foreach($transactions as $id=>$transaction) {
			$data = array(
				'ClopeTransaction' => array(
					'custom_id' => $id
				),
				'ClopeAttribute' => Hash::map($transaction, '{n}', function($attr){ return array('attribute' => $attr); })
			);
			$this->ClopeTransaction->saveAssociated($data, array('deep' => true));
		}
		unset($transaction);

		// Clustering
		$this->_clusterize();

		// Result
		$groupedByCluster = array();
		foreach ($transactions as $id=>&$transaction) {
			$groupedByCluster[$this->ClopeTransaction->clusterID($id)][$id] = $transaction;
			unset($transactions[$id]);
		}

		return $groupedByCluster;
	}

	/**
	 * Clustering algorithm
	 */
	private function _clusterize() {
		$this->setClusteringIncomplete();
		while (!$this->ifClusteringComplete()) {
			$this->ClopeTransaction->reset_pointer();
			$this->setClusteringComplete();
			while ($transaction = $this->ClopeTransaction->getNext()) {
				$bestClusterID = $this->bestClusterID($transaction, $this->repulsion);
				if ($this->ClopeTransaction->moveToCluster($transaction['ClopeTransaction']['id'], $transaction['ClopeTransaction']['cluster_id'], $bestClusterID)) {
					$this->_updateClusterFeatures($transaction['ClopeTransaction']['cluster_id'], $bestClusterID);
					$this->setClusteringIncomplete();
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
	private function bestClusterID($transaction, $repulsion) {
		$this->_clusterFeatures = array();
		if ($this->ClopeCluster->find('count') == 0 || $this->ClopeCluster->find('count', array('conditions' => 'size = 0')) == 0) {
			$this->ClopeCluster->create();
			$this->ClopeCluster->save(array('width' => 0, 'size' => 0, 'transactions' => 0));
		}
		$delta = 0;
		$bestClusterID = null;
		foreach ($this->ClopeCluster->find('all') as $cluster) {
			if ($transaction['ClopeTransaction']['cluster_id'] == $cluster['ClopeCluster']['id']) {
				$delta = $this->deltaRemove($cluster, $transaction, $repulsion);
			} else {
				$delta = $this->deltaAdd($cluster, $transaction, $repulsion);
			}
			if (!isset($max_delta)
				|| ($delta > $max_delta
					|| ($transaction['ClopeTransaction']['cluster_id'] == $cluster['ClopeCluster']['id'] && $delta == $max_delta)))
			{
				$max_delta = $delta;
				$bestClusterID = $cluster['ClopeCluster']['id'];
			}
		}
		return $bestClusterID;
	}

	/**
	 * Update Cluster Features: size, width, transactions count
	 *
	 * @param int $fromClusterID
	 * @param int $toClusterID
	 */
	private function _updateClusterFeatures($fromClusterID, $toClusterID) {
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
				),
				array('id' => $fromClusterID)
			);
		}

		// Update cluster TO which Transaction was moved
		$this->ClopeCluster->updateAll(
			array(
				'size' => $this->_clusterFeatures[$toClusterID]['size'],
				'width' => $this->_clusterFeatures[$toClusterID]['width'],
				'transactions' => 'transactions + 1'
			),
			array('id' => $toClusterID)
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
	private function deltaAdd($cluster, $transaction, $repulsion) {
		$clusterID = $cluster['ClopeCluster']['id'];
		$sizeNew = $cluster['ClopeCluster']['size'] + count($transaction['ClopeAttribute']);
		$widthNew = $cluster['ClopeCluster']['width'];
		foreach ($transaction['ClopeAttribute'] as $attribute) {
			if ($cluster['ClopeCluster']['transactions'] == 0
				|| $this->ClopeAttribute->countInCluster($attribute['attribute'], $clusterID) == 0)
			{
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
	private function deltaRemove($cluster, $transaction, $repulsion) {
		$clusterID = $cluster['ClopeCluster']['id'];
		$sizeNew = $cluster['ClopeCluster']['size'] - count($transaction['ClopeAttribute']);
		$widthNew = $cluster['ClopeCluster']['width'];
		foreach ($transaction['ClopeAttribute'] as $attribute) {
			if ($this->ClopeAttribute->countInCluster($attribute['attribute'], $clusterID) == 1) $widthNew -= 1;
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
	private function ifClusteringComplete() {
		return !(bool)$this->clustering_changed;
	}

	/**
	 * Mark current Clustering session as Complete
	 */
	private function setClusteringComplete() {
		$this->clustering_changed = false;
	}

	/**
	 * Mark current Clustering session as Incomplete
	 */
	private function setClusteringIncomplete() {
		$this->clustering_changed = true;
	}

	/**
	 * Generate random string
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	private function clusteringId() {
		if (isset($this->clusteringId)) {
			return $this->clusteringId;
		} else {
			$this->clusteringId = substr(md5(microtime()), rand(0, 26), 5);
			return $this->clusteringId;
		}
	}

}