cakephp-clope-clustering-plugin
===============================

Cake-PHP plugin for CLOPE clustering algorithm


## Installation

	cd my_cake_app/app
	git clone git://github.com/maxleonov/cakephp-clope-clustering-plugin.git Plugin/ClopeClustering


add plugin loading in Config/bootstrap.php

	CakePlugin::load('ClopeClustering');

add tables from `docs/Database/database.sql`

## Usage
```php
$Clope = ClassRegistry::init('ClopeClustering.Clope');

$params = array(
  'repulsion' => 2.0,
);
  
$transactions = array(
  array('a1', 'a2', 'a3'),
  array('a1', 'a2', 'a3', 'a4'),
  array('a1', 'a2', 'a3', 'a4'),
  array('a5', 'a6', 'a7'),
  array('a5', 'a6', 'a7'),
  array('a8', 'a9', 'a10')
);  

$Clope->clusterize($transactions, $params);
```
