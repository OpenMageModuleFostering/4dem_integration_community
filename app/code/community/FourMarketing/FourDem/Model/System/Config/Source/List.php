<?php

/**
 * 4Marketing lists source file
 *
 * @category   DevTrade
 * @package    DevTrade_FourDem
 * @author     DevTrade Team <devandtrade@devandtrade.it>
 */
class FourMarketing_FourDem_Model_System_Config_Source_List
{
	protected $_lists = null;

	public function __construct()
	{
		if(is_null($this->_lists) && !empty(Mage::helper('fourdem')->sessionID))
        {
			$this->_lists = Mage::helper('fourdem')->getLists();
		}
	}

    public function toOptionArray()
    {
    	$lists      = array();
        $listsByApi = $this->_lists->Lists;

        foreach($listsByApi as $list)
        {
            $lists [] = array(
                'value' => $list->ListID,
                'label' => $list->Name . ' (' . $list->SubscriberCount . ' ' . Mage::helper('fourdem')->__('Membri') . ')'
            );
        }

        return $lists;
    }

}
