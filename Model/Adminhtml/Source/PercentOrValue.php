<?php

namespace Purolator\Shipping\Model\Adminhtml\Source;

class PercentOrValue implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => 0, 'label' => 'Percentage'],
            ['value' => 1, 'label' => 'Value'],
            ['value' => 2, 'label' => 'Fixed Value'],
        ];

        return $options;
    }
}