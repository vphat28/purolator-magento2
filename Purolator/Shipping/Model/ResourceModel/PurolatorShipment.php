<?php
namespace Purolator\Shipping\Model\ResourceModel;
class PurolatorShipment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('ch_purolator_shipment','id');
    }
}
