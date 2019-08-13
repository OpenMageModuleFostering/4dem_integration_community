<?php
class FourMarketing_FourDem_SubscribersController extends Mage_Core_Controller_Front_Action
{
    public function newAction()
    {
        $params     = $this->getRequest()->getParams();
        $email      = $params['email'];
        $ipAddress  = $params['ip-address'];
        $listID     = Mage::getStoreConfig('fourdem/system_access/newsletter_list');

        Mage::helper('fourdem')->newSubscriber($listID, $email, $ipAddress);
        $this->_redirect('');
    }
}