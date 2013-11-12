<?php

/**
 * Clope Clustering

 * @link https://drive.google.com/file/d/1---LtHj1jLHCOcCUZChMRZQVirmt9nR6F8Zmw6bgNBBNyiBB6zmWsNuGIOfF Clope Clustering Algorithm

 * @author maxleonov <maks.leonov@gmail.com>
 */


/**
 * 
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
	 * Constructor
	 */
	public function __construct() {
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
		foreach($transactions as $id=>&$transaction) {
			$data = array(
				'ClopeTransaction' => array(
					'custom_id' => $id
				), 
				'ClopeAttribute' => Hash::map($transaction, '{n}', create_function('$attr', 'return array(\'attribute\'=>$attr);'))
			);
			$this->ClopeTransaction->saveAssociated($data, array('deep' => true));
		}

		// Clustering
		$this->_clusterize();

		// Result
		$groupedByCluster = array();
		foreach ($transactions as $id=>&$transaction) {
			$groupedByCluster[$this->ClopeTransaction->clusterID($id)][] = $transaction;
			unset($transactions[$id]);
		}

		return $groupedByCluster;
	}

	/**
	 * 
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
	 * 
	 */
	private function bestClusterID($transaction, $repulsion) {
		$this->clusterFeatures = array();
		if ($this->ClopeCluster->find('count') == 0 || $this->ClopeCluster->find('count', array('conditions' => 'size = 0') ) == 0) {
			$this->ClopeCluster->create();
			$this->ClopeCluster->save(array('width' => 0, 'size' => 0, 'transactions' => 0));
		}
		$delta=0;
		$bestClusterID = null;
		foreach ($this->ClopeCluster->find('all') as $cluster) {
			$delta = ($transaction['ClopeTransaction']['cluster_id'] != $cluster['ClopeCluster']['id'] ? $this->deltaAdd($cluster, $transaction, $repulsion) : $this->deltaRemove($cluster, $transaction, $repulsion));
			if (!isset($max_delta) || ($delta > $max_delta || ($transaction['ClopeTransaction']['cluster_id'] == $cluster['ClopeCluster']['id'] && $delta == $max_delta))) {
				$max_delta = $delta;
				$bestClusterID = $cluster['ClopeCluster']['id'];
			}
		}
		return $bestClusterID;
	}

	/**
	 * 
	 */
	private function _updateClusterFeatures($fromClusterID, $toClusterID) {
		if ($fromClusterID == $toClusterID) {
			return false;
		}

		// Update cluster FROM which Transaction was moved
		if (!is_null($fromClusterID)) {
			$this->ClopeCluster->updateAll(
				array(
					'size' => $this->clusterFeatures[$fromClusterID]['size'], 
					'width' => $this->clusterFeatures[$fromClusterID]['width'],
					'transactions' => 'transactions - 1'
				),
				array('id' => $fromClusterID)
			);
		}

		// Update cluster TO which Transaction was moved
		$this->ClopeCluster->updateAll(
			array(
				'size' => $this->clusterFeatures[$toClusterID]['size'], 
				'width' => $this->clusterFeatures[$toClusterID]['width'],
				'transactions' => 'transactions + 1'
			),
			array('id' => $toClusterID)
		);
	}

	/**
	 * 
	 */
	private function deltaAdd($cluster, $transaction, $repulsion) {
		$clusterID = $cluster['ClopeCluster']['id'];
		$sizeNew = $this->ClopeCluster->size($clusterID) + count($transaction['ClopeAttribute']);
		$widthNew = $this->ClopeCluster->width($clusterID);
		foreach ($transaction['ClopeAttribute'] as $attribute) {
			if ($this->ClopeAttribute->countInCluster($attribute['attribute'], $clusterID) == 0) $widthNew += 1;
		}
		$this->clusterFeatures[$clusterID]['size'] = $sizeNew;
		$this->clusterFeatures[$clusterID]['width'] = $widthNew;
		$exp1 = ($sizeNew * ($this->ClopeCluster->countOfTransactions($clusterID) + 1)) / pow($widthNew, $repulsion);
		$exp2 = ($this->ClopeCluster->width($clusterID) == 0 ? 0 : ($this->ClopeCluster->size($clusterID) * $this->ClopeCluster->countOfTransactions($clusterID)) / pow($this->ClopeCluster->width($clusterID), $repulsion));
		return $exp1 - $exp2;
	}

	/**
	 * 
	 */
	private function deltaRemove($cluster, $transaction, $repulsion) {
		$clusterID = $cluster['ClopeCluster']['id'];
		$sizeNew = $this->ClopeCluster->size($clusterID) - count($transaction['ClopeAttribute']);
		$widthNew = $this->ClopeCluster->width($clusterID);
		foreach ($transaction['ClopeAttribute'] as $attribute) {
			if ($this->ClopeAttribute->countInCluster($attribute['attribute'], $clusterID) == 1) $widthNew -= 1;
		}
		$this->clusterFeatures[$clusterID]['size'] = $sizeNew;
		$this->clusterFeatures[$clusterID]['width'] = $widthNew;
		$exp1 = ($this->ClopeCluster->size($clusterID) * $this->ClopeCluster->countOfTransactions($clusterID)) / pow($this->ClopeCluster->width($clusterID), $repulsion);
		$exp2 = ($widthNew == 0 ? 0 : ($sizeNew * ($this->ClopeCluster->countOfTransactions($clusterID) - 1)) / pow($widthNew, $repulsion));
		return $exp1 - $exp2;
	}

	/**
	 * 
	 */
	private function ifClusteringComplete() {
		return !(bool)$this->clustering_changed;
	}

	/**
	 * 
	 */
	private function setClusteringComplete() {
		$this->clustering_changed=false;
	}

	/**
	 * 
	 */
	private function setClusteringIncomplete() {
		$this->clustering_changed=true;
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