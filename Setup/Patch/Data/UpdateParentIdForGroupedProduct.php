<?php
declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Setup\Patch\Data;

class UpdateParentIdForGroupedProduct implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    protected \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup;

    protected \Magento\Framework\Serialize\SerializerInterface $serializer;

    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->serializer = $serializer;
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();
        $tableName = $connection->getTableName('sales_order_item');

        while (true) {
            $select = $connection->select()
                ->from($tableName, ['item_id', 'product_options'])
                ->where('product_type = ?', \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE)
                ->where('parent_product_id IS NULL')
                ->limit(500);
            $results = $connection->fetchAll($select);
            $data = [];

            if (empty($results)) {
                break;
            }

            foreach ($results as $result) {
                $parentProductId = 0;

                try {
                    $productOptions = $this->serializer->unserialize($result['product_options']);
                    $productOptions = new \Magento\Framework\DataObject($productOptions);
                    $parentProductId = (int)$productOptions->getDataByPath('info_buyRequest/super_product_config/product_id');
                } catch (\InvalidArgumentException $e) {
                    continue;
                }

                if (!$parentProductId) {
                    continue;
                }

                $data[$result['item_id']] = $parentProductId;
            }

            if (empty($data)) {
                continue;
            }

            $conditions = [];

            foreach ($data as $id => $parentProductId) {
                $case = $connection->quoteInto('?', $id);
                $result = $connection->quoteInto('?', $parentProductId);
                $conditions[$case] = $result;
            }

            $value = $connection->getCaseSql('item_id', $conditions, 'parent_product_id');
            $where = ['item_id IN (?)' => array_keys($data)];

            $connection->update($tableName, ['parent_product_id' => $value], $where);
        }

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
