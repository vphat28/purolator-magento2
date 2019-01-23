<?php

namespace Purolator\Shipping\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class BusinessRelationship implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'Related',
                'label' => __('Related'),
            ],
            [
                'value' => 'NotRelated',
                'label' => __('NotRelated'),
            ],
        ];
    }
}
