<?php

namespace Purolator\Shipping\Model\Purolator;

class OptionsService
{
    const SERVICE_AVAILABILITY_SERVICE = 'ServiceAvailabilityService';

    /**
     * @var \Purolator\Shipping\Helper\Data
     */
    protected $helper;

    protected $purolatorRequest;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var Request
     */
    protected $client;

    /**
     * @var \Magento\Shipping\Model\Shipping\LabelsFactory
     */
    protected $labelFactory;

    /**
     * OptionsService constructor.
     * @param \Purolator\Shipping\Helper\Data $helper
     * @param Request $client
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Shipping\Model\Shipping\LabelsFactory $labelFactory
     */
    public function __construct(
        \Purolator\Shipping\Helper\Data $helper,
        Request $client,
        \Magento\Framework\Registry $registry,
        \Magento\Shipping\Model\Shipping\LabelsFactory $labelFactory
    )
    {
        $this->helper = $helper;
        $this->registry = $registry;
        $this->client = $client;
        $this->labelFactory = $labelFactory;
    }

    public function getOptions($request = false)
    {
        $client = $this->client->createPWSSOAPClient(self::SERVICE_AVAILABILITY_SERVICE);

        //Define the request object
        $this->purolatorRequest = new \stdClass();
        $this->purolatorRequest->SenderAddress = new \stdClass();
        $this->purolatorRequest->ReceiverAddress = new \stdClass();
        //Populate the Payment Information
        $this->purolatorRequest->BillingAccountNumber = $this->helper->getAPIAccountNumber();

        if(!$request) {
            $shipment = $this->registry->registry('current_shipment');
            $this->helper->setNoLabelCreationFlag();
            $request = $this->labelFactory->create()->requestToShipment($shipment);
        }

        $this->purolatorRequest->SenderAddress->City = $this->helper->transliterateString($request->getShipperAddressCity());
        $this->purolatorRequest->SenderAddress->Province = $this->helper->transliterateString($request->getShipperAddressStateOrProvinceCode());
        $this->purolatorRequest->SenderAddress->Country = $this->helper->transliterateString($request->getShipperAddressCountryCode());
        $this->purolatorRequest->SenderAddress->PostalCode = $request->getShipperAddressPostalCode();


        $this->purolatorRequest->ReceiverAddress->City = $this->helper->transliterateString($request->getRecipientAddressCity());
        $this->purolatorRequest->ReceiverAddress->Province = $this->helper->transliterateString($request->getRecipientAddressStateOrProvinceCode());
        $this->purolatorRequest->ReceiverAddress->Country = $this->helper->transliterateString($request->getRecipientAddressCountryCode());
        $this->purolatorRequest->ReceiverAddress->PostalCode = str_replace(' ', '', $request->getRecipientAddressPostalCode());

        $response = $client->GetServicesOptions($this->purolatorRequest);
        $this->helper->checkResponse($response);

        return $response;
    }
}
