<?php

namespace Purolator\Shipping\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class MeasureUnitsWeight implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'lb',
                'label' => __('Lb'),
            ],
            [
                'value' => 'kg',
                'label' => __('Kg'),
            ],
        ];
    }
}
