<?php

namespace Purolator\Shipping\Model\ResourceModel\Order;

class Shipment extends \Magento\Sales\Model\ResourceModel\Order\Shipment
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_shipment', 'id');
    }
}
