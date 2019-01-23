<?php

namespace Purolator\Shipping\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class BillDutiesToParty implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'Sender',
                'label' => __('Sender'),
            ],
            [
                'value' => 'Receiver',
                'label' => __('Receiver'),
            ],
            [
                'value' => 'Buyer',
                'label' => __('Buyer'),
            ],
        ];
    }
}
