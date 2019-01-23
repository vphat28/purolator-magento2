<?php

namespace Purolator\Shipping\Model\Purolator;

class VoidService
{
    const SHIPPING_DOCUMENT_REQUEST_TYPE = 'ShippingDocumentsService';

    /**
     * @var \Purolator\Shipping\Helper\Data
     */
    protected $helper;

    /**
     * @var Request
     */
    protected $purolatorRequest;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * LabelService constructor.
     * @param \Purolator\Shipping\Helper\Data $helper
     * @param Request $request
     */
    public function __construct(
        \Purolator\Shipping\Helper\Data $helper,
        Request $request,
        \Magento\Framework\Message\ManagerInterface $messageManager
    )
    {
        $this->helper = $helper;
        $this->purolatorRequest = $request;
        $this->messageManager = $messageManager;

    }

    /**
     * @param $pin
     * @return bool
     */
    public function voidShipment($pin)
    {
        $client = $this->purolatorRequest->createPWSSOAPClient(\Purolator\Shipping\Model\Purolator\ShipmentService::SHIPMENT_REQUEST_TYPE);
        try {
            $response = $client->VoidShipment($this->getVoidRequest($pin));
            $this->checkResponse($response);
        } catch (\Exception $e) {
            return false;
        }

        return $response;
    }

    /**
     * @param $pin
     * @return \stdClass
     */
    protected function getVoidRequest($pin)
    {
        $request = new \stdClass();
        $request->PIN = new \stdClass();
        $request->PIN->Value = $pin;

        return $request;
    }

    /**
     * @param $response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkResponse($response)
    {
        if (!empty($response->ResponseInformation->Errors->Error)) {

            foreach ($response->ResponseInformation->Errors as $error) {
                if (property_exists($error, "Description")) {
                    $this->messageManager->addErrorMessage($error->Description);
                }
            }

            throw new \Magento\Framework\Exception\LocalizedException(__('Can\'t void shipment'));
        }
    }
}
