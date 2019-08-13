<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("
-- DROP TABLE IF EXISTS fourdem_users;
    CREATE TABLE fourdem_users(
      fourdem_id int(11) NOT NULL,
      firstname text NOT NULL,
      lastname text NOT NULL,
      email_address text NOT NULL,
      own_list text NOT NULL
     )
     ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();