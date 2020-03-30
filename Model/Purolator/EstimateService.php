<?php

namespace Purolator\Shipping\Model\Purolator;

use Purolator\Shipping\Helper\Data;
use Magento\Directory\Model\RegionFactory as RegionInterfaceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class EstimateService
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
        /** Purpose : Creates a SOAP Client in Non-WSDL mode with the appropriate authentication and
         *           header information
         **/

        try {
            if ($this->helper->isTestMode()) {
                $location = 'https://devwebservices.purolator.com/EWS/V2/Estimating/EstimatingService.asmx';
            } else {
                $location = 'https://webservices.purolator.com/EWS/V2/Estimating/EstimatingService.asmx';
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
                'Version' => '2.1',
                'Language' => 'en',
                'GroupID' => 'xxx',
                'RequestReference' => 'Get Rates',
                'UserToken' => $this->helper->getAPIActivationKey(),
            )
        );
        //Apply the SOAP Header to your client
        $client->__setSoapHeaders($headers);

        return $client;
    }

    /**
     * @param $senderPostCode
     * @param $recevierCity
     * @param $recevierProvince
     * @param $recevierCountry
     * @param $recevierPostCode
     * @param $rateRequest RateRequest
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRatesFromAddresses(
        $senderPostCode,
        $recevierCity,
        $recevierProvince,
        $recevierCountry,
        $recevierPostCode,
        $rateRequest
    )
    {
        $store = $this->storeManager->getStore($rateRequest->getStoreId());
        $request = new \stdClass();
        //Populate the Origin Information
        $request->Shipment = new \stdClass();
        $request->Shipment->SenderInformation = new \stdClass();
        $request->Shipment->SenderInformation->Address = new \stdClass();
        $request->Shipment->SenderInformation->Address->Name = $store->getName();
        $request->Shipment->SenderInformation->Address->City = $this->scopeConfig->getValue('shipping/origin/city', ScopeInterface::SCOPE_STORE, $store);

        $region = $this->regionInterfaceFactory->create();
        $this->regionResourceModel->load(
            $region,
            $this->scopeConfig->getValue('shipping/origin/region_id', ScopeInterface::SCOPE_STORE, $store),
            'region_id'
        );
        $request->Shipment->SenderInformation->Address->Province = $region->getCode();
        $request->Shipment->SenderInformation->Address->Country = $this->scopeConfig->getValue('shipping/origin/country_id', ScopeInterface::SCOPE_STORE, $store);
        $request->Shipment->SenderInformation->Address->PostalCode = $senderPostCode;
        $request->Shipment->ReceiverInformation = new \stdClass();
        $request->Shipment->ReceiverInformation->Address = new \stdClass();

        $request->Shipment->ReceiverInformation->Address->City = $recevierCity;
        $request->Shipment->ReceiverInformation->Address->Province = $recevierProvince;
        $request->Shipment->ReceiverInformation->Address->Country = $recevierCountry;
        $request->Shipment->ReceiverInformation->Address->PostalCode = $recevierPostCode;

        $request->Shipment->PaymentInformation = new \stdClass();
        $request->Shipment->PaymentInformation->PaymentType = 'Sender';
        $request->Shipment->PaymentInformation->BillingAccountNumber = $this->helper->getAPIAccountNumber();
        $request->Shipment->PaymentInformation->RegisteredAccountNumber = $this->helper->getRegisteredAccount();

        $request->Shipment->PackageInformation = new \stdClass();
        $request->Shipment->PackageInformation->TotalWeight = new \stdClass();

        $request->Shipment->PackageInformation->PiecesInformation = new \stdClass();
        $request->Shipment->PackageInformation->PiecesInformation->Piece = [];

        switch ($request->Shipment->ReceiverInformation->Address->Country) {
            case 'CA':
                $request->Shipment->PackageInformation->ServiceID = "PurolatorExpress";
                break;
            case 'US':
                $request->Shipment->PackageInformation->ServiceID = "PurolatorExpressU.S.";
                break;
            default:
                $request->Shipment->PackageInformation->ServiceID = "PurolatorExpressInternational";
        }

        $this->getBoxes($rateRequest, $request);

        //Execute the request and capture the response
         $response = $this->soapclient->GetFullEstimate($request);

         file_put_contents(BP . '/var/log/purolator.log', json_encode($response), FILE_APPEND);

        return $response;
    }

    /**
     * @param $rateRequest RateRequest
     * @param $request \stdClass
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getBoxes($rateRequest, $request)
    {
        $totalWeight = 0;
        $numberOfItems = 0;

        foreach ($rateRequest->getAllItems() as $item) {
            $numberOfItems++;
            /** @var \Magento\Quote\Model\Quote\Item $item */
            if(empty($item->getWeight())) {
                $totalWeight += $this->helper->getConfig('default_weight') * $item->getQty();
            } else {
                $totalWeight += $item->getWeight() * $item->getQty();
            }
        }


        if ($totalWeight < 1) {
            $totalWeight = 1;
        }
        $rateRequest->setPackageWeight($totalWeight);

        $packageWeight = $rateRequest->getPackageWeight();
        $defaultPackageWeight = $this->helper->getDefaultPackageWeight();
        $numberOfBoxes = ceil($packageWeight / $defaultPackageWeight);
        $weighUnit = $this->helper->getWeightMeasureUnit();
        $boxSize = $this->helper->getDefaultBoxSize($rateRequest->getStoreId());
        $boxSize = explode('*', $boxSize);
        $boxWidth = $boxSize[0];
        $boxHeight = $boxSize[1];
        $boxLength = $boxSize[2];
        $request->Shipment->PackageInformation->PiecesInformation = new \stdClass();

        if ($numberOfBoxes >= $numberOfItems) {
            $numberOfBoxes = $numberOfItems;
            $defaultPackageWeight = $packageWeight / $numberOfBoxes;
        }

        for ($i = 0; $i < $numberOfBoxes; $i++) {
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i] = new \stdClass();
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Weight = new \stdClass();
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Weight->Value = $defaultPackageWeight;
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Weight->WeightUnit = $weighUnit;
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Length = new \stdClass();
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Length->Value = $boxLength;
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Length->DimensionUnit = $this->helper->getConfig('measure_units_length');
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Width = new \stdClass();
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Width->Value = $boxWidth;
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Width->DimensionUnit = $this->helper->getConfig('measure_units_length');

            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Height = new \stdClass();
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Height->Value = $boxHeight;
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Height->DimensionUnit = $this->helper->getConfig('measure_units_length');
           /* $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Options = new \stdClass();
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Options->OptionIDValuePair[0]->ID = "SpecialHandling";
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Options->OptionIDValuePair[0]->Value = "true";
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Options->OptionIDValuePair[1]->ID = "SpecialHandlingType";
            $request->Shipment->PackageInformation->PiecesInformation->Piece[$i]->Options->OptionIDValuePair[1]->Value = "LargePackage";*/
        }

        $request->Shipment->PaymentInformation->PaymentType = "Sender";
        $request->Shipment->PaymentInformation->BillingAccountNumber = $this->helper->getAPIAccountNumber();
        $request->Shipment->PaymentInformation->RegisteredAccountNumber = $this->helper->getRegisteredAccount();

        $request->Shipment->PickupInformation = new \stdClass();
        $request->Shipment->PickupInformation->PickupType = "DropOff";
        $request->ShowAlternativeServicesIndicator = "true";

        $request->Shipment->PackageInformation->TotalWeight->Value = $packageWeight;
        $request->Shipment->PackageInformation->TotalWeight->WeightUnit = $weighUnit;
        $request->Shipment->PackageInformation->TotalPieces = $numberOfBoxes;
    }
}
