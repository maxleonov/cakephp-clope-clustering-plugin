<?php

/**
 * Tests
 *
 * @author maxleonov <maks.leonov@gmail.com>
 * @link http://book.cakephp.org/2.0/en/development/testing.html Format
 */

/**
 * 
 */
class ClopeTest extends CakeTestCase {

	/**
	 * {@inheritdoc}
	 *
	 * @var array
	 */
	public $fixtures = array(
		'plugin.ClopeClustering.ClopeTransaction',
		'plugin.ClopeClustering.ClopeAttribute',
		'plugin.ClopeClustering.ClopeCluster'
	);

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->clope = ClassRegistry::init('ClopeClustering.Clope');
	}

	/**
	 *
	 */
	public function testBasicFunctionality() {
		$transactions = array(
			array('a1', 'a2', 'a3'),
			array('a1', 'a2', 'a3', 'a4'),
			array('a1', 'a2', 'a3', 'a4'),
			array('a5', 'a6', 'a7'),
			array('a5', 'a6', 'a7'),
			array('a8', 'a9', 'a10'),
			array('a8', 'a9', 'a10'),
			array('a8', 'a9', 'a10', 'a11'),
			array('a8', 'a9', 'a10', 'a12'),
		);

		$params = array(
			'repulsion' => 2.0,
		);

		$result = $this->clope->clusterize($transactions, $params);

		$expected = array(
			(int) 1 => array(
				(int) 0 => array(
					(int) 0 => 'a1',
					(int) 1 => 'a2',
					(int) 2 => 'a3'
				),
				(int) 1 => array(
					(int) 0 => 'a1',
					(int) 1 => 'a2',
					(int) 2 => 'a3',
					(int) 3 => 'a4'
				),
				(int) 2 => array(
					(int) 0 => 'a1',
					(int) 1 => 'a2',
					(int) 2 => 'a3',
					(int) 3 => 'a4'
				)
			),
			(int) 2 => array(
				(int) 0 => array(
					(int) 0 => 'a5',
					(int) 1 => 'a6',
					(int) 2 => 'a7'
				),
				(int) 1 => array(
					(int) 0 => 'a5',
					(int) 1 => 'a6',
					(int) 2 => 'a7'
				)
			),
			(int) 3 => array(
				(int) 0 => array(
					(int) 0 => 'a8',
					(int) 1 => 'a9',
					(int) 2 => 'a10'
				),
				(int) 1 => array(
					(int) 0 => 'a8',
					(int) 1 => 'a9',
					(int) 2 => 'a10'
				),
				(int) 2 => array(
					(int) 0 => 'a8',
					(int) 1 => 'a9',
					(int) 2 => 'a10',
					(int) 3 => 'a11'
				),
				(int) 3 => array(
					(int) 0 => 'a8',
					(int) 1 => 'a9',
					(int) 2 => 'a10',
					(int) 3 => 'a12'
				)
			)
		);

		$this->assertEqual($result, $expected);
	}

}