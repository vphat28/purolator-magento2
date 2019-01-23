<?php

namespace Purolator\Shipping\Controller\Adminhtml\Shipment;

use Purolator\Shipping\Model\Purolator\ShipmentService;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;

class MassDeclaration extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Purolator\Shipping\Model\Purolator\DeclarationService
     */
    protected $purolatorDeclarationService;

    /**
     * @var \Purolator\Shipping\Model\PurolatorShipmentRepository
     */
    protected $purolatorShipmentRepository;

    /**
     * @var \Purolator\Shipping\Model\PurolatorShipmentFactory
     */
    protected $purolatorShipmentFactory;

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
     * @var \Purolator\Shipping\Helper\Data
     */
    protected $helper;

    /**
     * @var \Purolator\Shipping\Model\ResourceModel\PurolatorShipment\CollectionFactory
     */
    protected $purolatorShipmentCollectionFactory;

    /**
     * @var \Magento\Sales\Model\Order\ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * MassDeclaration constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param \Purolator\Shipping\Helper\Data $helper
     * @param \Purolator\Shipping\Model\Purolator\DeclarationService $purolatorDeclarationService
     * @param \Purolator\Shipping\Model\PurolatorShipmentRepository $purolatorShipmentRepository
     * @param \Purolator\Shipping\Model\PurolatorShipmentFactory $purolatorShipmentFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Purolator\Shipping\Model\ResourceModel\PurolatorShipment\CollectionFactory $purolatorShipmentCollectionFactory
     * @param \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Purolator\Shipping\Helper\Data $helper,
        \Purolator\Shipping\Model\Purolator\DeclarationService $purolatorDeclarationService,
        \Purolator\Shipping\Model\PurolatorShipmentRepository $purolatorShipmentRepository,
        \Purolator\Shipping\Model\PurolatorShipmentFactory $purolatorShipmentFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Purolator\Shipping\Model\ResourceModel\PurolatorShipment\CollectionFactory $purolatorShipmentCollectionFactory,
        \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->purolatorDeclarationService = $purolatorDeclarationService;
        $this->purolatorShipmentRepository = $purolatorShipmentRepository;
        $this->purolatorShipmentFactory = $purolatorShipmentFactory;
        $this->messageManager = $context->getMessageManager();
        $this->helper = $helper;
        $this->fileFactory = $fileFactory;
        $this->purolatorShipmentCollectionFactory = $purolatorShipmentCollectionFactory;
        $this->shipmentRepository = $shipmentRepository;
    }

    public function execute()
    {
        try {

            $collection = $this->filter->getCollection($this->purolatorShipmentCollectionFactory->create());
            $pdf = $this->massAction($collection);

        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        $date = $this->_objectManager->get(
            \Magento\Framework\Stdlib\DateTime\DateTime::class
        )->date('Y-m-d_H-i-s');

        return $this->fileFactory->create(
            'declaration-' . $date . '.pdf',
            $pdf->render(),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
            'application/pdf'
        );
    }

	protected function massAction(AbstractCollection $collection)
    {
        $pdf = new \Zend_Pdf();
        foreach ($collection as $item) {

            $pdfString = $this->purolatorDeclarationService->getShipmentDocument($item->getShipmentPin());
            $pdf = $this->addPage($pdf, $pdfString);
        }

        return $pdf;
    }

    public function addPage($pdf, $pdfString)
    {
        $extractor = new \Zend_Pdf_Resource_Extractor();
        $temp_pdf = \Zend_Pdf::parse($pdfString);

        foreach ($temp_pdf->pages as $k => $v) {
            $page = $extractor->clonePage($temp_pdf->pages[$k]);
            $pdf->pages[] = $page;
        }

        return $pdf;
    }
}
