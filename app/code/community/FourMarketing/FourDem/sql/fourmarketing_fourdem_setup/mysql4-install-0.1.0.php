<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("
    CREATE TABLE fourmarketing_fourdem_users(
      id_fourdem int(11) NOT NULL,
      firstname text NOT NULL,
      lastname text NOT NULL,
      email_address text NOT NULL,
      own_list text NOT NULL
     )
     ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();