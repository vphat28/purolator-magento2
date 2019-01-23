<?php

namespace Purolator\Shipping\Observer;

use Magento\Framework\Event\ObserverInterface;

class CreateShipment implements ObserverInterface
{
    /**
     * @var \Purolator\Shipping\Model\Purolator\ShipmentService
     */
    protected $purolatorShipmentService;

    /**
     * @var \Magento\Shipping\Model\Shipping\LabelsFactory
     */
    protected $labelFactory;

    /**
     * @var \Purolator\Shipping\Model\PurolatorShipmentFactory
     */
    protected $purolatorShipmentFactory;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $trackFactory;

    /**
     * @var \Purolator\Shipping\Model\PurolatorShipmentRepository
     */
    protected $purolatorShipmentRepository;

    /**
     * @var \Purolator\Shipping\Helper\Data
     */
    protected $helper;

    /**
     * CreateShipment constructor.
     * @param \Purolator\Shipping\Model\Purolator\ShipmentService $purolatorShipmentService
     * @param \Magento\Shipping\Model\Shipping\LabelsFactory $labelFactory
     * @param \Purolator\Shipping\Model\PurolatorShipmentFactory $purolatorShipmentFactory
     * @param \Purolator\Shipping\Model\PurolatorShipmentRepository $purolatorShipmentRepository
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
     * @param \Purolator\Shipping\Helper\Data $helper
     */
    public function __construct(
        \Purolator\Shipping\Model\Purolator\ShipmentService $purolatorShipmentService,
        \Magento\Shipping\Model\Shipping\LabelsFactory $labelFactory,
        \Purolator\Shipping\Model\PurolatorShipmentFactory $purolatorShipmentFactory,
        \Purolator\Shipping\Model\PurolatorShipmentRepository $purolatorShipmentRepository,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Purolator\Shipping\Helper\Data $helper
    )
    {
        $this->purolatorShipmentService = $purolatorShipmentService;
        $this->labelFactory = $labelFactory;
        $this->purolatorShipmentFactory = $purolatorShipmentFactory;
        $this->trackFactory = $trackFactory;
        $this->purolatorShipmentRepository = $purolatorShipmentRepository;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();

        $shippingMethod = $shipment->getOrder()->getShippingMethod(true);
        if ($shippingMethod->getCarrierCode() != 'purolator') {
            return $this;
        }

        if (!empty($shipment->getAllTracks())) {
            return $this;
        }

        $this->helper->setNoLabelCreationFlag();
        $request = $this->labelFactory->create()->requestToShipment($shipment);
        $answer = $this->purolatorShipmentService->createShipmentFromRequest($request);

        $response = $answer['response'];
        $purolatorRequest = $answer['purolator_request'];

        $piecePIN = [];
        if (!empty($response->PiecePINs)) {
            foreach ($response->PiecePINs as $pin) {
                if (!empty($pin->Value)) {
                    $piecePIN[] = $pin->Value;
                }
            }
        }

        try {

            $dangerousGoods = (!empty($purolatorRequest->Shipment->PackageInformation->DangerousGoodsDeclarationDocumentIndicator)) ? 1 : 0;
            $trackModel = $this->trackFactory->create()
                ->setNumber(
                    $response->ShipmentPIN->Value
                )->setCarrierCode(
                    $shippingMethod->getCarrierCode()
                )->setTitle(
                    $shippingMethod->getMethod()
                )->setDangerousGoods(
                    $dangerousGoods
                );

            $shipment->addTrack($trackModel);
            $shipment->save();

        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\LocalizedException(__($exception->getMessage()));
        }
    }
}
