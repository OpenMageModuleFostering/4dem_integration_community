<?php

/**
 * Events Observer Model
 *
 * @category   DevTrade
 * @package    DevTrade_FourDem
 * @author     DevTrade Team <devandtrade@devandtrade.it>
 */
class FourMarketing_FourDem_Model_Observer {
	public function changeSystemConfig(Varien_Event_Observer $observer) {
		
		/**
		 * CUSTOM FIELD MODEL IS
		 * <firstname translate="comment">
		 * <label>Nome</label>
		 * <frontend_type>select</frontend_type>
		 * <sort_order>1</sort_order>
		 * <show_in_default>1</show_in_default>
		 * <show_in_website>1</show_in_website>
		 * <show_in_store>1</show_in_store>
		 * <source_model>fourdem/adminhtml_system_source_fields</source_model>
		 * </firstname>
		 */
		
		// get init sections and tabs
		$config = $observer->getConfig ();
		
		// get tab 'advanced', change sort order and label
		$advancedTab = $config->getNode ( 'tabs/fourdem_config' );
		$advancedTab->sort_order = 1;
		// $advancedTab->label .= ' (on top TOTAL)';
		
		// Get extension custom field section
		$adminCustomFields = $config->getNode ( 'sections/fourdem/groups/fourdem_mapping/fields' );
		$adminFieldsCollection = Mage::getResourceModel ( 'customer/attribute_collection' )->addFieldToFilter ( 'is_user_defined', '1' )->getItems ();
		foreach ( $adminFieldsCollection as $modelField ) {
			$adminFieldsToAddXmlString = '
					<' . $modelField->getAttributeCode () . ' translate="comment">
			 		<label>' . $modelField->getFrontendLabel () . '</label>
					<frontend_type>select</frontend_type>
					<sort_order>100</sort_order>
					<show_in_default>1</show_in_default>
					<show_in_website>1</show_in_website>
					<show_in_store>1</show_in_store>
					<source_model>fourdem/adminhtml_system_source_fields</source_model>
					</' . $modelField->getAttributeCode () . '>';
			// Create an xml formatted string for custom fields to add
			$adminFieldsToAddXml = new Mage_Core_Model_Config_Element ( $adminFieldsToAddXmlString );
			$adminCustomFields->appendChild ( $adminFieldsToAddXml );
		}
		// Create an xml formatted string for custom fields to add
		// $adminFieldsToAddXml = new Mage_Core_Model_Config_Element ( $adminFieldsToAddXmlString );
		
		// Append new fields to section
		// $adminCustomFields->appendChild ( $adminFieldsToAddXml );
		
		return $this;
	}
	
	/**
	 * Controllo Credenziali inserite dall'utente dal pannello di configurazione
	 *
	 * @author Raffaele Capasso
	 * @version 1.0
	 * @copyright Dev And Trade
	 */
	public function check4demConfiguration() {
		try {
			$isEnabled = Mage::getStoreConfig ( 'fourdem/system_access/active' );
			
			if ($isEnabled) {
				if (empty ( Mage::helper ( 'fourdem' )->sessionID )) {
					throw new Exception ( 'Nome Utente o Password Errati! Controlla le tue credenziali di accesso.' );
				}
			}
			
			return;
		} catch ( Exception $e ) {
			Mage::getSingleton ( 'core/session' )->addError ( $e->getMessage () );
			return;
		}
	}
	
