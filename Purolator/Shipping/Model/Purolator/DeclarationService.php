<?php

namespace Purolator\Shipping\Model\Purolator;

class DeclarationService
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
     * LabelService constructor.
     * @param \Purolator\Shipping\Helper\Data $helper
     * @param Request $request
     */
    public function __construct(
        \Purolator\Shipping\Helper\Data $helper,
        Request $request
    )
    {
        $this->helper = $helper;
        $this->purolatorRequest = $request;

    }

    /**
     * @param $pin
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getShipmentDocument($pin)
    {
        $client = $this->purolatorRequest->createPWSSOAPClient(self::SHIPPING_DOCUMENT_REQUEST_TYPE);
        try {
            $response = $client->GetDocuments($this->getDocumentRequest($pin));
            $this->helper->checkResponse($response);
        } catch (\Exception $e) {
            return false;
        }

        return $this->getContent($response);
    }

    /**
     * @param $pin
     * @param $shipment
     * @param string $type
     * @return \stdClass
     */
    protected function getDocumentRequest($pin)
    {
        $request = new \stdClass();
        $request->DocumentCriterium = new \stdClass();
        $request->DocumentCriterium->DocumentCriteria = new \stdClass();
        $request->DocumentCriterium->DocumentCriteria->PIN = new \stdClass();
        $request->DocumentCriterium->DocumentCriteria->DocumentTypes = new \stdClass();

        $request->DocumentCriterium->DocumentCriteria->PIN->Value = $pin;
        $request->DocumentCriterium->DocumentCriteria->DocumentTypes->DocumentType = "DangerousGoodsDeclaration";
        $request->OutputType = "PDF";
        $request->Synchronous = false;
        $request->SynchronousSpecified = true;

        return $request;
    }

    /**
     * @param $response
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getContent($response)
    {
        if (empty($response->Documents->Document->DocumentDetails->DocumentDetail->URL)) {
            throw new \Magento\Framework\Exception\LocalizedException(__("File content empty ! , Please try again"));
        }

        $content = file_get_contents($response->Documents->Document->DocumentDetails->DocumentDetail->URL);
        if (empty($content)) {
            throw new \Magento\Framework\Exception\LocalizedException(__("File content empty ! , Please try again"));
        }

        return $content;
    }
}
