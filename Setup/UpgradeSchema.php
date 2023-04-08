<?php
namespace Paytiko\PaytikoPayments\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $tableName = $setup->getTable('sales_order');
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            $definition = [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => false,
                'comment' => 'Added by Paytiko'
            ];
            $connection = $setup->getConnection();
            $connection->addColumn($tableName, 'paytiko_order_ref', $definition);
        }
        $setup->endSetup();
    }
}