<?php

namespace Purolator\Shipping\Controller\Adminhtml\Manifest;

class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Purolator_Shipping::purolator_manifest';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Purolator_Shipping::purolator_manifest');
        $resultPage->getConfig()->getTitle()->prepend(__('Purolator Manifest'));
        return $resultPage;
    }
}