<?php

namespace Purolator\Shipping\Controller\Adminhtml\Shipment;

use Magento\Sales\Model\Order\Shipment\Validation\QuantityValidator;

class Batchshipment extends \Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save
{
    private $shipmentValidator;

    /** @var \Magento\Framework\Registry */
    private $registry;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
        \Magento\Shipping\Model\Shipping\LabelGenerator $labelGenerator,
        \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct(
            $context,
            $shipmentLoader,
            $labelGenerator,
            $shipmentSender
        );

        $this->registry = $registry;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        try {
            foreach ($params['selected'] as $selected) {
                $data = [];
                $data['comment_text'] = __('Shipment created by batch');
                $orderId = $selected;
                $this->shipmentLoader->setOrderId($orderId);
                $this->shipmentLoader->setShipmentId(null);
                $this->shipmentLoader->setShipment($data);
                $this->shipmentLoader->setTracking(null);
                $shipment = $this->shipmentLoader->load();

                if(empty($shipment)) {
                    return $this->_redirect('sales/order/index');
                }

                $validationResult = $this->getShipmentValidator()
                    ->validate($shipment, [QuantityValidator::class]);

                if ($validationResult->hasMessages()) {
                    $this->messageManager->addErrorMessage(
                        __("Shipment Document Validation Error(s):\n" . implode("\n", $validationResult->getMessages()))
                    );
                    $this->_redirect('sales/order/index');
                    return;
                }

                $shipment->register();

                $shipment->getOrder()->setCustomerNoteNotify(!empty(@$data['send_email']));

                $this->_saveShipment($shipment);
                $this->registry->unregister('current_shipment');
            }
        } catch (\Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);

            $this->messageManager->addErrorMessage(__('Cannot save shipment.'));
        }

        $this->_redirect('sales/order/index');
    }

    private function getShipmentValidator()
    {
        if ($this->shipmentValidator === null) {
            $this->shipmentValidator = $this->_objectManager->get(
                \Magento\Sales\Model\Order\Shipment\ShipmentValidatorInterface::class
            );
        }

        return $this->shipmentValidator;
    }
}