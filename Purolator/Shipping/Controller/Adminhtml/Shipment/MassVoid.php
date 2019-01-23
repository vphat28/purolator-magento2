<?php

namespace Purolator\Shipping\Controller\Adminhtml\Shipment;

use Purolator\Shipping\Model\Purolator\ShipmentService;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\ResultFactory;

class MassVoid extends \Magento\Backend\App\Action
{
    /**
     * @var ShipmentService
     */
    protected $purolatorVoidService;

    /**
     * @var \Purolator\Shipping\Model\PurolatorShipmentRepository
     */
    protected $purolatorShipmentRepository;

    /**
     * @var \Magento\Sales\Model\Order\ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var Collection
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var \Purolator\Shipping\Model\ResourceModel\PurolatorShipment\CollectionFactory
     */
    protected $purolatorShipmentCollectionFactory;

    /**
     * MassVoid constructor.
     * @param Context $context
     * @param Filter $filter
     * @param \Purolator\Shipping\Model\Purolator\VoidService $purolatorVoidService
     * @param \Purolator\Shipping\Model\PurolatorShipmentRepository $purolatorShipmentRepository
     * @param \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository
     * @param \Purolator\Shipping\Model\PurolatorShipmentFactory $purolatorShipmentFactory
     * @param \Purolator\Shipping\Model\ResourceModel\PurolatorShipment\CollectionFactory $purolatorShipmentCollectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        \Purolator\Shipping\Model\Purolator\VoidService $purolatorVoidService,
        \Purolator\Shipping\Model\PurolatorShipmentRepository $purolatorShipmentRepository,
        \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository,
        \Purolator\Shipping\Model\PurolatorShipmentFactory $purolatorShipmentFactory,
        \Purolator\Shipping\Model\ResourceModel\PurolatorShipment\CollectionFactory $purolatorShipmentCollectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->purolatorVoidService = $purolatorVoidService;
        $this->purolatorShipmentRepository = $purolatorShipmentRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->messageManager = $context->getMessageManager();
        $this->purolatorShipmentCollectionFactory = $purolatorShipmentCollectionFactory;
    }

    public function execute()
    {
        try {

            $collection = $this->filter->getCollection($this->purolatorShipmentCollectionFactory->create());
            $this->massAction($collection);

        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/index');
    }

	protected function massAction(AbstractCollection $collection)
    {
        $added = 0;
        $failed = 0;
        foreach ($collection as $purolatorShipment) {

            $response = $this->purolatorVoidService->voidShipment($purolatorShipment->getShipmentPin());
            if (!empty($response->ShipmentVoided)) {

                $shipmentId = $purolatorShipment->getMagentoShipmentId();
                $this->purolatorShipmentRepository->delete($purolatorShipment);
                $purolatorShipmentCollection = $this->purolatorShipmentCollectionFactory->create();
                $count = $purolatorShipmentCollection->addFieldToFilter('magento_shipment_id', $shipmentId)->getSize();

                if (empty($count)) {
                    $this->shipmentRepository->deleteById($shipmentId);
                }

                $added++;
                continue;
            }

            $failed++;
        }

        if ($added > 0) {
            $this->messageManager->addSuccessMessage(__("{$added} shipments sucessfully voided."));
        }

        if ($failed > 0) {
            $this->messageManager->addErrorMessage(__("{$failed} shipments could not be voided."));
        }
    }
}
