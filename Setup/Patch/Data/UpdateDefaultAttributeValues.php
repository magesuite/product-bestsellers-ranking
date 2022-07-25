<?php
declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Setup\Patch\Data;

class UpdateDefaultAttributeValues implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    protected \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup;
    protected \Magento\Eav\Setup\EavSetup $eavSetup;

    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Eav\Setup\EavSetup $eavSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetup = $eavSetup;
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $this->eavSetup->updateAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'bestseller_score_by_turnover',
            'default_value',
            1
        );

        $this->eavSetup->updateAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'bestseller_score_by_amount',
            'default_value',
            1
        );

        $connection->endSetup();

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
