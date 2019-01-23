<?php

namespace Purolator\Shipping\Model\Purolator;

use Purolator\Shipping\Helper\Data;
use Magento\Framework\Message\ManagerInterface;
use SoapClient;
use SoapHeader;

class ManifestService
{
    /** @var \SoapClient */
    private $soapclient = null;

    /** @var \SoapClient */
    private $soapclientManifest = null;

    /** @var Data */
    protected $helper;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * ManifestService constructor.
     * @param Data $helper
     * @param ManagerInterface $messageManager
     */
    public function __construct(Data $helper, ManagerInterface $messageManager)
    {
        $this->helper = $helper;
        $this->messageManager = $messageManager;
    }

    /**
     * Consolidate Request
     * @return \stdClass
     */
    private function _consolidationRequest()
    {
        return new \stdClass();
    }

    /**
     * Get manifest
     * @return boolean
     */
    public function getManifest()
    {
        $client = $this->createManifestSoapClient();

        try {
            $request = $this->_createManifestRequest();
            $response = $client->GetShipmentManifestDocument($request);
            $this->helper->checkResponse($response);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return false;
        }

        return $response;
    }

    /**
     * Create Manifest Request
     * @return \stdClass
     */
    private function _createManifestRequest()
    {
        $request = new \stdClass();
        $request->ShipmentManifestDocumentCriterium = new \stdClass();
        $request->ShipmentManifestDocumentCriterium->ShipmentManifestDocumentCriteria = new \stdClass();
        $request->ShipmentManifestDocumentCriterium->ShipmentManifestDocumentCriteria->ManifestDate = date("Y-m-d");

        return $request;
    }

    /**
     * @return bool
     */
    public function consolidate()
    {
        $client = $this->createShipmentSoapClient();

        try {
            $request = $this->_consolidationRequest();
            $response = $client->Consolidate($request);
            $this->helper->checkResponse($response);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return false;
        }

        return $response;
    }

    public function createShipmentSoapClient()
    {
        if ($this->soapclient) {
            return $this->soapclient;
        }

        /** Purpose : Creates a SOAP Client in Non-WSDL mode with the appropriate authentication and
         *           header information
         **/

        if ($this->helper->isTestMode()) {
            $location = 'https://devwebservices.purolator.com/EWS/V2/Shipping/ShippingService.asmx';
        } else {
            $location = 'https://webservices.purolator.com/EWS/V2/Shipping/ShippingService.asmx';
        }

        //Set the parameters for the Non-WSDL mode SOAP communication with your Development/Production credentials
        $client = new \SoapClient($location . "?wsdl",
            array(
                'trace' => true,
                'location' => $location,
                'uri' => "http://purolator.com/pws/datatypes/v2",
                'login' => $this->helper->getAccessKey(),
                'password' => $this->helper->getAPIKeyPassword(),
            )
        );
        //Define the SOAP Envelope Headers
        $headers[] = new \SoapHeader ('http://purolator.com/pws/datatypes/v2',
            'RequestContext',
            array(
                'Version' => '2.1',
                'Language' => 'en',
                'GroupID' => 'xxx',
                'RequestReference' => 'Consolidate',
                'UserToken' => $this->helper->getAccessKey(),
            )
        );
        //Apply the SOAP Header to your client
        $client->__setSoapHeaders($headers);
        $this->soapclient = $client;

        return $client;
    }

    public function createManifestSoapClient()
    {
        if ($this->soapclientManifest) {
            return $this->soapclientManifest;
        }

        if ($this->helper->isTestMode()) {
            $location = 'https://devwebservices.purolator.com/EWS/V1/ShippingDocuments/ShippingDocumentsService.asmx';
        } else {
            $location = 'https://webservices.purolator.com/EWS/V1/ShippingDocuments/ShippingDocumentsService.asmx';
        }

        //Set the parameters for the Non-WSDL mode SOAP communication with your Development/Production credentials
        $client = new SoapClient( $location . "?wsdl",
            array	(
                'trace'			=>	true,
                'location'	=>	$location,
                'uri'				=>	"http://purolator.com/pws/datatypes/v1",
                'login' => $this->helper->getAccessKey(),
                'password' => $this->helper->getAPIKeyPassword(),
            )
        );

        //Define the SOAP Envelope Headers
        $headers[] = new SoapHeader ( 'http://purolator.com/pws/datatypes/v1',
            'RequestContext',
            array (
                'Version'           =>  '1.3',
                'Language'          =>  'en',
                'GroupID'           =>  'xxx',
                'RequestReference'  =>  'Get Manifest',
                'UserToken' => $this->helper->getAPIActivationKey()
            )
        );
        //Apply the SOAP Header to your client
        $client->__setSoapHeaders($headers);
        $this->soapclientManifest = $client;

        return $client;
    }
}