<?php

namespace Purolator\Shipping\Model\Purolator;

class LabelService
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
     * @param $shipment
     * @param null $param
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getShipmentDocument($pin, $shipment, $param = null)
    {
        $client = $this->purolatorRequest->createPWSSOAPClient(self::SHIPPING_DOCUMENT_REQUEST_TYPE);
        try {
            if (!empty($param)) {
                $response = $client->GetDocuments($this->getDocumentRequest($pin, $shipment, $param));
            } else {
                $response = $client->GetDocuments($this->getDocumentRequest($pin, $shipment));
            }
            $this->checkResponse($response);
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
    protected function getDocumentRequest($pin, $shipment, $type = "label")
    {
        $origin = $this->helper->getShippingOriginData();

        $request = new \stdClass();
        $request->DocumentCriterium = new \stdClass();
        $request->DocumentCriterium->DocumentCriteria = new \stdClass();
        $request->DocumentCriterium->DocumentCriteria->PIN = new \stdClass();
        $request->DocumentCriterium->DocumentCriteria->DocumentTypes = new \stdClass();

        $request->DocumentCriterium->DocumentCriteria->PIN->Value = $pin;
        $request->OutputType = "PDF";
        $request->Synchronous = false;
        $request->SynchronousSpecified = true;

        $therm = "";
        if ($this->helper->getConfig("printertype")) {
            $therm = "Thermal";
        }

        if ($origin["country_id"] == $shipment->getShippingAddress()->getCountryId()) {
            $request->DocumentCriterium->DocumentCriteria->DocumentTypes->DocumentType = "DomesticBillOfLading" . $therm;
            return $request;
        }

        $request->DocumentCriterium->DocumentCriteria->DocumentTypes->DocumentType = "InternationalBillOfLading" . $therm;

        if ($type == "customs" && $this->helper->getConfig('customerinvoice')) {
            $request->DocumentCriterium->DocumentCriteria->DocumentTypes->DocumentType = "CustomsInvoice" . $therm;
        }
        if ($type == "nafta" && $this->helper->getConfig('nafta')) {
            $request->DocumentCriterium->DocumentCriteria->DocumentTypes->DocumentType = "NAFTA";
        }
        if ($type == "fcc" && $this->helper->getConfig('fcc')) {
            $request->DocumentCriterium->DocumentCriteria->DocumentTypes->DocumentType = "FCC740";
        }


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

    /**
     * @param $response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkResponse($response)
    {
        if (!empty($response->ResponseInformation->Errors->Error)) {

            $message = "";
            foreach ($response->ResponseInformation->Errors as $error) {
                if (property_exists($error, "Description")) {
                    $message .= $error->Description . "<br/>";
                }
            }

            $this->messageManager->addErrorMessage($message);
            throw new \Magento\Framework\Exception\LocalizedException(__($message));
        }
    }

    /**
     * @param $shipment
     * @param $pdf
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function customsDocument($shipment, $pdf)
    {
        $origin = $this->helper->getShippingOriginData();
        if (strtolower($origin["country_id"]) != strtolower($shipment->getShippingAddress()->getCountryId())) {

            if ($this->helper->getConfig('customerinvoice')) {

                $response = $this->purolatorShipmentService->getShipmentDocument($shipment, "customs");
                $pdfString = $this->getContent($response);
                $pdf = $this->addPage($pdf, $pdfString);
            }

            if (strtolower($shipment->getShippingAddress()->getCountryId()) == "us") {

                if ($this->helper->getConfig('nafta')) {

                    $response = $this->purolatorShipmentService->getShipmentDocument($shipment, "nafta");
                    $pdfString = $this->getContent($response);
                    $pdf = $this->addPage($pdf, $pdfString);
                }

                if ($this->helper->getConfig('fcc')) {

                    $response = $this->purolatorShipmentService->getShipmentDocument($shipment, "fcc");
                    $pdfString = $this->getContent($response);
                    $pdf = $this->addPage($pdf, $pdfString);
                }
            }
        }

        return $pdf;
    }
}
