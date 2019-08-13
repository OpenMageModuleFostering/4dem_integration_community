<?php

/**
 * Events Observer Model
 *
 * @category   FourMarketing
 * @package    FourMarketing_FourDem
 * @author     FourMarketing Team <devandtrade@devandtrade.it>
 */
class FourMarketing_FourDem_Model_Observer
{
    /**
     * Controllo Credenziali inserite dall'utente dal pannello di configurazione
     * @author Raffaele Capasso
     * @version 1.0
     * @copyright Dev And Trade
     */
    public function check4demConfiguration()
    {
        try
        {
            $isEnabled = Mage::getStoreConfig('fourdem/system_access/active');

            if($isEnabled)
            {
               if(empty(Mage::helper('fourdem')->sessionID))
               {
                  throw new Exception('Nome Utente o Password Errati! Controlla le tue credenziali di accesso.');
               }
            }

            return;
        }
        catch(Exception $e)
        {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            return;
        }
    }

    /**
     * Iscrivo il cliente nella lista "Clienti" durante la registrazione.
     * @author Raffaele Capasso
     * @version 1.0
     * @copyright Dev And Trade
     */
    public function subscribeCustomer(Varien_Event_Observer $observer)
    {
        $customer         = $observer->getCustomer();
        $listID           = Mage::getStoreConfig('fourdem/system_access/customer_list');
        $mapFields        = Mage::getStoreConfig('fourdem/fourdem_mapping');
        $email            = $customer->getEmail();
        $ipAddress        = $_SERVER['REMOTE_ADDR'];
        $customFields     = array();

        foreach($mapFields as $label => $customFieldID)
        {
            if(!empty($customFieldID))
            {
                $customerValueField = $customer->getData($label);

                if(!empty($customerValueField))
                {
                    $key                = 'CustomField'.$customFieldID;
                    $customFields[$key] = '&CustomField'.$customFieldID.'='.$customerValueField;
                }
            }
        }

        Mage::helper('fourdem')->newSubscriber($listID, $email, $ipAddress, $customFields, true, true);
        return;
    }

    public function collectionDataMapping(Varien_Event_Observer $observer)
    {
        $mapFields             = Mage::getStoreConfig('fourdem/fourdem_mapping');
        $customFields          = array();
        $listID                = Mage::getStoreConfig('fourdem/system_access/customer_list');
        $defaultBillingAddress = $observer->getCustomerAddress()->getData();
        $emailCustomer         = Mage::getModel('customer/customer')->load($defaultBillingAddress['parent_id'])->getEmail();

        foreach($mapFields as $label => $customFieldID)
        {
            if(!empty($customFieldID))
            {
                $billingValueField  = $defaultBillingAddress[$label];
                $key                = 'Fields'.$customFieldID;
                $customFields[$key] = '&Fields[CustomField'.$customFieldID.']='.$billingValueField;
            }
        }

        //echo '<pre>'; print_r($customFields); echo '</pre>'; die();
        Mage::helper('fourdem')->updateSubscriber($emailCustomer, $emailCustomer, $listID, $customFields, true);
        return;
    }

    /**
     * Cambio le impostazioni date dall'utente inserite nella fase di registrazione.
     * @author Raffaele Capasso
     * @version 1.0
     * @copyright Dev And Trade
     */
    public function changeSubscribeEmail(Varien_Event_Observer $observer)
    {
        $mapFields        = Mage::getStoreConfig('fourdem/fourdem_mapping');
        $customFields     = array();
        $oldDataCustomer  = $observer->getCustomer()->getOrigData();
        $newDataCustomer  = $observer->getCustomer()->getData();

        $listID           = Mage::getStoreConfig('fourdem/system_access/customer_list');
        $oldEmailCustomer = $oldDataCustomer['email'];
        $newEmailCustomer = $newDataCustomer['email'];
        $customerModel    = Mage::getModel('customer/customer')->load($newDataCustomer['entity_id']);

        if(empty($oldEmailCustomer))
        {
            $oldEmailCustomer = $newEmailCustomer;
        }

        foreach($mapFields as $label => $customFieldID)
        {
            if(!empty($customFieldID))
            {
                $billingValueField = $customerModel->getData($label);

                if(!empty($billingValueField))
                {
                    $key                = 'Fields'.$customFieldID;
                    $customFields[$key] = '&Fields[CustomField'.$customFieldID.']='.$billingValueField;
                }
                else
                {
                    if(is_object($customerModel->getDefaultBillingAddress()))
                    {
                     $billingDefaultAddress = $customerModel->getDefaultBillingAddress()->getData();
                     $billingValueField     = $billingDefaultAddress[$label];
                     $key                   = 'Fields'.$customFieldID;
                     $customFields[$key]    = '&Fields[CustomField'.$customFieldID.']='.$billingValueField;
                    }
                }
            }
        }

        if(isset($newDataCustomer['is_subscribed']) && !$newDataCustomer['is_subscribed'])
        {
            // Se l'utente disabilita la newsletter lo disiscrivo da 4Marketing.it..
            $resource = Mage::getSingleton('core/resource')->getConnection('core_write');
            $email    = $newDataCustomer['email'];
            $user     = $resource->query("SELECT * FROM fourmarketing_fourdem_users WHERE email_address = '".$email."'");
            $userRow  = array_pop($user);
            $idUser   = $userRow['id_fourdem'];

            Mage::helper('fourdem')->unsubscribeSubscriber($listID, $idUser, $email, true);
            return;
        }
        elseif(isset($newDataCustomer['is_subscribed']) && $newDataCustomer['is_subscribed'])
        {
            // Se l'utente abilita la newsletter lo iscrivo nella lista 4Marketing.it impostata dall'utente.
            $email     = $newDataCustomer['email'];
            $ipAddress = $_SERVER['REMOTE_ADDR'];

            Mage::helper('fourdem')->newSubscriber($listID, $email, $ipAddress, $customFields, true, true);
            return;
        }

        Mage::helper('fourdem')->updateSubscriber($oldEmailCustomer, $newEmailCustomer, $listID, $customFields, true);
        return;
    }