	/**
	 * Iscrivo il cliente nella lista "Clienti" durante la registrazione.
	 *
	 * @author Raffaele Capasso
	 * @version 1.0
	 * @copyright Dev And Trade
	 */
	public function subscribeCustomer(Varien_Event_Observer $observer) {
		$customer = $observer->getCustomer ();
		$listID = Mage::getStoreConfig ( 'fourdem/system_access/customer_list' );
		$mapFields = Mage::getStoreConfig ( 'fourdem/fourdem_mapping' );
		$email = $customer->getEmail ();
		$ipAddress = $_SERVER ['REMOTE_ADDR'];
		$customFields = array ();
		
		foreach ( $mapFields as $label => $customFieldID ) {
			if (! empty ( $customFieldID )) {
				$customerValueField = $customer->getData ( $label );
				// ************************** INIZIO MODIFICA CAMPI PERSONALIZZATI SELECT *************************************
				// Step 1 - Verifica se il campo è personalizzato e di tipo select
				$Custom_Var = Mage::getResourceModel ( 'customer/attribute_collection' )->addFieldToFilter ( 'is_user_defined', '1' )->addFieldToFilter ( 'attribute_code', $label )->addFieldToFilter ( 'frontend_input', 'select' )->getItems ();
				// Step 2 - Carica il valore testuale della selezione (il campo contiene l'ID della option selezionata dall'utente
				if (! empty ( $Custom_Var ) && isset ( $Custom_Var )) {
					$customerModel = Mage::getModel ( 'customer/customer' ); /* ->setStoreId ( $modelCustomer->getData ( 'store_id' ) ); */
					$attr = $customerModel->getResource ()->getAttribute ( $label );
					if ($attr->usesSource ()) {
						$currentStore = Mage::app ()->getStore ()->getCode ();
						Mage::app ()->getStore ()->setId ( 0 );
						$billingValueField = $attr->getSource ()->getOptionText ( $billingValueField );
						Mage::app ()->getStore ()->setId ( $currentStore );
					}
				}
				// ************************** FINE MODIFICA CAMPI PERSONALIZZATI SELECT ************************************
				// Website and WebStore names
				if ($label == "website_id") {
					$customerValueField = Mage::app ()->getWebsite ( $customerValueField )->getName ();
				}
				if ($label == "store_id") {
					$customerValueField = Mage::app ()->getStore ( $customerValueField )->getName ();
				}
				// ----- end ---
				
				if (! empty ( $customerValueField )) {
					$key = 'CustomField' . $customFieldID;
					$customFields [$key] = '&CustomField' . $customFieldID . '=' . $customerValueField;
				}
			}
		}
		
		Mage::helper ( 'fourdem' )->newSubscriber ( $listID, $email, $ipAddress, $customFields, true, true );
		return;
	}
	public function collectionDataMapping(Varien_Event_Observer $observer) {
		$mapFields = Mage::getStoreConfig ( 'fourdem/fourdem_mapping' );
		$customFields = array ();
		$listID = Mage::getStoreConfig ( 'fourdem/system_access/customer_list' );
		$defaultBillingAddress = $observer->getCustomerAddress ()->getData ();
		$emailCustomer = Mage::getModel ( 'customer/customer' )->load ( $defaultBillingAddress ['parent_id'] )->getEmail ();
		
		foreach ( $mapFields as $label => $customFieldID ) {
			if (! empty ( $customFieldID )) {
				$billingValueField = $defaultBillingAddress [$label];
				
				// ************************** INIZIO MODIFICA CAMPI PERSONALIZZATI SELECT *************************************
				// Step 1 - Verifica se il campo è personalizzato e di tipo select
				$Custom_Var = Mage::getResourceModel ( 'customer/attribute_collection' )->addFieldToFilter ( 'is_user_defined', '1' )->addFieldToFilter ( 'attribute_code', $label )->addFieldToFilter ( 'frontend_input', 'select' )->getItems ();
				// Step 2 - Carica il valore testuale della selezione (il campo contiene l'ID della option selezionata dall'utente
				if (! empty ( $Custom_Var ) && isset ( $Custom_Var )) {
					$customerModel = Mage::getModel ( 'customer/customer' ); /* ->setStoreId ( $modelCustomer->getData ( 'store_id' ) ); */
					$attr = $customerModel->getResource ()->getAttribute ( $label );
					if ($attr->usesSource ()) {
						$currentStore = Mage::app ()->getStore ()->getCode ();
						Mage::app ()->getStore ()->setId ( 0 );
						$billingValueField = $attr->getSource ()->getOptionText ( $billingValueField );
						Mage::app ()->getStore ()->setId ( $currentStore );
					}
				}
				// ************************** FINE MODIFICA CAMPI PERSONALIZZATI SELECT ************************************
				// Website and WebStore names
				if ($label == "website_id") {
					$billingValueField = Mage::app ()->getWebsite ( $billingValueField )->getName ();
				}
				if ($label == "store_id") {
					$billingValueField = Mage::app ()->getStore ( $billingValueField )->getName ();
				}
				// ----- end ---
				
				$key = 'Fields' . $customFieldID;
				$customFields [$key] = '&Fields[CustomField' . $customFieldID . ']=' . $billingValueField;
			}
		}
		
		// echo '<pre>'; print_r($customFields); echo '</pre>'; die();
		Mage::helper ( 'fourdem' )->updateSubscriber ( $emailCustomer, $emailCustomer, $listID, $customFields, true );
		return;
	}
	
