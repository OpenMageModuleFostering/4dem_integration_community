<?php
class FourMarketing_FourDem_Block_Adminhtml_Users_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setDefaultSort('id_fourdem');
        $this->setId('fourmarketing_fourdem_users_grid');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    protected function _getCollectionClass()
    {
        return 'fourmarketing_fourdem/users_collection';
    }

    protected function _prepareCollection()
    {
        // Get and set our collection for the grid
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id_fourdem',
            array(
                'header'=> $this->__('ID 4MARKETING'),
                'align' =>'right',
                'width' => '50px',
                'index' => 'id_fourdem'
            )
        );

        $this->addColumn('firstname',
            array(
                'header'=> $this->__('Nome Cliente'),
                'index' => 'firstname'
            )
        );

        $this->addColumn('lastname',
            array(
                'header'=> $this->__('Cognome Cliente'),
                'index' => 'lastname'
            )
        );

        $this->addColumn('email_address',
            array(
                'header'=> $this->__('Email Cliente'),
                'index' => 'email_address'
            )
        );

        $this->addColumn('own_list',
            array(
                'header' => $this->__('Lista di Appartenenza'),
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