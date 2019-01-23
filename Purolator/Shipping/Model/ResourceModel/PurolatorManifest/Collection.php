<?php

namespace Purolator\Shipping\Model\ResourceModel\PurolatorManifest;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Purolator\Shipping\Model\PurolatorManifest','Purolator\Shipping\Model\ResourceModel\PurolatorManifest');
    }
}
