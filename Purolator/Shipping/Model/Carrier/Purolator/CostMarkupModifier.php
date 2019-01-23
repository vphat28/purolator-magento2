<?php

namespace Purolator\Shipping\Model\Carrier\Purolator;

use Purolator\Shipping\Helper\Data;
use Purolator\Shipping\Model\Adminhtml\Source\ShippingServices;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CostMarkupModifier
{
    /** @var Data */
    private $data;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var array */
    private $settings = [];

    /** @var ShippingServices */
    private $shippingServices;

    /**
     * CostMarkupModifier constructor.
     * @param Data $data
     * @param ScopeConfigInterface $scopeConfig
     * @param ShippingServices $shippingServices
     */
    public function __construct(
        Data $data,
        ScopeConfigInterface $scopeConfig,
        ShippingServices $shippingServices
    ) {
        $this->data = $data;
        $this->scopeConfig = $scopeConfig;
        $this->shippingServices = $shippingServices;
    }

    private function getConfig($configPath)
    {
        if (!isset($this->settings[$configPath])) {
            $this->settings[$configPath] = $this->scopeConfig->getValue($configPath);
        }

        return $this->settings[$configPath];
    }

    /**
     * @param $rate \stdClass
     */
    public function adjustShippingCost($rate)
    {
        if ($this->data->getCostMarkupSpecific() === 1) {
            // Apply same rule for all services
            if ($this->getConfig('carriers/purolator/markupall_type') == 2) {
                $rate->TotalPrice = $this->getConfig('carriers/purolator/markupall_fixed');
            }

            if ($this->getConfig('carriers/purolator/markupall_type') == 1) {
                $rate->TotalPrice = $rate->TotalPrice + (float)$this->getConfig('carriers/purolator/markupall_amount');
            }

            if ($this->getConfig('carriers/purolator/markupall_type') == 0) {
                $rate->TotalPrice = ((float)$rate->TotalPrice / 100) * (100 + (int)$this->getConfig('carriers/purolator/markupall_amount'));
            }
        }

        if ($this->data->getCostMarkupSpecific() === 2) {
            // Appling specified rule for each services
            $groupName = $this->shippingServices->getGroupFromService($rate->ServiceID);

            if ($this->getConfig('carriers/purolator/' . 'markup_' . $groupName . '_type') == 2) {
                $rate->TotalPrice = (float)$this->getConfig('carriers/purolator/markup_' . $groupName . '_fixed');
            }

            if ($this->getConfig('carriers/purolator/' . 'markup_' . $groupName . '_type') == 1) {
                $rate->TotalPrice = $rate->TotalPrice + (float)$this->getConfig('carriers/purolator/markup_' . $groupName . '_amount');
            }

            if ($this->getConfig('carriers/purolator/' . 'markup_' . $groupName . '_type') == 0) {
                $rate->TotalPrice = ((float)$rate->TotalPrice / 100) * (100 + (int)$this->getConfig('carriers/purolator/markup_' . $groupName . '_amount'));
            }
        }
    }
}