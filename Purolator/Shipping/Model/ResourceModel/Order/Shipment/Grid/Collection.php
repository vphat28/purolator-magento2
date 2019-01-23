<?php

namespace Purolator\Shipping\Model\ResourceModel\Order\Shipment\Grid;

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
        $resourceModel = \Purolator\Shipping\Model\ResourceModel\Order\Shipment::class
    ) {
        $this->request = $request;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    protected function _renderFiltersBefore() {
        $joinTable = $this->getTable('ch_purolator_shipment');
        $this->getSelect()->join($joinTable.' as purolator_shipment','main_table.entity_id = purolator_shipment.magento_shipment_id', array('*'));
        parent::_renderFiltersBefore();
    }
}


