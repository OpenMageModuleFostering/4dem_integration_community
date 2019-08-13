<?php

/**
 * Helper - Wrap Connection To 4Marketing
 *
 * @category   DevTrade
 * @package    DevTrade_FourDem
 * @author     DevTrade Team <devandtrade@devandtrade.it>
 */
class FourMarketing_FourDem_Helper_Data extends Mage_Core_Helper_Abstract
{
    public $consoleUrl         = '';
    public $urlConnectionAPI   = "?Command=%s&ResponseFormat=JSON";
    public  $sessionID         = '';
    public static $singleAlert = 1;
    public static $singleAlertSuccess = 1;

    public function __construct()
    {
        
    	$this->consoleUrl = Mage::getStoreConfig('fourdem/system_access/url_console');
		
    	//$this->consoleUrl = 'http://mailchef.4dem.it/api.php';
    	
        $params  = array(
			
			 
        	'username' => '&Username='.Mage::getStoreConfig('fourdem/system_access/username'),
            'password' => '&Password='.Mage::getStoreConfig('fourdem/system_access/password')
        	/*
       		'username' => '&Username=andreabardi',
       		'password' => '&Password=andrea'
       		*/
        );

        $resultJSON  = $this->_getRequestApi('User.Login', $params);

        if($resultJSON->Success)
        {
            $this->sessionID = $resultJSON->SessionID;
        }

        return;
    }

    private function _getRequestApi($method, array $params, $printUrl = false)
    {
        $consoleUrl = $this->consoleUrl.$this->urlConnectionAPI;
        $urlRequest = sprintf($consoleUrl, $method);

        foreach($params as $param)
        {
            $urlRequest .= $param;
        }

        if($printUrl)
        {
          print_r('<strong>'.$urlRequest.'</strong>'); die();
        }

        $curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(array('timeout' => 15));
        $curl->write(Zend_Http_Client::GET, $urlRequest, '1.0');
        preg_match('/\{.*\}/i', $curl->read(), $jsonObj);

        return json_decode(array_pop($jsonObj));
    }

    public function getListInformation($listID)
    {
        $method     = 'List.Get';
        $params     = array(
            'sessionID'  => '&SessionID='.$this->sessionID,
            'listID'     => '&ListID='.$listID
        );

        return $this->_getRequestApi($method, $params);
    }

    public function getLists()
    {
        $method     = 'Lists.Get';
        $params     = array(
            'sessionID'  => '&SessionID='.$this->sessionID,
            'orderField' => '&OrderField=Name',
            'orderType'  => '&OrderType=ASC'
        );

        return $this->_getRequestApi($method, $params);
    }

    public function getCustomFields($listID)
    {
        $method = "CustomFields.Get";
        $params = array(
            'sessionID'  => '&SessionID='.$this->sessionID,
            'orderField' => '&OrderField=FieldName',
            'orderType'  => '&OrderType=ASC',
            'subscriberListID' => '&SubscriberListID='.$listID
        );

        return $this->_getRequestApi($method, $params);
    }

    public function newSubscriber($listID, $email, $ipAddress, $customFields = array(), $singleAlert = false, $isMessageSuppressed = false)
    {
        $method      = 'Subscriber.Subscribe';
        $params      = array(
            'sessionID'    => '&SessionID='.$this->sessionID,
            'listID'       => '&ListID='.$listID,
            'emailAddress' => '&EmailAddress='.$email,
            'ipAddress'    => '&IPAddress='.$ipAddress
        );

        if(!empty($customFields))
        {
           foreach($customFields as $key => $field)
           {
               $params[$key] = str_replace(" ", "%20", $field);
           }
        }

        $fourObject    = $this->_getRequestApi($method, $params);
        $newSubscriber = $fourObject->Subscriber;

        if(!empty($newSubscriber->SubscriberID))
        {
            if(self::$singleAlertSuccess && $singleAlert==true)
            {
                if(self::$singleAlertSuccess==1)
                {
                    if(!$isMessageSuppressed)
                    {
                        self::$singleAlertSuccess++;
                        $message = $this->__('Iscrizione alla newsletter avvenuta con successo!');
                        Mage::getSingleton('core/session')->addSuccess($message);
                    }
                }
            }
        }
        elseif($singleAlert)
        {
            if(self::$singleAlert==1)
            {
                if(!$isMessageSuppressed)
                {
                    self::$singleAlert++;
                    $message = $this->__("Alcuni indirizzi era già presenti nella lista di destinazione.");
                    Mage::getSingleton('core/session')->addError($message);
                }
            }
        }
        else
        {
            if(!$isMessageSuppressed)
            {
                $message = $this->__("Indirizzo email $email già presente nella lista!");
                Mage::getSingleton('core/session')->addError($message);
            }
        }
    }

