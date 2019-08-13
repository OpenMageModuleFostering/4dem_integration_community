<?php
class FourMarketing_FourDem_Block_Adminhtml_FourDem_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setDefaultSort('fourdem_id');
        $this->setId('fourdemGrid');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    protected function _getCollectionClass()
    {
        return 'fourdem/fourdem';
    }

    protected function _prepareCollection()
    {
    	
        // Get and set our collection for the grid
        //$collection = Mage::getResourceModel($this->_getCollectionClass());
    	$collection = Mage::getModel('fourdem/fourdem')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('fourdem_id',
            array(
                'header'=> Mage::helper('fourdem')->__('ID 4MARKETING'),
                'align' =>'right',
                'width' => '50px',
                'index' => 'fourdem_id'
            )
        );

        $this->addColumn('firstname',
            array(
                'header'=> Mage::helper('fourdem')->__('Nome Cliente'),
                'index' => 'firstname'
            )
        );

        $this->addColumn('lastname',
            array(
                'header'=> Mage::helper('fourdem')->__('Cognome Cliente'),
                'index' => 'lastname'
            )
        );

        $this->addColumn('email_address',
            array(
                'header'=> Mage::helper('fourdem')->__('Email Cliente'),
                'index' => 'email_address'
            )
        );

        $this->addColumn('own_list',
            array(
                'header' => Mage::helper('fourdem')->__('Lista di Appartenenza'),
                'index'  => 'own_list'
            )
        );

        $this->addColumn('action',
            array(
                'header'    => Mage::helper('catalog')->__('Disiscrizione Newsletter'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('Disiscrivi Utente'),
                        'confirm' => $this->__('Sei sicuro di voler disiscrivere il cliente dalla lista?'),
                        'url'     => array(
                        'base'    => '*/*/delete'
                        ),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
        ));

        return parent::_prepareColumns();
    }
}