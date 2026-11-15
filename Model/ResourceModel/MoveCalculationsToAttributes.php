<?php

namespace MageSuite\ProductBestsellersRanking\Model\ResourceModel;

class MoveCalculationsToAttributes
{
    protected \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute;
    protected ?\Magento\Framework\DB\Adapter\AdapterInterface $connection;
    protected \Magento\Framework\EntityManager\MetadataPool $metadataPool;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool
    ) {
        $this->connection = $resourceConnection->getConnection();
        $this->eavAttribute = $eavAttribute;
        $this->metadataPool = $metadataPool;
    }

    public function execute()
    {
        $bestsellersCalculationTableName = $this->connection->getTableName('bestsellers_calculations');
        $catalogProductEntityIntTableName = $this->connection->getTableName('catalog_product_entity_int');
        $catalogProductEntityTableName = $this->connection->getTableName('catalog_product_entity');
        $linkField = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)->getLinkField();

        $attributeIds = [];

        foreach (\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation::ATTRIBUTES as $attributeCode) {
            $attributeId = $this->eavAttribute->getIdByCode(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);
            $attributeIds[] = (int)$attributeId;

            // the following cannot be implemented using native Magento query builder
            // there is no method to create insert into from select along with on duplicate key statement
            // phpcs:disable
            $this->connection->query("
               INSERT INTO
                    $catalogProductEntityIntTableName ($linkField, attribute_id, `value`, store_id)
               SELECT
                    $linkField, $attributeId, bc.$attributeCode, " . \Magento\Store\Model\Store::DEFAULT_STORE_ID . "
               FROM
                    {$bestsellersCalculationTableName} bc
               LEFT JOIN
                    {$catalogProductEntityTableName} cpe ON cpe.entity_id=bc.product_id
               ON DUPLICATE KEY
               UPDATE `value`=bc.$attributeCode
            ");
            // phpcs:enable
        }

        $this->deleteScoresOfProductsWithoutCalculation(
            $catalogProductEntityIntTableName,
            $catalogProductEntityTableName,
            $bestsellersCalculationTableName,
            $linkField,
            $attributeIds
        );
    }

    protected function deleteScoresOfProductsWithoutCalculation(
        string $catalogProductEntityIntTableName,
        string $catalogProductEntityTableName,
        string $bestsellersCalculationTableName,
        string $linkField,
        array $attributeIds
    ): void {
        $calculatedProducts = $this->connection->select()
            ->from(['bc' => $bestsellersCalculationTableName], [])
            ->join(['cpe' => $catalogProductEntityTableName], 'cpe.entity_id = bc.product_id', [$linkField]);

        $this->connection->delete(
            $catalogProductEntityIntTableName,
            [
                'store_id = ?' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                'attribute_id IN (?)' => $attributeIds,
                "$linkField NOT IN (?)" => $calculatedProducts
            ]
        );
    }
}
