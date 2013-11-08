--
-- Table structure for table `clope_transactions`
--

DROP TABLE IF EXISTS `clope_transactions`;
CREATE TABLE IF NOT EXISTS `clope_transactions` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `custom_id` varchar(255) collate utf8_bin NOT NULL,
  `cluster_id` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1 COLLATE=latin1_bin;

--
-- Table structure for table `clope_attributes`
--

DROP TABLE IF EXISTS `clope_attributes`;
CREATE TABLE IF NOT EXISTS `clope_attributes` (
  `id` int unsigned NOT NULL auto_increment,
  `transaction_id` smallint(5) unsigned NOT NULL,
  `attribute` varchar(255) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `itransaction_id` (`transaction_id`),
  KEY `iattribute` (`attribute`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1 COLLATE=latin1_bin;

--
-- Table structure for table `clope_clusters`
--

DROP TABLE IF EXISTS `clope_clusters`;
CREATE TABLE IF NOT EXISTS `clope_clusters` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `width` smallint(5) unsigned,
  `size` smallint(5) unsigned,
  `transactions` smallint(5) unsigned,
  PRIMARY KEY  (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1 COLLATE=latin1_bin;
