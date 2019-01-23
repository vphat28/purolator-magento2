<?php
namespace Purolator\Shipping\Model\ResourceModel\PurolatorShipment;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Purolator\Shipping\Model\PurolatorShipment','Purolator\Shipping\Model\ResourceModel\PurolatorShipment');
    }
}
