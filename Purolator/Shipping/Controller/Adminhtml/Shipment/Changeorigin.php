<?php

namespace Purolator\Shipping\Controller\Adminhtml\Shipment;

use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Directory\Model\ResourceModel\Region;

class Changeorigin extends \Purolator\Shipping\Controller\Adminhtml\Shipment
{
    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    private $orderRepository;

    /** @var RegionInterfaceFactory */
    private $regionInterfaceFactory;

    /** @var Region */
    private $regionRepository;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Directory\Model\RegionFactory $regionInterfaceFactory,
        Region $regionRepository
    ) {
        parent::__construct($context, $coreRegistry, $fileFactory, $layoutFactory, $resultLayoutFactory, $resultPageFactory);
        $this->orderRepository = $orderRepository;
        $this->regionInterfaceFactory = $regionInterfaceFactory;
        $this->regionRepository = $regionRepository;
    }

    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getParams();
            $order = $this->orderRepository->get($params['order_id']);
            $payment = $order->getPayment();

            if (!isset($params['region_id'])) {
                // Use text for region
                $regionText = $params['region'];
                $payment->setAdditionalInformation('purolator_ship_from_region', $regionText);
            }

            if (!empty($params['region_id'])) {
                $payment->setAdditionalInformation('purolator_ship_from_region_id', $params['region_id']);
            }

            $payment->setAdditionalInformation('purolator_ship_from_city', $params['city']);
            $payment->setAdditionalInformation('purolator_ship_from_postcode', $params['postcode']);
            $payment->setAdditionalInformation('purolator_ship_from_country_id', $params['country_id']);
            $payment->setAdditionalInformation('purolator_ship_from_streetLine1', $params['street_line_1']);
            $this->orderRepository->save($order);
        }
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {


        return parent::dispatch($request);
    }

}