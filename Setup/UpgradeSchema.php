<?php

namespace MageSuite\ProductBestsellersRanking\Setup;

class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.0.2', '<')) {
            $setup->getConnection()->addIndex(
                $setup->getTable('sales_order_item'),
                $setup->getIdxName('sales_order_item', ['product_id'], 'index'),
                ['product_id']
            );
        }

        $setup->endSetup();
    }
}
