<?php

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: Feb 5, 2014
 * Time: 5:06:16 PM
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */

/**
 * AllClopeClusteringTest
 */
class AllClopeClusteringTest extends PHPUnit_Framework_TestSuite {

	/**
	 * 	All ClopeClustering tests suite
	 *
	 * @return PHPUnit_Framework_TestSuite the instance of PHPUnit_Framework_TestSuite
	 */
	public static function suite() {
		$suite = new CakeTestSuite('All ClopeClustering Tests');

		$basePath = App::pluginPath('ClopeClustering') . 'Test' . DS . 'Case' . DS;
		$suite->addTestDirectoryRecursive($basePath);

		return $suite;
	}

}
