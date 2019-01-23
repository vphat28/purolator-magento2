<?php

namespace Purolator\Shipping\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class ImportExportType implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'Permanent',
                'label' => __('Permanent'),
            ],
            [
                'value' => 'Temporary',
                'label' => __('Temporary'),
            ],
            [
                'value' => 'Repair',
                'label' => __('Repair'),
            ],
            [
                'value' => 'Return',
                'label' => __('Return'),
            ],
        ];
    }
}
