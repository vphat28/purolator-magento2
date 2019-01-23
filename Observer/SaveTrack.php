<?php

namespace Purolator\Shipping\Observer;

use Magento\Framework\Event\ObserverInterface;

class SaveTrack implements ObserverInterface
{
    /**
     * @var \Purolator\Shipping\Model\PurolatorShipmentFactory
     */
    protected $purolatorShipmentFactory;

    /**
     * @var \Purolator\Shipping\Model\PurolatorShipmentRepository
     */
    protected $purolatorShipmentRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * SaveTrack constructor.
     * @param \Purolator\Shipping\Model\PurolatorShipmentFactory $purolatorShipmentFactory
     * @param \Purolator\Shipping\Model\PurolatorShipmentRepository $purolatorShipmentRepository
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Purolator\Shipping\Model\PurolatorShipmentFactory $purolatorShipmentFactory,
        \Purolator\Shipping\Model\PurolatorShipmentRepository $purolatorShipmentRepository,
        \Magento\Framework\Registry $registry
    )
    {
        $this->purolatorShipmentFactory = $purolatorShipmentFactory;
        $this->purolatorShipmentRepository = $purolatorShipmentRepository;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $track = $observer->getEvent()->getTrack();

        $shippingMethod = $track->getShipment()->getOrder()->getShippingMethod(true);
        if ($shippingMethod->getCarrierCode() != 'purolator') {
            return $this;
        }

        $dangerousGoods = $track->getDangerousGoods();
        if(empty($dangerousGoods)) {
            $dangerousGoods = $this->registry->registry('purolator_dangerous_goods');
        }

        $purolatorShipmentModel = $this->purolatorShipmentFactory->create()
            ->setShipmentPin($track->getTrackNumber())
            ->setMagentoShipmentId($track->getShipment()->getId())
            ->setDangerousGoods($dangerousGoods);

        try {
            $this->purolatorShipmentRepository->save($purolatorShipmentModel);
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\LocalizedException(__($exception->getMessage()));
        }
    }
}
