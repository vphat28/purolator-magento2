<?php

namespace Purolator\Shipping\Model\Adminhtml\Source;

class MarkupSpecific implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => 0, 'label' => 'No price adjustment'],
            ['value' => 1, 'label' => 'Apply adjustment for all services'],
            ['value' => 2, 'label' => 'Specify adjustment for each service'],
        ];

        return $options;
    }
}