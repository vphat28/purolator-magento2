<?php

namespace Purolator\Shipping\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Purolator\Shipping\Model\Purolator\ServiceAvailabilityService;

class Options extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Purolator\Shipping\Model\Purolator\OptionsService
     */
    protected $optionsService;

    protected $html = '';

    /**
     * @var ServiceAvailabilityService
     */
    protected $availabilityService;

    /**
     * Options constructor.
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ServiceAvailabilityService $availabilityService
     * @param \Purolator\Shipping\Model\Purolator\OptionsService $optionsService
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        ServiceAvailabilityService $availabilityService,
        \Purolator\Shipping\Model\Purolator\OptionsService $optionsService,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->availabilityService = $availabilityService;
        $this->optionsService = $optionsService;
    }

    /**
     * @return string|null
     */
    public function getErrorValidationDescription(): ?string
    {
        $shipment = $this->registry->registry('current_shipment');

        /** @var Order $order */
        $order = $shippingMethod = $shipment->getOrder();

        if (in_array($order->getShippingAddress()->getCountryId(), ['CA', 'US'])) {
            $validationResult = $this->availabilityService->validateAddress(
                $order->getShippingAddress()->getCity(),
                $order->getShippingAddress()->getRegionCode(),
                $order->getShippingAddress()->getCountryId(),
                $order->getShippingAddress()->getPostcode()
            );

            if (isset($validationResult->ResponseInformation->Errors)
                && !empty((array) $validationResult->ResponseInformation->Errors)
            ) {
                return $validationResult->SuggestedAddresses->SuggestedAddress->ResponseInformation->Errors->Error->Description;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function checkIsPurolatorShipment()
    {
        $shipment = $this->registry->registry('current_shipment');

        if(empty($shipment)) {
            return false;
        }

        $shippingMethod = $shipment->getOrder()->getShippingMethod(true);
        if ($shippingMethod->getCarrierCode() != 'purolator') {
            return false;
        }

        return true;
    }

    public function getAvailableOptions()
    {
        $options = $this->optionsService->getOptions();
        if (empty($options->Services)) {
            return false;
        }

        $shipment = $this->registry->registry('current_shipment');
        $shippingMethod = $shipment->getOrder()->getShippingMethod(true);

        foreach ($options->Services->Service as $option) {
            if ($option->ID == $shippingMethod->getMethod()) {
                return $option->Options->Option;
            }
        }
    }

    public function renderChildOptions($childOptions)
    {
        foreach ($childOptions as $option){

            $this->html .= '<div class="admin__field" style="display: none;">';
            $this->html .= '<label class="admin__field-label" for="'.$option->ID .'">';
            $this->html .= '<span>'.$option->Description.'</span></label>';
            $this->html .= '<div class="admin__field-control">';

            if ($option->ValueType == 'Decimal') {
                $this->html .= '<input class="admin__control-text" type="text" name="shipment[purolator][' . $option->ID . ']" maxlength = "255" >';
            } else {
                if (!empty($option->PossibleValues) && !empty($option->PossibleValues->OptionValue)) {
                    $this->html .= '<select class="admin__control-select" name="shipment[purolator]['.$option->ID .']" id="'.$option->ID .'">';
                    $this->html .= '<option data-title="Select" value="0">Select</option>';
                    foreach ($option->PossibleValues as $values) {
                        if(!is_array($values)) {
                            $this->html .= '<option data-title="Yes" value="' . $values->Value . '">' . $values->Description . '</option>';
                            continue;
                        }

                        foreach ($values as $value) {
                            $this->html .= '<option data-title="Yes" value="' . $value->Value . '">' . $value->Description . '</option>';
                        }
                    }
                    $this->html .= '</select>';
                }
            }

            $this->html .= '</div></div>';

            if (!empty($option->ChildServiceOptions) && !empty($option->ChildServiceOptions->Option)) {
                $this->renderChildOptions($option->ChildServiceOptions);
            }
        }

        return $this->html;
    }

    public function resetHtml()
    {
        $this->html = '';
    }
}
