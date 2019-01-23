<?php

namespace Purolator\Shipping\Controller\Adminhtml\Shipment;

class Index extends \Purolator\Shipping\Controller\Adminhtml\Shipment
{

	public function execute()
	{

        if ($this->getRequest()->getQuery('ajax')) {
            $resultForward = $this->_resultForwardFactory->create();
            $resultForward->forward('grid');

            return $resultForward;
        }

		$resultPage = $this->resultPageFactory->create();

		/**
		 * Set active menu item
		 */
		$resultPage->setActiveMenu("Purolator_Shipping::purolator_shipment");
		$resultPage->getConfig()->getTitle()->prepend(__('Purolator Shipment'));

		return $resultPage;
	}
	
}