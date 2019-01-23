<?php
namespace Purolator\Shipping\Model\Carrier;

use Magento\Framework\Exception\LocalizedException;
use Purolator\Shipping\Helper\Data;
use Purolator\Shipping\Model\Carrier\Purolator\CostMarkupModifier;
use Purolator\Shipping\Model\Purolator\EstimateService;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Store\Model\ScopeInterface;
use Purolator\Shipping\Model\Purolator\TrackingService;

class Purolator extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'purolator';

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $rateMethodFactory;

    /** @var Request */
    protected $purolatorRequest;

    /** @var \Magento\Directory\Model\RegionFactory */
    protected $regionInterfaceFactory;

    /** @var \Magento\Directory\Model\ResourceModel\Region */
    protected $regionResourceModel;

    /** @var Data */
    public $helper;

    /** @var int */
    private $markupSpecificSetting = null;

    /** @var CostMarkupModifier */
    private $costMarkupModifier;

    /**
     * @var \Purolator\Shipping\Model\Purolator\ShipmentService
     */
    protected $purolatorShipmentService;

    /**
     * @var \Purolator\Shipping\Model\Purolator\LabelService
     */
    protected $purolatorLabelService;

    /** @var TrackingService */
    private $trackingService;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * Purolator constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param EstimateService $purolatorRequest
     * @param \Purolator\Shipping\Model\Purolator\LabelService $purolatorLabelService
     * @param \Purolator\Shipping\Model\Purolator\ShipmentService $purolatorShipmentService
     * @param \Magento\Directory\Model\RegionFactory $regionInterfaceFactory
     * @param \Magento\Directory\Model\ResourceModel\Region $regionResourceModel
     * @param Data $helper
     * @param \Magento\Framework\Registry $registry
     * @param CostMarkupModifier $costMarkupModifier
     * @param TrackingService $trackingService
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Purolator\Shipping\Model\Purolator\EstimateService $purolatorRequest,
        \Purolator\Shipping\Model\Purolator\LabelService $purolatorLabelService,
        \Purolator\Shipping\Model\Purolator\ShipmentService $purolatorShipmentService,
        \Magento\Directory\Model\RegionFactory $regionInterfaceFactory,
        \Magento\Directory\Model\ResourceModel\Region $regionResourceModel,
        Data $helper,
        \Magento\Framework\Registry $registry,
        CostMarkupModifier $costMarkupModifier,
        TrackingService $trackingService,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->purolatorRequest = $purolatorRequest;
        $this->purolatorShipmentService = $purolatorShipmentService;
        $this->purolatorLabelService = $purolatorLabelService;
        $this->regionInterfaceFactory = $regionInterfaceFactory;
        $this->regionResourceModel = $regionResourceModel;
        $this->helper = $helper;
        $this->registry = $registry;
        $this->trackingService = $trackingService;
        $this->costMarkupModifier = $costMarkupModifier;
    }

    public function getAllowedMethods()
    {
        return ['purolator' => __('Purolator')];
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * @param string $number
     * @return array
     */
    public function getTrackingInfo($number)
    {
        $response = $this->trackingService->getTrackingByNumber($number);
        var_dump($number);
        return $response;
    }

    /**
     * @param RateRequest $request
     * @return bool|\Magento\Framework\DataObject|\Magento\Shipping\Model\Rate\Result|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function collectRates(RateRequest $request)
    {
        $result = $this->rateResultFactory->create();

        $senderPostCode = $this->_scopeConfig->getValue('shipping/origin/postcode', ScopeInterface::SCOPE_STORE, $request->getStoreId());
        $recevierCity = $request->getDestCity();

        $regId = $request->getDestRegionId();
        $recevierProvince = $this->regionInterfaceFactory->create(['region_id' => $request->getRegionId()]);
        $this->regionResourceModel->load($recevierProvince, $regId, 'region_id');

        try {
            $requestResponse = $this->purolatorRequest->getRatesFromAddresses(
                $senderPostCode,
                $recevierCity,
                $recevierProvince->getData('code'),
                $request->getDestCountryId(),
                $request->getDestPostcode(),
                $request
            );
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        $allowedMethods = $this->helper->getAllowedMethods();

        if (!empty($requestResponse->ResponseInformation->Errors)) {
            if (!is_object($requestResponse->ShipmentEstimates)) {
                return $result;
            }
            if (is_array($requestResponse->ShipmentEstimates->ShipmentEstimate)) {
                foreach ($requestResponse->ShipmentEstimates->ShipmentEstimate as $rate) {
                    if (in_array($rate->ServiceID, $allowedMethods) || empty($allowedMethods)) {
                        $this->markupPrice($rate);
                        $result->append($this->createResultMethod((float)$rate->TotalPrice, $rate->ServiceID, $rate->ExpectedDeliveryDate, $rate->EstimatedTransitDays));
                    }
                }
            } else {
                $rate = $requestResponse->ShipmentEstimates->ShipmentEstimate;
                if (in_array($rate->ServiceID, $allowedMethods) || empty($allowedMethods)) {
                    $this->markupPrice($rate);
                    $result->append($this->createResultMethod((float)$rate->TotalPrice, $rate->ServiceID, $rate->ExpectedDeliveryDate, $rate->EstimatedTransitDays));
                }
            }
        }

        return $result;
    }

    /**
     * Shipping cost adjustments based on backend settings
     *
     * @param $rate
     * @return mixed
     */
    private function markupPrice($rate)
    {
        if ($this->markupSpecificSetting == null) {
            $this->markupSpecificSetting = $this->helper->getCostMarkupSpecific();
        }

        if ($this->markupSpecificSetting === 0) {
            return $rate;
        } else {
            return $this->costMarkupModifier->adjustShippingCost($rate);
        }
    }

    /**
     * @param $shippingPrice
     * @param $rateCode
     * @param $expectedDeliveryDate
     * @param $estimatedTransitDays
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createResultMethod($shippingPrice, $rateCode, $expectedDeliveryDate, $estimatedTransitDays)
    {
        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();
        $title = $rateCode;

        if (!empty($expectedDeliveryDate)) {
            $title = $expectedDeliveryDate . ' ' . $title;
        }

        if (!empty($estimatedTransitDays)) {
            if ((int)$estimatedTransitDays > 1) {
                $tmp =  __('days');
            } else {
                $tmp = __('day');
            }

            $title = $estimatedTransitDays . ' ' . $tmp . ' ' . $title;
        }

        $method->setCarrier($this->getCarrierCode());
        $method->setCarrierTitle($title);

        $method->setMethod($rateCode);
        $method->setMethodTitle($rateCode);

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);
        return $method;
    }

    /**
     * @return bool
     */
    public function isShippingLabelsAvailable()
    {
        return true;
    }

    public function requestToShipment($request)
    {
        if ($this->helper->getNoLabelCreationFlag()) {
            return $request;
        }

        if (!is_array($request->getPackages()) || !$request->getPackages()) {
            throw new LocalizedException(__('No packages for request'));
        }

        $data = [];
        foreach ($request->getPackages() as $packageId => $package) {
            $request->setPackageId($packageId);
            $request->setPackagingType($package['params']['container']);
            $request->setPackageWeight($package['params']['weight']);
            $request->setPackageParams(new \Magento\Framework\DataObject($package['params']));
            $request->setPackageItems($package['items']);
            $result = $this->_doShipmentRequest($request);

            $data[] = [
                'tracking_number' => $result->getTrackingNumber(),
                'label_content' => $result->getShippingLabelContent(),
            ];

            if (empty($request->getTrackingNumber())) {
                $request->setMasterTrackingId($result->getTrackingNumber());
            }
        }

        $response = new \Magento\Framework\DataObject(['info' => $data]);
        if (!empty($result) && $result->getErrors()) {
            $response->setErrors($result->getErrors());
        }

        return $response;
    }

    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        $answer = $this->purolatorShipmentService->createShipmentFromRequest($request);

        $response = $answer['response'];
        $purolatorRequest = $answer['purolator_request'];

        $dangerousGoods = (!empty($purolatorRequest->Shipment->PackageInformation->DangerousGoodsDeclarationDocumentIndicator)) ? 1 : 0;
        $this->registry->register('purolator_dangerous_goods', $dangerousGoods);

        $request->getOrderShipment()->setShipmentPin($response->ShipmentPIN->Value);
        $label = $this->purolatorLabelService->getShipmentDocument($response->ShipmentPIN->Value, $request->getOrderShipment());
        $result = new \Magento\Framework\DataObject();
        if (isset($response->Error)) {
            $result->setErrors((string)$response->Error->ErrorDescription);
        } else {
            $result->setShippingLabelContent($label);
            $result->setTrackingNumber((string)$response->ShipmentPIN->Value);
        }

        return $result;
    }


    public function getContainerTypes(\Magento\Framework\DataObject $params = null)
    {
        return [
            0 => "No",
            "AdditionalHandling" => "Additional Handling",
            "FlatPackage" => "Flat Package",
            "LargePackage" => "Large Package",
            "Oversized" => "Oversized",
            "ResidentialAreaHeavyweight" => "Residential Area Heavyweight"
        ];
    }
}
