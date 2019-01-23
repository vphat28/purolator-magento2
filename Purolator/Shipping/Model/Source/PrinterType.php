<?php

namespace Purolator\Shipping\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class PrinterType implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'Thermal',
                'label' => __('Thermal'),
            ],
            [
                'value' => 'Regular',
                'label' => __('Regular'),
            ]
        ];
    }
}
