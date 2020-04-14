<?php

namespace Purolator\Shipping\Model\Purolator;

use Magento\Framework\DataObjectFactory;
use Psr\Log\LoggerInterface;
use Purolator\Shipping\Helper\Data;

class TrackingService
{
    /** @var \SoapClient */
    protected $soapclient = null;

    /** @var \Purolator\Shipping\Model\Logger */
    private $logger;

    /** @var Data */
    private $helper;

    /** @var DataObjectFactory */
    private $objectFactory;

    /**
     * TrackingService constructor.
     * @param \Purolator\Shipping\Model\Logger $logger
     * @param Data $helper
     * @param DataObjectFactory $objectFactory
     */
    public function __construct(
        \Purolator\Shipping\Model\Logger $logger,
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
        } catch (\Exception $e) {
            return null;
        }
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
            $this->logger->debug('Tracking info ' . json_encode($trackingProgress));
            if (isset($trackingProgress->TrackingInformationList->TrackingInformation->Scans->Scan)) {
                $trackingInfoList = $trackingProgress->TrackingInformationList->TrackingInformation->Scans->Scan;
            } else {
                $trackingInfoList = [];
            }

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
            file_put_contents(BP . '/var/log/purolator.log', PHP_EOL . __FILE__ . ' ' . __LINE__ . ' ' .$e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }
}
