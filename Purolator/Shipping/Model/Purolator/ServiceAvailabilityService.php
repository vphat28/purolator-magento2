<?php

namespace Purolator\Shipping\Model\Purolator;

use Purolator\Shipping\Helper\Data;
use Magento\Directory\Model\RegionFactory as RegionInterfaceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class ServiceAvailabilityService
{
    /** @var \SoapClient */
    protected $soapclient = null;

    /** @var Data */
    protected $helper;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var RegionInterfaceFactory */
    private $regionInterfaceFactory;

    /** @var \Magento\Directory\Model\ResourceModel\Region */
    private $regionResourceModel;

    /**
     * EstimateService constructor.
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param RegionInterfaceFactory $regionInterfaceFactory
     * @param \Magento\Directory\Model\ResourceModel\Region $regionResourceModel
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        RegionInterfaceFactory $regionInterfaceFactory,
        \Magento\Directory\Model\ResourceModel\Region $regionResourceModel
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->soapclient = $this->createPWSSOAPClient();
        $this->regionInterfaceFactory = $regionInterfaceFactory;
        $this->regionResourceModel = $regionResourceModel;
    }

    /**
     * @return null|\SoapClient
     */
    private function createPWSSOAPClient()
    {
        try {
            if ($this->helper->isTestMode()) {
                $location = 'https://devwebservices.purolator.com/EWS/V2/ServiceAvailability/ServiceAvailabilityService.asmx';
            } else {
                $location = 'https://webservices.purolator.com/EWS/V2/ServiceAvailability/ServiceAvailabilityService.asmx';
            }
        } catch (\Exception $e) {
            return null;
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
                'Version' => '2.0',
                'Language' => 'en',
                'GroupID' => 'xxx',
                'RequestReference' => 'Validation',
                'UserToken' => $this->helper->getAPIActivationKey(),
            )
        );
        //Apply the SOAP Header to your client
        $client->__setSoapHeaders($headers);

        return $client;
    }

    /**
     * @param $city
     * @param $provice
     * @param $country
     * @param $postCode
     * @return mixed
     */
    public function validateAddress($city, $provice, $country, $postCode) {
        $client = $this->soapclient;

        $request = new \stdClass();
        $request->Addresses = new \stdClass();
        $request->Addresses->ShortAddress = new \stdClass();
        $request->Addresses->ShortAddress->City = $city;
        $request->Addresses->ShortAddress->Province = $provice;
        $request->Addresses->ShortAddress->Country = $country;
        $request->Addresses->ShortAddress->PostalCode = $postCode;

        return $client->ValidateCityPostalCodeZip($request);
    }
}