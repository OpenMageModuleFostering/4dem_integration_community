<?php
class FourMarketing_FourDem_Model_Adminhtml_System_Source_Fields
{
    public function toOptionArray()
    {
        $customerListID   = Mage::getStoreConfig('fourdem/system_access/customer_list');
        $newsletterListID = Mage::getStoreConfig('fourdem/system_access/newsletter_list');

        if(!empty($customerListID))
        {
            $listID = $customerListID;
        }
        else
        {
            $listID = $newsletterListID;
        }

        $customFields = Mage::helper('fourdem')->getCustomFields($listID)->CustomFields;

        if(!empty($customFields))
        {
            $options[] = array(
                'value' => '',
                'label' => '-- Nessun Campo --',
            );

            foreach($customFields as $field)
            {
                $options[] = array(
                    'value' => $field->CustomFieldID,
                    'label' => $field->FieldName,
                );
            }
        }

        return $options;
    }
}