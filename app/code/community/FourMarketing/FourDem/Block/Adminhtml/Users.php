<?php
 class FourMarketing_FourDem_Block_Adminhtml_Users extends Mage_Adminhtml_Block_Widget_Grid_Container
 {
   public function __construct()
   {
      $this->_blockGroup     = 'fourmarketing_fourdem';
      $this->_controller     = 'adminhtml_users';
      $this->_headerText     = $this->__('Clienti Sincronizzati');

      $this->_addButton('button_synchronize', array(
           'label'     => Mage::helper('fourdem')->__('Sincronizza Magento/4Dem'),
           'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/synchronize') .'\')',
           'class'     => 'remove',
      ));

      parent::__construct();
      $this->_removeButton('add');
   }

   public function getHeaderCssClass()
   {
     return 'icon-head head-newsletter-list';
   }
 }