	/**
	 * Cambio le impostazioni date dall'utente inserite nella fase di registrazione.
	 *
	 * @author Raffaele Capasso
	 * @version 1.0
	 * @copyright Dev And Trade
	 */
	public function changeSubscribeEmail(Varien_Event_Observer $observer) {
		$mapFields = Mage::getStoreConfig ( 'fourdem/fourdem_mapping' );
		$customFields = array ();
		$oldDataCustomer = $observer->getCustomer ()->getOrigData ();
		$newDataCustomer = $observer->getCustomer ()->getData ();
		
		$listID = Mage::getStoreConfig ( 'fourdem/system_access/customer_list' );
		$oldEmailCustomer = $oldDataCustomer ['email'];
		$newEmailCustomer = $newDataCustomer ['email'];
		$customerModel = Mage::getModel ( 'customer/customer' )->load ( $newDataCustomer ['entity_id'] );
		
		if (empty ( $oldEmailCustomer )) {
			return;
			$oldEmailCustomer = $newEmailCustomer;
		}
		
		foreach ( $mapFields as $label => $customFieldID ) {
			if (! empty ( $customFieldID )) {
				$billingValueField = $customerModel->getData ( $label );
				
				if (! empty ( $billingValueField )) {
					
					// ************************** INIZIO MODIFICA CAMPI PERSONALIZZATI SELECT *************************************
					// Step 1 - Verifica se il campo è personalizzato e di tipo select
					$Custom_Var = Mage::getResourceModel ( 'customer/attribute_collection' )->addFieldToFilter ( 'is_user_defined', '1' )->addFieldToFilter ( 'attribute_code', $label )->addFieldToFilter ( 'frontend_input', 'select' )->getItems ();
					// Step 2 - Carica il valore testuale della selezione (il campo contiene l'ID della option selezionata dall'utente
					if (! empty ( $Custom_Var ) && isset ( $Custom_Var )) {
						$customerModel = Mage::getModel ( 'customer/customer' ); /* ->setStoreId ( $modelCustomer->getData ( 'store_id' ) ); */
						$attr = $customerModel->getResource ()->getAttribute ( $label );
						if ($attr->usesSource ()) {
							$currentStore = Mage::app ()->getStore ()->getCode ();
							Mage::app ()->getStore ()->setId ( 0 );
							$billingValueField = $attr->getSource ()->getOptionText ( $billingValueField );
							Mage::app ()->getStore ()->setId ( $currentStore );
						}
					}
					// ************************** FINE MODIFICA CAMPI PERSONALIZZATI SELECT ************************************
					// Website and WebStore names
					if ($label == "website_id") {
						$billingValueField = Mage::app ()->getWebsite ( $billingValueField )->getName ();
					}
					if ($label == "store_id") {
						$billingValueField = Mage::app ()->getStore ( $billingValueField )->getName ();
					}
					// ----- end ---
					
					$key = 'Fields' . $customFieldID;
					$customFields [$key] = '&Fields[CustomField' . $customFieldID . ']=' . $billingValueField;
				} else {
					if (is_object ( $customerModel->getDefaultBillingAddress () )) {
						$billingDefaultAddress = $customerModel->getDefaultBillingAddress ()->getData ();
						$billingValueField = $billingDefaultAddress [$label];
						
						// ************************** INIZIO MODIFICA CAMPI PERSONALIZZATI SELECT *************************************
						// Step 1 - Verifica se il campo è personalizzato e di tipo select
						$Custom_Var = Mage::getResourceModel ( 'customer/attribute_collection' )->addFieldToFilter ( 'is_user_defined', '1' )->addFieldToFilter ( 'attribute_code', $label )->addFieldToFilter ( 'frontend_input', 'select' )->getItems ();
						// Step 2 - Carica il valore testuale della selezione (il campo contiene l'ID della option selezionata dall'utente
						if (! empty ( $Custom_Var ) && isset ( $Custom_Var )) {
							$customerModel = Mage::getModel ( 'customer/customer' ); /* ->setStoreId ( $modelCustomer->getData ( 'store_id' ) ); */
							$attr = $customerModel->getResource ()->getAttribute ( $label );
							if ($attr->usesSource ()) {
								$currentStore = Mage::app ()->getStore ()->getCode ();
								Mage::app ()->getStore ()->setId ( 0 );
								$billingValueField = $attr->getSource ()->getOptionText ( $billingValueField );
								Mage::app ()->getStore ()->setId ( $currentStore );
							}
						}
						// ************************** FINE MODIFICA CAMPI PERSONALIZZATI SELECT ************************************
						// Website and WebStore names
						if ($label == "website_id") {
							$billingValueField = Mage::app ()->getWebsite ( $billingValueField )->getName ();
						}
						if ($label == "store_id") {
							$billingValueField = Mage::app ()->getStore ( $billingValueField )->getName ();
						}
						// ----- end ---
						
						$key = 'Fields' . $customFieldID;
						$customFields [$key] = '&Fields[CustomField' . $customFieldID . ']=' . $billingValueField;
					}
				}
			}
		}
		
		if (isset ( $newDataCustomer ['is_subscribed'] ) && ! $newDataCustomer ['is_subscribed']) {
			// Se l'utente disabilita la newsletter lo disiscrivo da 4Marketing.it..
			$resource = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_write' );
			$email = $newDataCustomer ['email'];
			$user = $resource->query ( "SELECT * FROM devtrade_fourdem_users WHERE email_address = '" . $email . "'" );
			$userRow = array_pop ( $user );
			$idUser = $userRow ['fourdem_id'];
			
			Mage::helper ( 'fourdem' )->unsubscribeSubscriber ( $listID, $idUser, $email, true );
			return;
		} elseif (isset ( $newDataCustomer ['is_subscribed'] ) && $newDataCustomer ['is_subscribed']) {
			// Se l'utente abilita la newsletter lo iscrivo nella lista 4Marketing.it impostata dall'utente.
			$email = $newDataCustomer ['email'];
			$ipAddress = $_SERVER ['REMOTE_ADDR'];
			
			//Mage::helper ( 'fourdem' )->newSubscriber ( $listID, $email, $ipAddress, $customFields, true, true );
			Mage::helper ( 'fourdem' )->updateSubscriber ( $oldEmailCustomer, $newEmailCustomer, $listID, $customFields, true );
			return;
		}
		
		Mage::helper ( 'fourdem' )->updateSubscriber ( $oldEmailCustomer, $newEmailCustomer, $listID, $customFields, true );
		return;
	}
	
