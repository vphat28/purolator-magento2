<?php

namespace Purolator\Shipping\Helper;

use Magento\Sales\Model\Order;

class Address
{
    /**
     * @param Order $order
     * @return null|array
     */
    public function getModifiedOriginAddress($order)
    {
        $payment = $order->getPayment();

        if (empty($payment->getAdditionalInformation('purolator_ship_from_country_id'))) {
            return null;
        }

        return [
            'country_id' => $payment->getAdditionalInformation('purolator_ship_from_country_id'),
            'region' => $payment->getAdditionalInformation('purolator_ship_from_region'),
            'region_id' => $payment->getAdditionalInformation('purolator_ship_from_region_id'),
            'postcode' => $payment->getAdditionalInformation('purolator_ship_from_postcode'),
            'city' => $payment->getAdditionalInformation('purolator_ship_from_city'),
            'streetLine1' => $payment->getAdditionalInformation('purolator_ship_from_streetLine1'),
        ];
    }
}