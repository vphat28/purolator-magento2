<?php

namespace Purolator\Shipping\Model\ResourceModel\Order\Shipment\GridCreate;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Shipment\Grid\Collection
{
    protected $request;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        \Magento\Framework\App\Request\Http $request,
        $mainTable = 'sales_shipment_grid',
        $resourceModel = \Magento\Sales\Model\ResourceModel\Order\Shipment::class
    ) {
        $this->request = $request;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }


    protected function _beforeLoad()
    {
        $this->addFieldToFilter('main_table.shipping_information', ['like' => "%Purolator%"]);
        $joinTable = $this->getTable('ch_purolator_shipment');
        $this->getSelect()->joinLeft($joinTable . ' as purolator_shipment',
            'main_table.entity_id = purolator_shipment.magento_shipment_id', array('*'));
        $this->addFieldToFilter('purolator_shipment.magento_shipment_id', array('null' => true));

        return parent::_beforeLoad();
    }
}
