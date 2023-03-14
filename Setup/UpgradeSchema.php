<?php

namespace Paytiko\PaytikoPayments\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements  UpgradeSchemaInterface
{
	public function upgrade(SchemaSetupInterface $setup,
							ModuleContextInterface $context
						)
	{
		$setup->startSetup();

			// Get module table
			$tableName = $setup->getTable('sales_order');

			// Check if the table already exists
			if ($setup->getConnection()->isTableExists($tableName) == true)
			{
				// Declare column definition
				$definition =  [
									'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
									'nullable' => false,
									'comment' => 'Is placed by Paytiko',
									'default' => 0
								];

				$connection = $setup->getConnection();

				$connection->addColumn($tableName, 'paytiko_order_ref', $definition);
				$connection->addColumn($tableName, 'paytiko_order_id', $definition);			 
			}
		

		$setup->endSetup();
	}
}