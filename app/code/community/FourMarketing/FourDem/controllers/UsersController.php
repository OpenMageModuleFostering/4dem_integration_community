<?php
/*
 * Magento Class For 4Marketing Magento ADMIN
 */
class FourMarketing_FourDem_UsersController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    protected function _initAction()
    {
        $this->loadLayout()
             ->_setActiveMenu('fourdem/fourmarketing_fourdem_users')
             ->_title($this->__('Iscritti Newsletter'))->_title($this->__('4Marketing'));
        
        return $this;
    }

    public function deleteAction()
    {
        $params  = $this->getRequest()->getParams();
        $idUser  = $params['id'];
        $user    = Mage::getModel('fourmarketing_fourdem/users')->load($idUser);

        preg_match_all("/\d+/", $user->getOwnList(), $matches);
        $listID  = $matches[0][0];
        $email   = $user->getData('email_address');

        try
        {
            // Cancello l'utente da Magento e dalla tabella di riferimento..
            $response = Mage::helper('fourdem')->unsubscribeSubscriber($listID, $idUser, $email);

            if($response->Success)
            {
                $user->delete();

                $message = $this->__("L'utente $email è stato cancellato con successo!");
                Mage::getSingleton('core/session')->addSuccess($message);
                $this->_redirectReferer();
            }
            else
            {
                throw new Exception("L'utente $email non è presente in 4Marketing. Può darsi che sia già stato cancellato.");
            }
        }
        catch(Exception $e)
        {
            $message = $e->getMessage();
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirectReferer();
        }
    }

    public function synchronizeAction()
    {
        $start = microtime(true);
        try
        {
           // Clean Magento Table..
           $resource        = Mage::getSingleton('core/resource');
           $writeConnection = $resource->getConnection('core_write');
           $writeConnection->query("TRUNCATE fourmarketing_fourdem_users");

           $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');
           $customers  = $collection->getData();

           //echo '<pre>'; print_r($customers); echo '</pre>'; die();

           $mapFields             = Mage::getStoreConfig('fourdem/fourdem_mapping');
           $customFields          = array();
           $customFieldsUpdate    = array();

           $customerListID        = Mage::getStoreConfig('fourdem/system_access/customer_list');
           $newsletterListID      = Mage::getStoreConfig('fourdem/system_access/newsletter_list');

           $customerList          = Mage::helper('fourdem')->getListInformation($customerListID)->List->Name;
           $newsletterList        = Mage::helper('fourdem')->getListInformation($newsletterListID)->List->Name;

           $customerSubscribers   = Mage::helper('fourdem')->getAllSubscribers($customerListID)->Subscribers;
           $newsletterSubscribers = Mage::helper('fourdem')->getAllSubscribers($newsletterListID)->Subscribers;

           // Prima Fase:  Importo i Clienti Magento nella "Lista Clienti" Di Destinazione..
           foreach($customers as $customer)
           {
                $modelCustomer        = Mage::getModel('customer/customer')->load($customer['entity_id']);
                $addressCustomerModel = Mage::getModel('customer/address')->load($modelCustomer->getDefaultBilling());

                foreach($mapFields as $label => $customFieldID)
                {
                   if(!empty($customFieldID) && $label!='billing_address')
                   {
                       $customerValueField = $modelCustomer->getData($label);

                       if(!empty($customerValueField))
                       {
                           $key                = 'CustomField'.$customFieldID;
                           $customFields[$key] = '&CustomField'.$customFieldID.'='.$customerValueField;
						   
						   $keyUpdate                	   = 'Fields'.$customFieldID;
						   $customFieldsUpdate[$keyUpdate] = '&Fields[CustomField'.$customFieldID.']='.$customerValueField;
                       }
                       else
                       {
						   $customerValueField = $addressCustomerModel->getData($label);
                           $key                = 'CustomField'.$customFieldID;
                           $customFields[$key] = '&CustomField'.$customFieldID.'='.$customerValueField;
						   
						   $keyUpdate                	   = 'Fields'.$customFieldID;
						   $customFieldsUpdate[$keyUpdate] = '&Fields[CustomField'.$customFieldID.']='.$customerValueField;
                       }
                   }
                }

                //echo '<pre>'; print_r($modelCustomer->getData()); echo '</pre>'; die();
                //echo '<pre>'; print_r($mapFields); echo '</pre>'; die();
                //echo '<pre>'; print_r($customFields); echo '</pre>'; die();

                $listID                  = $customerListID;
                $singleAlert             = true;
                $isThereSubscribers      = Mage::helper('fourdem')->getAboutSubscriber($customer['email'], $listID)->Subscribers;

                // Se l'utente è presente nella lista, controllo se sia cancellato oppure no..
                if($isThereSubscribers)
                {
                    $customerConsoleResponse = array_pop($isThereSubscribers);
                    $customerStatusOnConsole = $customerConsoleResponse->SubscriptionStatus;

                    //echo '<pre>'; print_r(var_dump($customerStatusOnConsole)); echo '</pre>'; die();

                    if($customerStatusOnConsole === 'Unsubscribed')
                    {
                        Mage::helper('fourdem')->newSubscriber($listID, $customer['email'], $_SERVER['REMOTE_ADDR'], $customFields, $singleAlert, true);
                    }
                    elseif($customerStatusOnConsole === 'Subscribed')
                    {
                        Mage::helper('fourdem')->updateSubscriber($customer['email'], $customer['email'], $listID, $customFieldsUpdate, true);
                    }
                }
                else
                {
                    // Se l'utente non esite nella lista di destinazione lo iscrivo..
                    Mage::helper('fourdem')->newSubscriber($listID, $customer['email'], $_SERVER['REMOTE_ADDR'], $customFields, $singleAlert);
                }
           }

           // Seconda Fase: Importo Le Liste Nella Tabella di Riferimento in Magento..
           if(!empty($customerListID))
           {
             foreach($customerSubscribers as $subscriber)
             {

               $info      = get_object_vars($subscriber);
               $firstname = $info['CustomField'.$mapFields['firstname']];
               $lastname  = $info['CustomField'.$mapFields['lastname']];

               $insertStatement = "INSERT INTO fourmarketing_fourdem_users VALUES(
                                    ".$subscriber->SubscriberID.",
                                    '$firstname',
                                    '$lastname',
                                    '".$subscriber->EmailAddress."',
                                    'ID: $customerListID / Nome: $customerList')";
               $writeConnection->query($insertStatement);
             }
           }
           else
           {
             // Import Newsletter Users
             foreach($newsletterSubscribers as $subscriber)
             {
               $info      = get_object_vars($subscriber);
               $firstname = $info['CustomField'.$mapFields['firstname']];
               $lastname  = $info['CustomField'.$mapFields['lastname']];

               $insertStatement = "INSERT INTO fourmarketing_fourdem_users VALUES(
                                ".$subscriber->SubscriberID.",
                                '$firstname',
                                '$lastname',
                                '".$subscriber->EmailAddress."',
                                'ID: $newsletterListID / Nome: $newsletterList')";
               $writeConnection->query($insertStatement);
             }
           }

           $time_taken = microtime(true) - $start;
           $minSeconds = date("i:s", $time_taken);

           $message    = $this->__("Tabella Sincronizzata Con Successo! Tempo stimato: ".$minSeconds." secondi");
           Mage::getSingleton('core/session')->addSuccess($message);

           $this->_redirectReferer();
        }
        catch(Exception $e)
        {
           $message = $this->__($e->getMessage());
           Mage::getSingleton('core/session')->addError($message);
        }
    }
}