<?php

namespace Purolator\Shipping\Model\Purolator;

use Purolator\Shipping\Helper\Data;

class Request
{
    /** @var \SoapClient */
    protected $soapclient = null;

    /** @var Data */
    protected $helper;

    /**
     * Request constructor.
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function createPWSSOAPClient($type = false)
    {
        switch ($type) {

            case \Purolator\Shipping\Model\Purolator\ShipmentService::SHIPMENT_REQUEST_TYPE: {

                $client = new \SoapClient($this->getLocation($type) . "?wsdl",
                    array(
                        'trace' => true,
                        'location' => $this->getLocation($type),
                        'uri' => "http://purolator.com/pws/datatypes/v2",
                        'login' => $this->helper->getAccessKey(),
                        'password' => $this->helper->getAPIKeyPassword(),
                    )
                );

                $headers[] = new \SoapHeader ('http://purolator.com/pws/datatypes/v2',
                    'RequestContext',
                    array(
                        'Version' => '2.0',
                        'Language' => 'en',
                        'GroupID' => 'xxx',
                        'RequestReference' => 'Shipping Example',
                        'UserToken' => $this->helper->getAPIActivationKey(),
                    )
                );
            }

                break;

            case \Purolator\Shipping\Model\Purolator\LabelService::SHIPPING_DOCUMENT_REQUEST_TYPE: {

                $client = new \SoapClient($this->getLocation($type) . "?wsdl",
                    array(
                        'trace' => true,
                        'location' => $this->getLocation($type),
                        'uri' => "http://purolator.com/pws/datatypes/v1",
                        'login' => $this->helper->getAccessKey(),
                        'password' => $this->helper->getAPIKeyPassword(),
                    )
                );

                $headers[] = new \SoapHeader ('http://purolator.com/pws/datatypes/v1',
                    'RequestContext',
                    array(
                        'Version' => $this->getVersion($type),
                        'Language' => ($this->helper->getDefaultCountry() == "FR") ? 'fr' : 'en',
                        'GroupID' => 'xxx',
                        'RequestReference' => $type . ' Request',
                        'UserToken' => $this->helper->getAPIActivationKey(),
                    )
                );
            }

                break;

            case \Purolator\Shipping\Model\Purolator\ShipmentService::SERVICE_AVAILABILITY_SERVICE: {

                $client = new \SoapClient($this->getLocation($type) . "?wsdl",
                    array(
                        'trace' => true,
                        'location' => $this->getLocation($type),
                        'uri' => "http://purolator.com/pws/datatypes/v2",
                        'login' => $this->helper->getAccessKey(),
                        'password' => $this->helper->getAPIKeyPassword(),
                    )
                );

                $headers[] = new \SoapHeader ('http://purolator.com/pws/datatypes/v2',
                    'RequestContext',
                    array(
                        'Version' => $this->getVersion($type),
                        'Language' => ($this->helper->getDefaultCountry() == "FR") ? 'fr' : 'en',
                        'GroupID' => 'xxx',
                        'RequestReference' => 'Rating Example',
                        'UserToken' => $this->helper->getAPIActivationKey(),
                    )
                );
            }

                break;

            default: {

                $client = new \SoapClient($this->getLocation($type) . "?wsdl",
                    array(
                        'trace' => true,
                        'location' => $this->getLocation($type),
                        'uri' => "http://purolator.com/pws/datatypes/v2",
                        'login' => $this->helper->getAccessKey(),
                        'password' => $this->helper->getAPIKeyPassword(),
                    )
                );

                $headers[] = new \SoapHeader ('http://purolator.com/pws/datatypes/v2',
                    'RequestContext',
                    array(
                        'Version' => '2.1',
                        'Language' => 'en',
                        'GroupID' => 'xxx',
                        'RequestReference' => 'Get Rates',
                        'UserToken' => $this->helper->getAPIActivationKey(),
                    )
                );
            }

                break;
        }

        //Apply the SOAP Header to your client
        $client->__setSoapHeaders($headers);

        return $client;
    }

    protected function getLocation($type)
    {
        switch ($type) {
            case \Purolator\Shipping\Model\Purolator\ShipmentService::SHIPMENT_REQUEST_TYPE: {
                $location = ($this->helper->isTestMode()) ?
                    'https://devwebservices.purolator.com/EWS/V2/Shipping/ShippingService.asmx' :
                    'https://webservices.purolator.com/EWS/V2/Shipping/ShippingService.asmx';
            }

                break;

            case \Purolator\Shipping\Model\Purolator\ShipmentService::SERVICE_AVAILABILITY_SERVICE: {
                $location = ($this->helper->isTestMode()) ?
                    'https://devwebservices.purolator.com/EWS/V2/ServiceAvailability/ServiceAvailabilityService.asmx' :
                    'https://webservices.purolator.com/EWS/V2/ServiceAvailability/ServiceAvailabilityService.asmx';
            }

                break;

            case \Purolator\Shipping\Model\Purolator\LabelService::SHIPPING_DOCUMENT_REQUEST_TYPE: {
                $location = ($this->helper->isTestMode()) ?
                    'https://devwebservices.purolator.com/PWS/V1/ShippingDocuments/ShippingDocumentsService.asmx' :
                    'https://webservices.purolator.com/PWS/V1/ShippingDocuments/ShippingDocumentsService.asmx';
            }

                break;

            default: {
                $location = ($this->helper->isTestMode()) ?
                    'https://devwebservices.purolator.com/EWS/V2/Estimating/EstimatingService.asmx' :
                    'https://webservices.purolator.com/EWS/V2/Estimating/EstimatingService.asmx';
            }

                break;
        }

        return $location;
    }

    /**
     * @param $senderPostCode
     * @param $recevierCity
     * @param $recevierProvince
     * @param $recevierCountry
     * @param $recevierPostCode
     * @param $weight
     * @return mixed
     */
    public function getRatesFromAddresses(
        $senderPostCode,
        $recevierCity,
        $recevierProvince,
        $recevierCountry,
        $recevierPostCode,
        $weight
    )
    {
        $request = new \stdClass();
        //Populate the Billing Account Number
        $request->BillingAccountNumber = $this->helper->getAPIAccountNumber();
        //Populate the Origin Information
        $request->SenderPostalCode = $senderPostCode;
        //Populate the Desination Information
        $request->ReceiverAddress = new \stdClass();
        $request->ReceiverAddress->City = $recevierCity;

        if (!empty($recevierProvince)) {
            $request->ReceiverAddress->Province = $recevierProvince;
        } else {
            $request->ReceiverAddress->Province = '';
        }

        $request->ReceiverAddress->PostalCode = $recevierPostCode;
        $request->ReceiverAddress->Country = $recevierCountry;
        //Populate the Package Information
        $request->PackageType = "CustomerPackaging";
        //Populate the Shipment Weight
        $request->TotalWeight = new \stdClass();

        $weightUnit = $this->helper->getWeightMeasureUnit();

        $request->TotalWeight->Value = $weight;
        $request->TotalWeight->WeightUnit = $weightUnit;
        //Execute the request and capture the response
        $client = $this->createPWSSOAPClient();
        $response = $client->GetQuickEstimate($request);

        return $response;
    }

    /**
     * Version ID
     *
     * @param type $type API type
     *
     * @return string
     */
    protected function getVersion($type)
    {
        if ($type == 'ShippingDocumentsService') {
            return '1.3';
        }

        if ($type == 'ShippingService') {
            return '2.0';
        }

        if ($type == 'EstimatingService') {
            return '2.0';
        }

        if ($type == 'ServiceAvailabilityService') {
            return '2.0';
        }
    }
}
