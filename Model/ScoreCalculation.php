<?php

namespace MageSuite\ProductBestsellersRanking\Model;

class ScoreCalculation
{
    public const DEFAULT_MULTIPLIER = 100;
    public const ATTRIBUTES = ['bestseller_score_by_amount', 'bestseller_score_by_turnover', 'bestseller_score_by_sale'];

    protected $useTransaction = false;
    protected $dryRun = false;

    protected ?\Magento\Framework\DB\Adapter\AdapterInterface $connection;
    protected \MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider $boostingFactorDataProvider;
    protected \MageSuite\ProductBestsellersRanking\DataProviders\OrdersPeriodFilterDataProvider $periodFilter;
    protected \MageSuite\ProductBestsellersRanking\DataProviders\MultiplierDataProvider $multiplierDataProvider;
    protected \Magento\Framework\EntityManager\MetadataPool $metadataPool;
    protected \MageSuite\ProductBestsellersRanking\Model\ResourceModel\MoveCalculationsToAttributes $moveCalculationsToAttributes;
    protected \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration;
    protected array $supportedProductTypes;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider $boostingFactorDataProvider,
        \MageSuite\ProductBestsellersRanking\DataProviders\OrdersPeriodFilterDataProvider $ordersPeriodFilterDataProvider,
        \MageSuite\ProductBestsellersRanking\DataProviders\MultiplierDataProvider $multiplierDataProvider,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration,
        \MageSuite\ProductBestsellersRanking\Model\ResourceModel\MoveCalculationsToAttributes $moveCalculationsToAttributes,
        $supportedProductTypes = []
    ) {
        $this->connection = $resourceConnection->getConnection();
        $this->boostingFactorDataProvider = $boostingFactorDataProvider;
        $this->periodFilter = $ordersPeriodFilterDataProvider;
        $this->multiplierDataProvider = $multiplierDataProvider;
        $this->metadataPool = $metadataPool;
        $this->configuration = $configuration;
        $this->moveCalculationsToAttributes = $moveCalculationsToAttributes;
        $this->supportedProductTypes = $supportedProductTypes;
    }

    public function setUseTransaction($useTransaction)
    {
        $this->useTransaction = $useTransaction;
    }

    public function setDryRun($dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function recalculateScore()
    {
        $this->connection->delete($this->connection->getTableName('bestsellers_calculations'));

        foreach ($this->supportedProductTypes as $productType) {
            $this->calculateScoresByProductType($productType);
        }

        if ($this->configuration->getSortOrder() === \MageSuite\ProductBestsellersRanking\Model\Config\Source\SortingDirection::DIRECTION_DESC) {
            $this->reverseScoresSorting();
        }

        if ($this->dryRun) {
            return;
        }

        if (!$this->useTransaction) {
            $this->moveCalculationsToAttributes->execute();
            return;
        }

        try {
            $this->connection->beginTransaction();
            $this->moveCalculationsToAttributes->execute();
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    public function calculateScores($productIds, $productType = 'simple', $multipliers = []): array
    {
        $bestsellerScores = [];

        $query = $this->getBaseQuery($productIds, $productType);

        $results = $this->connection->fetchAll($query);

        if (!$results) {
            return $bestsellerScores;
        }

        foreach ($results as $result) {
            $productId = in_array($productType, ['grouped']) ? $result['parent_product_id'] : $result['product_id'];
            $productMultiplier = $multipliers[$productId] ?? self::DEFAULT_MULTIPLIER;

            $qtyMultiplier = isset($result['qty']) && (float)$result['qty'] == 0 ? $this->configuration->getSoldOutFactor() : 1;

            $scores = [];

            $scores['bestseller_score_by_amount'] = 1 + round($result['sum_qty_ordered'] * $result['period_multiplier'] * $productMultiplier * $qtyMultiplier);
            $scores['bestseller_score_by_turnover'] = 1 + round($result['sum_turnover'] * $result['period_multiplier'] * self::DEFAULT_MULTIPLIER * $productMultiplier * $qtyMultiplier);
            $scores['bestseller_score_by_sale'] = 1 + round($result['count_ordered'] * $result['period_multiplier'] * $productMultiplier * $qtyMultiplier);

            if (!isset($bestsellerScores[$productId])) {
                $bestsellerScores[$productId] = ['product_id' => (int)$productId];

                foreach (self::ATTRIBUTES as $attributeCode) {
                    $bestsellerScores[$productId][$attributeCode] = 0;
                }
            }

            foreach (self::ATTRIBUTES as $attributeCode) {
                $bestsellerScores[$productId][$attributeCode] += $scores[$attributeCode];
            }
        }

        return $bestsellerScores;
    }

    public function getBaseQuery($productIds, $productType = 'simple')
    {
        $salesOrderItemTable = $this->connection->getTableName('sales_order_item');
        $stockTableName = $this->connection->getTableName('cataloginventory_stock_item');

        $sql = $this->connection->select()->from(['soi' => $salesOrderItemTable], ['product_id', 'parent_product_id']);

        $productIdColumn = in_array($productType, ['grouped']) ? 'parent_product_id' : 'product_id';

        $sql->join(
            ['st' => $stockTableName],
            sprintf('st.product_id = soi.%s', $productIdColumn),
            'st.qty'
        );

        $caseConditions = [];

        foreach ($this->boostingFactorDataProvider->getBoostingFactors() as $period) {
            if ((float)$period['value'] <= 0) {
                continue;
            }

            $from = date('Y-m-d', strtotime(sprintf('-%s days', $period['max_days_old'])));
            $caseConditions[] = sprintf("WHEN `created_at` > '%s' THEN %s", $from, $period['value']);
        }

        $createdAtFrom = $this->periodFilter->getOrdersPeriodFilter() ?? $from;

        $sql->where(sprintf('soi.%s IN(?)', $productIdColumn), $productIds);
        $sql->where("created_at >= ?", $createdAtFrom);
        $sql->columns("SUM(qty_ordered) AS sum_qty_ordered");
        $sql->columns("SUM(row_total) AS sum_turnover");
        $sql->columns("COUNT(DISTINCT order_id) AS count_ordered");
        $sql->columns(new \Zend_Db_Expr(sprintf("CASE %s END as `period_multiplier`", implode(' ', $caseConditions))));

        if ($productType === 'simple') {
            $sql->where('parent_product_id IS NULL');
            $sql->where('parent_item_id IS NULL');
        }

        $sql->where('row_total > 0');

        $sql->group([$productIdColumn, 'period_multiplier']);

        return $sql;
    }

    protected function getProductsToCalculateRating($productType)
    {
        $bestsellerScoreMultiplierAttributeId = $this->configuration->getScoreMultiplierAttributeId();
        $linkField = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)->getLinkField();

        $page = 1;

        do {
            $productsQuery = $this->connection->select()->from(
                ['p' => $this->connection->getTableName('catalog_product_entity')],
                ['entity_id', 'type_id']
            )->joinLeft(
                ['bsm' => $this->connection->getTableName('catalog_product_entity_int')],
                "bsm.{$linkField} = p.{$linkField} AND bsm.attribute_id = {$bestsellerScoreMultiplierAttributeId}",
                ['bestseller_score_multiplier' => 'value']
            )
            ->where('p.type_id = ?', $productType)
            ->group("p.entity_id")
            ->limitPage($page, $this->configuration->getBatchSize());

            $results = $this->connection->fetchAll($productsQuery);

            $page++;

            yield $results;
        } while (!empty($results));
    }

    protected function calculateScoresByProductType(string $productType): void
    {
        foreach ($this->getProductsToCalculateRating($productType) as $products) {
            $productIds = [];
            $multipliers = [];

            foreach ($products as $product) {
                $productId = (int)$product['entity_id'];
                $productIds[] = $productId;
                $multipliers[$productId] = $product['bestseller_score_multiplier'] ?? self::DEFAULT_MULTIPLIER;
            }

            $scores = $this->calculateScores($productIds, $productType, $multipliers);

            if (empty($scores)) {
                continue;
            }

            $this->connection->insertOnDuplicate(
                $this->connection->getTableName('bestsellers_calculations'),
                $scores,
                array_keys($scores[array_key_first($scores)])
            );
        }
    }

    protected function reverseScoresSorting(): void
    {
        $updateColumns = [];

        foreach (self::ATTRIBUTES as $attributeCode) {
            $max = $this->connection->fetchCol("
                SELECT
                    MAX($attributeCode)
                FROM
                     {$this->connection->getTableName('bestsellers_calculations')}
            ")[0] ?? 0;

            $updateColumns[$attributeCode] = new \Zend_Db_Expr(((int)$max+1) . ' - ' . $attributeCode);
        }

        $this->connection->update(
            $this->connection->getTableName('bestsellers_calculations'),
            $updateColumns,
        );
    }
}
