<?php

/**
 * System configuration form field renderer for mapping MergeVars fields with Magento
 * attributes.
 *
 * @category   DevTrade
 * @package    DevTrade_FourDem
 * @author     DevTrade Team <devandtrade@devandtrade.it>
 */
class FourMarketing_FourDem_Block_Adminhtml_System_Config_Form_Field_Mapfields extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    public function __construct()
    {
        $this->addColumn('magento', array(
            'label' => Mage::helper('fourdem')->__('Magento'),
            'style' => 'width:120px',
        ));
        $this->addColumn('fourdem', array(
            'label' => Mage::helper('fourdem')->__('4Marketing'),
            'style' => 'width:120px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('fourdem')->__('Aggiungi Campo');
        parent::__construct();
    }
}