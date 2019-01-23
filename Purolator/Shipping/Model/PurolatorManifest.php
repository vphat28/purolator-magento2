<?php

namespace Purolator\Shipping\Model;

class PurolatorManifest extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Purolator\Shipping\Model\ResourceModel\PurolatorManifest');
    }
}
