<?php

namespace Purolator\Shipping\Model\Purolator;

use Magento\Framework\DataObjectFactory;
use Psr\Log\LoggerInterface;
use Purolator\Shipping\Helper\Data;

class TrackingService
{
    /** @var \SoapClient */
    protected $soapclient = null;

    /** @var LoggerInterface */
    private $logger;

    /** @var Data */
    private $helper;

    /** @var DataObjectFactory */
    private $objectFactory;

    /**
     * TrackingService constructor.
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param DataObjectFactory $objectFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Data $helper,
        DataObjectFactory $objectFactory
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->soapclient = $this->createPWSSOAPClient();
        $this->objectFactory = $objectFactory;
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
                $location = 'https://devwebservices.purolator.com/PWS/V1/Tracking/TrackingService.asmx';
            } else {
                $location = 'https://webservices.purolator.com/PWS/V1/Tracking/TrackingService.asmx';
            }
        } catch (\Exception $e) {
            return null;
        }

        //Set the parameters for the Non-WSDL mode SOAP communication with your Development/Production credentials
        $client = new \SoapClient($location . "?wsdl",
            array(
                'trace' => true,
                'location' => $location,
                'uri' => "http://purolator.com/pws/datatypes/v1",
                'login' => $this->helper->getAccessKey(),
                'password' => $this->helper->getAPIKeyPassword(),
            )
        );
        //Define the SOAP Envelope Headers
        $headers[] = new \SoapHeader ('http://purolator.com/pws/datatypes/v1',
            'RequestContext',
            array(
                'Version' => '1.2',
                'Language' => 'en',
                'GroupID' => 'xxx',
                'RequestReference' => 'Get Tracking',
                'UserToken' => $this->helper->getAPIActivationKey(),
            )
        );
        //Apply the SOAP Header to your client
        $client->__setSoapHeaders($headers);

        return $client;
    }

    /**
     * @param string $number
     * @return mixed
     */
    public function getTrackingByNumber($number)
    {
        try {
            $request = new \stdClass();
            $request->PINs = new \stdClass();
            $request->PINs->PIN = new \stdClass();
            $request->PINs->PIN->Value = $number;

            $trackingProgress = $this->soapclient->TrackPackagesByPin($request);
            $trackingInfoList = $trackingProgress->TrackingInformationList->TrackingInformation->Scans->Scan;
            $trackingDetails = [];

            foreach ($trackingInfoList as $trackInfo) {
                $trackingDetails[] = [
                    'deliverydate' => $trackInfo->ScanDate,
                    'deliverytime' => $trackInfo->ScanTime,
                    'deliverylocation' => $trackInfo->Depot->Name,
                    'activity' => $trackInfo->Description,
                ];
            }

            $dataObject = $this->objectFactory->create();
            $dataObject->setData('tracking', $number);
            $dataObject->setData('progressdetail', $trackingDetails);

            return $dataObject;
        } catch (\Exception $e) {
            $this->logger->error(__FILE__ . ' ' . __LINE__ . ' ' .$e->getMessage());
        }
    }
}