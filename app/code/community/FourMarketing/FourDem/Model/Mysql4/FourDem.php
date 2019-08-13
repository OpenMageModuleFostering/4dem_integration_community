<?php
class FourMarketing_FourDem_Model_Mysql4_FourDem extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('fourdem/fourdem', 'fourdem_id');
    }
}