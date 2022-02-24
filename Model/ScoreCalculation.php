<?php

namespace MageSuite\ProductBestsellersRanking\Model;

class ScoreCalculation
{
    private $boostingFactors;

    private $storeId;

    private $ordersPeriodFilter;

    protected $productsScoreArray = [];

    protected $dataArray = [];

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $productModel;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $productResource;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Action
     */
    protected $productResourceAction;

    /**
     * @var \MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider
     */
    protected $boostingFactorDataProvider;

    /**
     * @var \MageSuite\ProductBestsellersRanking\DataProviders\OrdersPeriodFilterDataProvider
     */
    protected $periodFilter;

    /**
     * @var \MageSuite\ProductBestsellersRanking\DataProviders\MultiplierDataProvider
     */
    protected $multiplierDataProvider;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Repository\OrderItemsCollection
     */
    protected $ordersItemsCollection;

    /**
     * @var integer
     */
    protected $sortOrder;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     */
    protected $eavAttribute;

    /**
     * @var array
     */
    protected $maxScores = [];

    /**
     * @var \Magento\GroupedProduct\Model\ResourceModel\Product\Link
     */
    protected $productLink;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    protected $metadataPool;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Model\ResourceModel\Product\Action $productResourceAction,
        \MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider $boostingFactorDataProvider,
        \MageSuite\ProductBestsellersRanking\DataProviders\OrdersPeriodFilterDataProvider $ordersPeriodFilterDataProvider,
        \MageSuite\ProductBestsellersRanking\DataProviders\MultiplierDataProvider $multiplierDataProvider,
        \MageSuite\ProductBestsellersRanking\Repository\OrderItemsCollection $ordersItemsCollection,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productModel = $productModel;
        $this->productResource = $productResource;
        $this->productResourceAction = $productResourceAction;
        $this->boostingFactorDataProvider = $boostingFactorDataProvider;
        $this->periodFilter = $ordersPeriodFilterDataProvider;
        $this->multiplierDataProvider = $multiplierDataProvider;
        $this->ordersItemsCollection = $ordersItemsCollection;
        $this->eavAttribute = $eavAttribute;
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->metadataPool = $metadataPool;
        $this->maxScores = [
            'bestseller_score_by_amount' => 0,
            'bestseller_score_by_turnover' => 0,
            'bestseller_score_by_sale' => 0
        ];
    }

    protected function applyParameters()
    {
        $this->boostingFactors = $this->boostingFactorDataProvider->getBoostingFactors();
        $this->storeId = $this->storeManager->getStore()->getId();
        $this->ordersPeriodFilter = $this->periodFilter->getOrdersPeriodFilter();
        $this->sortOrder = $this->scopeConfig->getValue('bestsellers/sorting/direction', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function recalculateScore()
    {
        $this->connection = $this->resourceConnection->getConnection();

        $this->applyParameters();
        $this->calculateProductRating();
    }

    public function calculateProductRating()
    {
        $products = $this->getProductsToCalculateRating();
        $soldOutFactor = floatval($this->scopeConfig->getValue('bestsellers/boosting_factors/boosting_factor_sold_out', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

        /**
         * @var \Dmatthew\AttributeDescription\Model\Entity\Attribute\Interceptor $attribute
         * @var \Magento\Catalog\Model\Product\Interceptor $product
         */
        foreach ($products as $product) {
            $multiplier = $product['bestseller_score_multiplier'] ?? 100;
            $price = $product['price_from_index'] ?: $product['price_from_attribute'];
            $productId = $product['entity_id'];

            if ($product['type_id'] === \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
                $this->buildSelectForGroupedProduct($productId, $soldOutFactor);
            } else {
                $updatedBestsellerScores = $this->buildSelectForProduct($productId, $price, $multiplier, $soldOutFactor);

                if (!empty($updatedBestsellerScores)) {
                    $this->updateBestsellerScores($productId, $updatedBestsellerScores);
                }
            }
        }

        if ($this->sortOrder == 'desc') {
            $table = $this->connection->getTableName('catalog_product_entity_int');
            $amountScoreAttributeId = $this->eavAttribute->getIdByCode('catalog_product', 'bestseller_score_by_amount');
            $turnoverScoreAttributeId = $this->eavAttribute->getIdByCode('catalog_product', 'bestseller_score_by_turnover');
            $saleScoreAttributeId = $this->eavAttribute->getIdByCode('catalog_product', 'bestseller_score_by_sale');

            $this->connection->update($table, [
                'value' => new \Zend_Db_Expr($this->maxScores['bestseller_score_by_amount'] + 1 . ' - value'),
            ], ['attribute_id = ?' => $amountScoreAttributeId]);

            $this->connection->update($table, [
                'value' => new \Zend_Db_Expr($this->maxScores['bestseller_score_by_turnover'] + 1 . ' - value'),
            ], ['attribute_id = ?' => $turnoverScoreAttributeId]);

            $this->connection->update($table, [
                'value' => new \Zend_Db_Expr($this->maxScores['bestseller_score_by_sale'] + 1 . ' - value'),
            ], ['attribute_id = ?' => $saleScoreAttributeId]);
        }
    }

    public function buildSelectForProduct($productId, $price, $multiplier, $soldOutFactor, $parentProductId = null): array
    {
        return $this->buildQueryByPeriodBooster($productId, $price, $multiplier, $soldOutFactor, $parentProductId);
    }

    public function buildSelectForGroupedProduct($productId, $soldOutFactor): array
    {
        $this->searchCriteriaBuilder->addFilter('parent_product_id', $productId);
        $this->searchCriteriaBuilder->addFilter('store_id', $this->storeId);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $childItems = $this->orderItemRepository->getList($searchCriteria);

        $itemIds = array_map(function ($item) {
            return $item->getItemId();
        }, $childItems->getItems());

        if (empty($itemIds)) {
            return [];
        }

        $childrenBestsellerScores = [];
        foreach ($childItems as $item) {
            $multiplier = $this->productResource->getAttributeRawValue($item->getProductId(), 'bestseller_score_multiplier', $this->storeId);
            $multiplier = $multiplier === null ? 100 : $multiplier;

            $bestsellerScores = $this->buildSelectForProduct($item->getProductId(), $item->getPrice(), $multiplier, $soldOutFactor, $productId);
            $childrenBestsellerScores[$item->getId()] = $bestsellerScores;
        }
        $this->updateBestsellerScoresForGroupedProduct($productId, $childrenBestsellerScores);

        return $itemIds;
    }

    public function getBaseQuery($productId, $parentProductId = null)
    {
        $tableName = $this->resourceConnection->getTableName('sales_order_item');
        $stockTableName = $this->resourceConnection->getTableName('cataloginventory_stock_item');

        $sql = $this->connection
            ->select()
            ->from($tableName, [
                'item_id',
                'qty_ordered',
                'product_id',
                'created_at'
            ]);

        if ($parentProductId !== null) {
            $conditions = [
                $this->connection->quoteInto($tableName . '.product_id = ?', $productId),
                $this->connection->quoteInto($tableName . '.parent_product_id = ?', $parentProductId)
            ];
            $sql->where(implode(' AND ', $conditions));
        } else {
            $conditions = [
                $this->connection->quoteInto($tableName . '.product_id = ?', $productId),
                $this->connection->quoteInto($tableName . '.parent_product_id = ?', $productId)
            ];
            $sql->where(implode(' OR ', $conditions));
        }

        $sql->join($stockTableName, $stockTableName . '.product_id = ' . $productId, $stockTableName . '.qty');

        if ($this->periodFilter->getOrdersPeriodFilter()) {
            $sql->where("created_at >= '".$this->periodFilter->getOrdersPeriodFilter()."'");
        }

        return $sql;
    }

    public function buildQueryByPeriodBooster($productId, $price, $multiplier, $soldOutFactor, $parentProductId = null): array
    {
        $days = 0;

        $this->productResourceAction->updateAttributes(
            [$productId],
            [
                'bestseller_score_by_amount' => 1,
                'bestseller_score_by_turnover' => 1,
                'bestseller_score_by_sale' => 1
            ],
            $this->storeId
        );

        $bestsellerScores = [];
        foreach($this->boostingFactorDataProvider->getBoostingFactors() as $period) {
            $periodSql = $this->getBaseQuery($productId, $parentProductId);
            $from = date('Y-m-d 00:00:00', strtotime('-'.$period['max_days_old'].' days'));
            $to = date('Y-m-d 23:59:59', strtotime('-'.$days.' days'));

            $days = $period['max_days_old'];

            $periodSql->where("created_at >= '".$from."'");
            $periodSql->where("created_at <= '".$to."'");
            $periodSql->columns('SUM(`sales_order_item`.qty_ordered) AS sum_qty_ordered');
            $periodSql->columns('COUNT(`sales_order_item`.product_id) AS count_ordered')
                ->group('product_id');

            $result = $this->connection->fetchRow($periodSql);
            if ($result) {
                $qtyMultiplier = 1;
                if (isset($result['qty']) && floatval($result['qty']) == 0) {
                    $qtyMultiplier = $soldOutFactor;
                }

                $updatedAmountScore = 1 + round($result['sum_qty_ordered'] * $period['value'] * $multiplier * $qtyMultiplier);
                $updatedTurnoverScore = 1 + round($result['sum_qty_ordered'] * $price * $period['value'] * 100 * $multiplier * $qtyMultiplier);
                $updatedSalesScore = 1 + round($result['count_ordered'] * $period['value'] * $multiplier * $qtyMultiplier);

                if ($this->maxScores['bestseller_score_by_amount'] < $updatedAmountScore) {
                    $this->maxScores['bestseller_score_by_amount'] = $updatedAmountScore;
                }

                if ($this->maxScores['bestseller_score_by_turnover'] < $updatedTurnoverScore) {
                    $this->maxScores['bestseller_score_by_turnover'] = $updatedTurnoverScore;
                }

                if ($this->maxScores['bestseller_score_by_sale'] < $updatedSalesScore) {
                    $this->maxScores['bestseller_score_by_sale'] = $updatedSalesScore;
                }

                if (!isset($bestsellerScores[$result['product_id']])) {
                    $bestsellerScores[$result['product_id']] = [
                        'bestseller_score_by_amount' => 0,
                        'bestseller_score_by_turnover' => 0,
                        'bestseller_score_by_sale' => 0
                    ];
                }

                $bestsellerScores[$result['product_id']]['bestseller_score_by_amount'] += $updatedAmountScore;
                $bestsellerScores[$result['product_id']]['bestseller_score_by_turnover'] += $updatedTurnoverScore;
                $bestsellerScores[$result['product_id']]['bestseller_score_by_sale'] += $updatedSalesScore;
            }
        }
        return $bestsellerScores;
    }

    protected function updateBestsellerScores($productId, $bestsellerScores)
    {
        foreach ($bestsellerScores as $scores) {
            if (empty($scores)) {
                continue;
            }
            $this->productResourceAction->updateAttributes(
                [$productId],
                $scores,
                $this->storeId
            );
        }
    }

    protected function updateBestsellerScoresForGroupedProduct($productId, $bestsellerScores)
    {
        $bestsellerScoresSum = [
            'bestseller_score_by_amount' => 0,
            'bestseller_score_by_turnover' => 0,
            'bestseller_score_by_sale' => 0
        ];
        foreach ($bestsellerScores as $scores) {
            if (empty($scores)) {
                continue;
            }
            $scores = reset($scores);
            array_walk($bestsellerScoresSum, function (&$value, $key) use ($scores) {
                $value += (int) $scores[$key];
            });
        }

        $this->productResourceAction->updateAttributes(
            [$productId],
            $bestsellerScoresSum,
            $this->storeId
        );
    }

    protected function getProductsToCalculateRating()
    {
        $bestsellerScoreMultiplierAttributeId = $this->eavAttribute->getIdByCode('catalog_product', 'bestseller_score_multiplier');
        $priceAttributeId = $this->eavAttribute->getIdByCode('catalog_product', 'price');
        $linkField = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)->getLinkField();

        $productsQuery = $this->connection->select()->from(
            ['p' => $this->connection->getTableName('catalog_product_entity')],
            ['entity_id', 'type_id']
        )->joinLeft(
            ['bsm' => $this->connection->getTableName('catalog_product_entity_int')],
            "bsm.{$linkField} = p.{$linkField} AND bsm.attribute_id = {$bestsellerScoreMultiplierAttributeId}",
            ['bestseller_score_multiplier' => 'value']
        )->joinLeft(
            ['pfi' => $this->connection->getTableName('catalog_product_index_price')],
            "pfi.entity_id = p.entity_id",
            ['price_from_index' => 'price']
        )->joinLeft(
            ['pfa' => $this->connection->getTableName('catalog_product_entity_decimal')],
            "pfa.{$linkField} = p.{$linkField} AND pfa.attribute_id = {$priceAttributeId}",
            ['price_from_attribute' => 'value']
        )->group("p.entity_id");

        return $this->connection->fetchAll($productsQuery);
    }
}
