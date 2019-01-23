<?php

namespace Purolator\Shipping\Controller\Adminhtml\Manifest;

use Purolator\Shipping\Model\Purolator\ManifestService;
use Purolator\Shipping\Model\PurolatorManifest;
use Purolator\Shipping\Model\PurolatorManifestFactory;
use Purolator\Shipping\Model\ResourceModel\PurolatorManifest as PurolatorManifestResource;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\ObjectManager\FactoryInterface;

class Consolidate extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Purolator_Shipping::purolator_manifest';

    /** @var RedirectFactory */
    protected $resultPageFactory;

    /** @var ManifestService */
    private $manifestService;

    /** @var PurolatorManifestResource */
    private $purolatorManifestResource;

    /** @var PurolatorManifestFactory|FactoryInterface */
    private $purolatorManifestFactory;

    /**
     * Consolidate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param ManifestService $manifestService
     * @param PurolatorManifestResource $purolatorManifestResource
     * @param PurolatorManifestFactory $purolatorManifestFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        ManifestService $manifestService,
        PurolatorManifestResource $purolatorManifestResource,
        PurolatorManifestFactory $purolatorManifestFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $context->getResultRedirectFactory();
        $this->manifestService = $manifestService;
        $this->purolatorManifestResource = $purolatorManifestResource;
        $this->purolatorManifestFactory = $purolatorManifestFactory;
    }

    public function execute()
    {
        $result = $this->resultPageFactory->create();
        $this->manifestService->consolidate();
        $manifestResponse = $this->manifestService->getManifest();

        if (empty($manifestResponse)) {
            $result->setPath('*/*/index');
            $this->messageManager->addErrorMessage(__('Manifest already created for the shipments.'));
            
            return $result;
        }

        if (!property_exists($manifestResponse->ManifestBatches->ManifestBatch->ManifestBatchDetails, "ManifestBatchDetail")) {
            $result->setPath('*/*/index');
        }

        $manifest_details = $manifestResponse->ManifestBatches->ManifestBatch->ManifestBatchDetails->ManifestBatchDetail;

        $manifest_arr = array();
        if (count($manifest_details) > 1) {
            foreach ($manifest_details as $item) {
                array_push($manifest_arr, $item);
            }
            $latestManifest = $manifest_arr[count($manifest_arr) - 1];
        } else {
            $latestManifest = $manifest_details;
        }

        try {
            if (!property_exists($latestManifest, "URL") || ($latestManifest->URL == "")) {
                throw new \Exception();
            }

            /** @var PurolatorManifest $manifest */
            $manifest = $this->purolatorManifestFactory->create();

            $this->purolatorManifestResource->load($manifest, $latestManifest->URL, 'URL');

            if ($manifest->getData('id') == null) {
                /** @var PurolatorManifest $newManifest */
                $newManifest = $this->purolatorManifestFactory->create();
                $newManifest->setStatus($latestManifest->DocumentStatus)
                    ->setShipmentManifestDate($manifestResponse->ManifestBatches->ManifestBatch->ShipmentManifestDate)
                    ->setManifestCloseDate($manifestResponse->ManifestBatches->ManifestBatch->ManifestCloseDateTime)
                    ->setUrl($latestManifest->URL)
                    ->setDocumentType($latestManifest->DocumentType)
                    ->setDescription($latestManifest->Description);
                $this->purolatorManifestResource->save($newManifest);
            } else {
                $this->messageManager->addErrorMessage(__('Manifest already created for the shipments.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Purolator Module was not able to consolidate manifest.'));
        }

        $result->setPath('*/*/index');

        return $result;
    }
}