	/**
	 * Quando il cliente effettua un'acquisto viene iscritto alla lista "Clienti" presente su 4Marketing.
	 *
	 * @author Raffaele Capasso
	 * @version 1.0
	 * @copyright Dev And Trade
	 */
	public function subscribeCustomerAfterCheckout(Varien_Event_Observer $observer) {
		$mapFields = Mage::getStoreConfig ( 'fourdem/fourdem_mapping' );
		$listID = Mage::getStoreConfig ( 'fourdem/system_access/customer_list' );
		$customerQuote = $observer->getEvent ()->getQuote ()->getData ();
		$billingInformation = $observer->getEvent ()->getQuote ()->getBillingAddress ()->getData ();
		$customFields = array ();
		
		if ($customerQuote ['checkout_method'] === 'guest') {
			$email = $customerQuote ['customer_email'];
			$ipAddress = $_SERVER ['REMOTE_ADDR'];
			$isMessageSuppressed = true;
			$isCustomerRegistered = Mage::getModel ( 'customer/customer' )->setWebsiteId ( 1 )->loadByEmail ( $email )->getId ();
			
			if ($isCustomerRegistered) {
				foreach ( $mapFields as $label => $customFieldID ) {
					if (! empty ( $customFieldID )) {
						$billingValueField = $billingInformation [$label];
						
						// ************************** INIZIO MODIFICA CAMPI PERSONALIZZATI SELECT *************************************
						// Step 1 - Verifica se il campo è personalizzato e di tipo select
						$Custom_Var = Mage::getResourceModel ( 'customer/attribute_collection' )->addFieldToFilter ( 'is_user_defined', '1' )->addFieldToFilter ( 'attribute_code', $label )->addFieldToFilter ( 'frontend_input', 'select' )->getItems ();
						// Step 2 - Carica il valore testuale della selezione (il campo contiene l'ID della option selezionata dall'utente
						if (! empty ( $Custom_Var ) && isset ( $Custom_Var )) {
							$customerModel = Mage::getModel ( 'customer/customer' ); /* ->setStoreId ( $modelCustomer->getData ( 'store_id' ) ); */
							$attr = $customerModel->getResource ()->getAttribute ( $label );
							if ($attr->usesSource ()) {
								$currentStore = Mage::app ()->getStore ()->getCode ();
								Mage::app ()->getStore ()->setId ( 0 );
								$billingValueField = $attr->getSource ()->getOptionText ( $billingValueField );
								Mage::app ()->getStore ()->setId ( $currentStore );
							}
						}
						// ************************** FINE MODIFICA CAMPI PERSONALIZZATI SELECT ************************************
						// Website and WebStore names
						if ($label == "website_id") {
							$billingValueField = Mage::app ()->getWebsite ( $billingValueField )->getName ();
						}
						if ($label == "store_id") {
							$billingValueField = Mage::app ()->getStore ( $billingValueField )->getName ();
						}
						// ----- end ---
						
						$key = 'Fields' . $customFieldID;
						$customFields [$key] = '&Fields[CustomField' . $customFieldID . ']=' . $billingValueField;
					}
				}
				
				Mage::helper ( 'fourdem' )->updateSubscriber ( $email, $email, $listID, $customFields );
				return;
			} else {
				foreach ( $mapFields as $label => $customFieldID ) {
					if (! empty ( $customFieldID )) {
						$billingValueField = $billingInformation [$label];
						// ************************** INIZIO MODIFICA CAMPI PERSONALIZZATI SELECT *************************************
						// Step 1 - Verifica se il campo è personalizzato e di tipo select
						$Custom_Var = Mage::getResourceModel ( 'customer/attribute_collection' )->addFieldToFilter ( 'is_user_defined', '1' )->addFieldToFilter ( 'attribute_code', $label )->addFieldToFilter ( 'frontend_input', 'select' )->getItems ();
						// Step 2 - Carica il valore testuale della selezione (il campo contiene l'ID della option selezionata dall'utente
						if (! empty ( $Custom_Var ) && isset ( $Custom_Var )) {
							$customerModel = Mage::getModel ( 'customer/customer' ); /* ->setStoreId ( $modelCustomer->getData ( 'store_id' ) ); */
							$attr = $customerModel->getResource ()->getAttribute ( $label );
							if ($attr->usesSource ()) {
								$currentStore = Mage::app ()->getStore ()->getCode ();
								Mage::app ()->getStore ()->setId ( 0 );
								$billingValueField = $attr->getSource ()->getOptionText ( $billingValueField );
								Mage::app ()->getStore ()->setId ( $currentStore );
							}
						}
						// ************************** FINE MODIFICA CAMPI PERSONALIZZATI SELECT ************************************
						// Website and WebStore names
						if ($label == "website_id") {
							$billingValueField = Mage::app ()->getWebsite ( $billingValueField )->getName ();
						}
						if ($label == "store_id") {
							$billingValueField = Mage::app ()->getStore ( $billingValueField )->getName ();
						}
						// ----- end ---
						
						if (! empty ( $billingValueField )) {
							$key = 'CustomField' . $customFieldID;
							$customFields [$key] = '&CustomField' . $customFieldID . '=' . $billingValueField;
						}
					}
				}
				
				Mage::helper ( 'fourdem' )->newSubscriber ( $listID, $email, $ipAddress, $customFields, false, $isMessageSuppressed );
				return;
			}
		} else {
			$customerOrder = $observer->getEvent ()->getOrder ();
			$customer = $customerOrder->getCustomer ();
			// echo '<pre>'; print_r($customer->getData()); echo '</pre>'; die();
			$customerModel = Mage::getModel ( 'customer/customer' )->load ( $customer->getData ( 'entity_id' ) );
			$billingAddress = $customerOrder->getBillingAddress ();
			$email = $customerOrder->getCustomer ()->getEmail ();
			$isCustomerRegistered = Mage::getModel ( 'customer/customer' )->setWebsiteId ( 1 )->loadByEmail ( $email )->getId ();
			$ipAddress = $_SERVER ['REMOTE_ADDR'];
			
			if ($isCustomerRegistered) {
				foreach ( $mapFields as $label => $customFieldID ) {
					if (! empty ( $customFieldID ) && $label != 'billing_address') {
						$billingValueField = $billingAddress->getData ( $label );
						
						// ************************** INIZIO MODIFICA CAMPI PERSONALIZZATI SELECT *************************************
						// Step 1 - Verifica se il campo è personalizzato e di tipo select
						$Custom_Var = Mage::getResourceModel ( 'customer/attribute_collection' )->addFieldToFilter ( 'is_user_defined', '1' )->addFieldToFilter ( 'attribute_code', $label )->addFieldToFilter ( 'frontend_input', 'select' )->getItems ();
						// Step 2 - Carica il valore testuale della selezione (il campo contiene l'ID della option selezionata dall'utente
						if (! empty ( $Custom_Var ) && isset ( $Custom_Var )) {
							$customerModel = Mage::getModel ( 'customer/customer' ); /* ->setStoreId ( $modelCustomer->getData ( 'store_id' ) ); */
							$attr = $customerModel->getResource ()->getAttribute ( $label );
							if ($attr->usesSource ()) {
								$currentStore = Mage::app ()->getStore ()->getCode ();
								Mage::app ()->getStore ()->setId ( 0 );
								$billingValueField = $attr->getSource ()->getOptionText ( $billingValueField );
								Mage::app ()->getStore ()->setId ( $currentStore );
							}
						}
						// ************************** FINE MODIFICA CAMPI PERSONALIZZATI SELECT ************************************
						// Website and WebStore names
						if ($label == "website_id") {
							$billingValueField = Mage::app ()->getWebsite ( $billingValueField )->getName ();
						}
						if ($label == "store_id") {
							$billingValueField = Mage::app ()->getStore ( $billingValueField )->getName ();
						}
						// ----- end --
						$key = 'Fields' . $customFieldID;
						$customFields [$key] = '&Fields[CustomField' . $customFieldID . ']=' . $billingValueField;
					}
				}
				
				// echo '<pre>'; print_r($customFields); echo '</pre>'; die();
				Mage::helper ( 'fourdem' )->updateSubscriber ( $email, $email, $listID, $customFields, true );
				return;
			} else {
				foreach ( $mapFields as $label => $customFieldID ) {
					if (! empty ( $customFieldID )) {
						$billingValueField = $billingAddress->getData ( $label );
						
						// ************************** INIZIO MODIFICA CAMPI PERSONALIZZATI SELECT *************************************
						// Step 1 - Verifica se il campo è personalizzato e di tipo select
						$Custom_Var = Mage::getResourceModel ( 'customer/attribute_collection' )->addFieldToFilter ( 'is_user_defined', '1' )->addFieldToFilter ( 'attribute_code', $label )->addFieldToFilter ( 'frontend_input', 'select' )->getItems ();
						// Step 2 - Carica il valore testuale della selezione (il campo contiene l'ID della option selezionata dall'utente
						if (! empty ( $Custom_Var ) && isset ( $Custom_Var )) {
							$customerModel = Mage::getModel ( 'customer/customer' ); /* ->setStoreId ( $modelCustomer->getData ( 'store_id' ) ); */
							$attr = $customerModel->getResource ()->getAttribute ( $label );
							if ($attr->usesSource ()) {
								$currentStore = Mage::app ()->getStore ()->getCode ();
								Mage::app ()->getStore ()->setId ( 0 );
								$billingValueField = $attr->getSource ()->getOptionText ( $billingValueField );
								Mage::app ()->getStore ()->setId ( $currentStore );
							}
						}
						// ************************** FINE MODIFICA CAMPI PERSONALIZZATI SELECT ************************************
						// Website and WebStore names
						if ($label == "website_id") {
							$billingValueField = Mage::app ()->getWebsite ( $billingValueField )->getName ();
						}
						if ($label == "store_id") {
							$billingValueField = Mage::app ()->getStore ( $billingValueField )->getName ();
						}
						// ----- end --
						
						if (! empty ( $billingValueField )) {
							$key = 'CustomField' . $customFieldID;
							$customFields [$key] = '&CustomField' . $customFieldID . '=' . $billingValueField;
						} else {
							$key = 'CustomField' . $customFieldID;
							$customerValueField = $customerModel->getData ( $label );
							$customFields [$key] = '&CustomField' . $customFieldID . '=' . $customerValueField;
						}
					}
				}
				
				Mage::helper ( 'fourdem' )->newSubscriber ( $listID, $email, $ipAddress, $customFields );
				return;
			}
		}
	}
}