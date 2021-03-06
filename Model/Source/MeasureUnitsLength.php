<?php

namespace Purolator\Shipping\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class MeasureUnitsLength implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'in',
                'label' => __('Inch and Pound'),
            ],
            [
                'value' => 'cm',
                'label' => __('Centimeter and Kilogram'),
            ],
        ];
    }
}
