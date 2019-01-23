<?php

namespace Purolator\Shipping\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addManifestTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws
     */
    private function addManifestTable($setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('ch_purolator_manifest'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entry ID'
            )
            ->addColumn(
                'shipment_manifest_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                256,
                ['nullable' => true],
                'shipment_manifest_date'
            )
            ->addColumn(
                'manifest_close_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                256,
                ['nullable' => true],
                'manifest_close_date'
            )
            ->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                128,
                ['nullable' => true, 'default' => 'pending'],
                'status'
            )
            ->addColumn(
                'document_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                256,
                ['nullable' => true],
                'document_type'
            )
            ->addColumn(
                'description',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                256,
                ['nullable' => true],
                'description'
            )
            ->addColumn(
                'url',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                512,
                ['nullable' => true],
                'url'
            )
            ->addIndex(
                $setup->getIdxName(
                    'ch_purolator_manifest',
                    ['id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            );
        $setup->getConnection()->createTable($table);
    }
}
