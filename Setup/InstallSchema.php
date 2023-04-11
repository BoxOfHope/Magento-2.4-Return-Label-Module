<?php
namespace Boxofhope\ReturnLabelPlugin\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

/**
 * Class InstallSchema
 * @package PandaGroup\MyAdminController\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (!$installer->tableExists('boh_return_label')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('boh_return_label')
            )
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'Item id'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    255,
                    ['nullable => false'],
                    'Order ID'
                )
                ->addColumn(
                    'delivery_id',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable => false'],
                    'Boh Delivery ID'
                )
                ->addColumn(
                    'label_id',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable => false'],
                    'Boh Return Label ID'
                )
                ->addColumn(
                    'return_code',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Boh return code'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Code created at'
                )->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Code updated at')
                ->setComment('Created table for BoxOfHope Return label extension')
                ->setOption('charset', 'utf8')
                ->setOption('collate', 'utf8_general_ci');
            $installer->getConnection()->createTable($table);

            $installer->getConnection()->addIndex(
                $installer->getTable('boh_return_label'),
                $setup->getIdxName(
                    $installer->getTable('boh_return_label'),
                    ['label_id', 'return_code', 'order_id'],
                    AdapterInterface::INDEX_TYPE_FULLTEXT
                ),
                ['label_id', 'return_code', 'order_id'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            );

            $installer->getConnection()->addForeignKey(
                $installer->getFkName(
                    'boh_return_label',
                    'order_id',
                    'sales_order',
                    'entity_id'
                ),
                $installer->getTable('boh_return_label'),
                'order_id',
                $installer->getTable('sales_order'),
                'entity_id'
            );
        }
        $installer->endSetup();
    }
}
