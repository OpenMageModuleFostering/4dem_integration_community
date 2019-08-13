<?php
class FourMarketing_FourDem_Model_Mysql4_Users extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('fourmarketing_fourdem/users', 'id_fourdem');
    }
}