<?php
namespace Purolator\Shipping\Setup;
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if ($installer->tableExists('ch_purolator_shipment')) {
            $installer->endSetup();
            return ;
        }

        /**
         * Create table 'ch_purolator_shipment'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('ch_purolator_shipment')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )->addColumn(
            'magento_shipment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Magento Shipment Id'
        )->addColumn(
            'shipment_pin',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Shipment Pin'
        )->addColumn(
            'return_shipment_pin',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Return Shipment Pin'
        )->addColumn(
            'express_shipment_pin',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Express Shipment Pin'
        )->addColumn(
            'dangerous_goods',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            11,
            ['nullable' => false, 'default' => 0],
            'Dangerous Goods'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->setComment(
            'Purolator Shipment'
        );

        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}