    /**
     * Quando il cliente effettua un'acquisto viene iscritto alla lista "Clienti" presente su 4Marketing.
     * @author Raffaele Capasso
     * @version 1.0
     * @copyright Dev And Trade
     */
    public function subscribeCustomerAfterCheckout(Varien_Event_Observer $observer)
    {
        $mapFields          = Mage::getStoreConfig('fourdem/fourdem_mapping');
        $listID             = Mage::getStoreConfig('fourdem/system_access/customer_list');
        $customerQuote      = $observer->getEvent()->getQuote()->getData();
        $billingInformation = $observer->getEvent()->getQuote()->getBillingAddress()->getData();
        $customFields       = array();

        if($customerQuote['checkout_method'] === 'guest')
        {
            $email                = $customerQuote['customer_email'];
            $ipAddress            = $_SERVER['REMOTE_ADDR'];
            $isMessageSuppressed  = true;
            $isCustomerRegistered = Mage::getModel('customer/customer')->setWebsiteId(1)->loadByEmail($email)->getId();

            if($isCustomerRegistered)
            {
                foreach($mapFields as $label => $customFieldID)
                {
                    if(!empty($customFieldID))
                    {
                        $billingValueField  = $billingInformation[$label];
                        $key                = 'Fields'.$customFieldID;
                        $customFields[$key] = '&Fields[CustomField'.$customFieldID.']='.$billingValueField;
                    }
                }

                Mage::helper('fourdem')->updateSubscriber($email, $email, $listID, $customFields);
                return;
            }
            else
            {
                foreach($mapFields as $label => $customFieldID)
                {
                    if(!empty($customFieldID))
                    {
                        $billingValueField = $billingInformation[$label];

                        if(!empty($billingValueField))
                        {
                            $key                = 'CustomField'.$customFieldID;
                            $customFields[$key] = '&CustomField'.$customFieldID.'='.$billingValueField;
                        }
                    }
                }

                Mage::helper('fourdem')->newSubscriber($listID, $email, $ipAddress, $customFields, false, $isMessageSuppressed);
                return;
            }
        }
        else
        {
            $customerOrder        = $observer->getEvent()->getOrder();
            $customer             = $customerOrder->getCustomer();
            //echo '<pre>'; print_r($customer->getData()); echo '</pre>'; die();
            $customerModel        = Mage::getModel('customer/customer')->load($customer->getData('entity_id'));
            $billingAddress       = $customerOrder->getBillingAddress();
            $email                = $customerOrder->getCustomer()->getEmail();
            $isCustomerRegistered = Mage::getModel('customer/customer')->setWebsiteId(1)->loadByEmail($email)->getId();
            $ipAddress            = $_SERVER['REMOTE_ADDR'];

            if($isCustomerRegistered)
            {
                foreach($mapFields as $label => $customFieldID)
                {
                    if(!empty($customFieldID) && $label!='billing_address')
                    {
                        $billingValueField  = $billingAddress->getData($label);
                        $key                = 'Fields'.$customFieldID;
                        $customFields[$key] = '&Fields[CustomField'.$customFieldID.']='.$billingValueField;
                    }
               }

               //echo '<pre>'; print_r($customFields); echo '</pre>'; die();
               Mage::helper('fourdem')->updateSubscriber($email, $email, $listID, $customFields, true);
               return;
            }
            else
            {
               foreach($mapFields as $label => $customFieldID)
               {
                 if(!empty($customFieldID))
                 {
                    $billingValueField = $billingAddress->getData($label);

                    if(!empty($billingValueField))
                    {
                        $key                = 'CustomField'.$customFieldID;
                        $customFields[$key] = '&CustomField'.$customFieldID.'='.$billingValueField;
                    }
                    else
                    {
                        $key                = 'CustomField'.$customFieldID;
                        $customerValueField = $customerModel->getData($label);
                        $customFields[$key] = '&CustomField'.$customFieldID.'='.$customerValueField;
                    }
                 }
               }

               Mage::helper('fourdem')->newSubscriber($listID, $email, $ipAddress, $customFields);
               return;
            }
        }
    }
}