    public function updateSubscriber($emailAddress, $newEmailAddress, $listID, $customFields, $isMessageSuppressed = false)
    {
        $fourObject = $this->getAllSubscribers($listID, $emailAddress);
        //echo '<pre>'; print_r($fourObject); echo '</pre>'; die();

        try
        {
            if($fourObject->TotalSubscribers)
            {
                $method           = "Subscriber.Update";
                $subscriberObject = array_pop($fourObject->Subscribers);

                $params = array(
                   'sessionID'        => "&SessionID=".$this->sessionID,
                   'subscriberID'     => "&SubscriberID=".$subscriberObject->SubscriberID,
                   'subscriberListID' => "&SubscriberListID=".$listID,
                   'emailAddress'     => "&EmailAddress=".$newEmailAddress,
                );

                if(!empty($customFields))
                {
                    foreach($customFields as $key => $field)
                    {
                        $params[$key] = str_replace(" ", "%20", $field);
                    }
                }

                $params['access'] = "&Access=admin";
                //echo '<pre>'; print_r($params); echo '</pre>'; die();
                $fourObject       = $this->_getRequestApi($method, $params);

                if($fourObject->Success)
                {
                    if(!$isMessageSuppressed)
                    {
                        $message = $this->__('Aggiornamento Newsletter: I Tuoi Dati Sono Stati Aggiornati Con Successo!');
                        Mage::getSingleton('core/session')->addSuccess($message);
                    }
                }
            }
        }
        catch(Exception $e)
        {
            $message = $this->__($e->getMessage());
            Mage::getSingleton('core/session')->addError($message);
        }

        return;
    }

    public function unsubscribeSubscriber($listID, $idSubscriber, $emailAddress)
    {
        $method = "Subscriber.Unsubscribe";
        $params = array(
            'sessionID'        => '&SessionID='.$this->sessionID,
            'subscriberListID' => '&ListID='.$listID,
            'subscribers'      => '&SubscriberID='.$idSubscriber,
            'emailAddress'     => '&EmailAddress='.$emailAddress
        );

        return $this->_getRequestApi($method, $params);
    }

    public function getAllSubscribers($listID, $emailAddress = NULL)
    {
        $method = "Subscribers.Get";
        $params = array(
            'sessionID'         => "&SessionID=".$this->sessionID,
            'orderField'        => "&OrderField=EmailAddress",
            'orderType'         => "&OrderType=DESC",
            'recordsFrom'       => "&RecordsFrom=0",
            //'recordPerRequest'  => "&RecordsPerRequest=1",
            'searchField'       => "&SearchField=EmailAddress",
            'subscriberSegment' => "&SubscriberSegment=Active",
            'subscriberListID'  => "&SubscriberListID=".$listID,
        );

        if($emailAddress!=NULL)
        {
            $params['searchKeyword'] = "&SearchKeyword=".$emailAddress;
        }

        return $this->_getRequestApi($method, $params);
    }

    public function getAboutSubscriber($emailAddress, $listID)
    {
        $method = "Subscribers.Get";
        $params = array(
            'sessionID'         => "&SessionID=".$this->sessionID,
            'orderField'        => "&OrderField=EmailAddress",
            'orderType'         => "&OrderType=DESC",
            'recordsFrom'       => "&RecordsFrom=0",
            'recordPerRequest'  => "&RecordsPerRequest=1",
            'searchKeyword'     => "&SearchKeyword=".$emailAddress,
            'searchField'       => "&SearchField=EmailAddress",
            'subscriberSegment' => "&SubscriberSegment=Active",
            'subscriberListID'  => "&SubscriberListID=".$listID,
        );

        return $this->_getRequestApi($method, $params);
    }
}