<?php

namespace Purolator\Shipping\Model;

class PurolatorShipment extends \Magento\Framework\Model\AbstractModel implements \Purolator\Shipping\Api\Data\PurolatorShipmentInterface, \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'Purolator_Shipping_purolatorshipment';

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Purolator\Shipping\Model\ResourceModel\PurolatorShipment');
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
