<?php
namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class CalculationMethodsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\ScoreCalculation
     */
    private $scoreCalculationModel;

    /**
     * @var \MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider
     */
    private $boostingFactorDataProvider;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function setUp(): void
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->scoreCalculationModel = $objectManager->create(\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation::class);
        $this->boostingFactorDataProvider = $objectManager->get(\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider::class);
        $this->productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadProducts
     * @magentoDataFixture loadOrders
     */
    public function testCalculation()
    {
        $scoreCalculationModel = $this->scoreCalculationModel;
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $scoreCalculationModel->recalculateScore();

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);

        $product = $this->productRepository->get('simple-100000');
        $this->assertEquals(6001, $product->getBestsellerScoreByAmount());
        $this->assertEquals(6000001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(301, $product->getBestsellerScoreBySale());

        $product = $this->productRepository->get('simple-400000');
        $this->assertEquals(5101, $product->getBestsellerScoreByAmount());
        $this->assertEquals(20400001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(301, $product->getBestsellerScoreBySale());

        $product = $this->productRepository->get('simple-600000');
        $this->assertEquals(4501, $product->getBestsellerScoreByAmount());
        $this->assertEquals(27000001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(301, $product->getBestsellerScoreBySale());

        $product = $this->productRepository->get('simple-1200000');
        $this->assertEquals(2701, $product->getBestsellerScoreByAmount());
        $this->assertEquals(32400001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(301, $product->getBestsellerScoreBySale());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadGroupedProducts
     * @magentoDataFixture loadGroupedOrders
     */
    public function testCalculationForBundle()
    {
        $scoreCalculationModel = $this->scoreCalculationModel;
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $scoreCalculationModel->recalculateScore();

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);
        $product = $this->productRepository->get('grouped');

        $bestsellerScoreByAmount = [6001, 5101, 4501, 2701];
        $bestsellerScoreByTurnover = [6000001, 20400001, 27000001, 32400001];
        $bestsellerScoreBySale = [301, 301, 301, 301];

        $this->assertEquals(array_sum($bestsellerScoreByAmount), $product->getBestsellerScoreByAmount());
        $this->assertEquals(array_sum($bestsellerScoreByTurnover), $product->getBestsellerScoreByTurnover());
        $this->assertEquals(array_sum($bestsellerScoreBySale), $product->getBestsellerScoreBySale());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadProducts
     * @magentoDataFixture loadOrders
     */
    public function testCalculationIncludingMultiplier()
    {
        $scoreCalculationModel = $this->scoreCalculationModel;
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $scoreCalculationModel->recalculateScore();

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);

        $product = $this->productRepository->get('simple-3000000');
        $this->assertEquals(31, $product->getBestsellerScoreByAmount());
        $this->assertEquals(600001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(31, $product->getBestsellerScoreBySale());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadProducts
     * @magentoDataFixture loadOrders
     * @magentoConfigFixture current_store bestsellers/sorting/direction desc
     */
    public function testCalculationWithDescendingOrder()
    {
        $scoreCalculationModel = $this->scoreCalculationModel;
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $scoreCalculationModel->recalculateScore();

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);

        $product = $this->productRepository->get('simple-1200000');
        $this->assertEquals(3301, $product->getBestsellerScoreByAmount());
        $this->assertEquals(600001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(1, $product->getBestsellerScoreBySale());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadProducts
     * @magentoDataFixture loadOrders
     * @magentoConfigFixture current_store bestsellers/boosting_factors/boosting_factor_sold_out 0.5
     */
    public function testSoldOutProductCalculation()
    {
        $scoreCalculationModel = $this->scoreCalculationModel;
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $scoreCalculationModel->recalculateScore();

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);

        $product = $this->productRepository->get('simple-4000000');
        $this->assertEquals(151, $product->getBestsellerScoreByAmount());
        $this->assertEquals(3000001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(151, $product->getBestsellerScoreBySale());
    }

    public static function loadProducts() {
        include __DIR__.'/../../_files/product_add.php';
    }

    public static function loadOrders() {
        include __DIR__.'/../../_files/order.php';
    }

    public static function loadGroupedProducts() {
        include __DIR__.'/../../_files/product_grouped_with_simple.php';
    }

    public static function loadGroupedOrders() {
        include __DIR__.'/../../_files/order_with_grouped_product.php';
    }

    protected function getBoostingFactorArray(){
        return [
            'boosterA' =>
                [
                    'value' => 3,
                    'max_days_old' => 7
                ],
            'boosterB' =>
                [
                    'value' => 2,
                    'max_days_old' => 30
                ],
            'boosterC' =>
                [
                    'value' => 1,
                    'max_days_old' => 365
                ],
            'boosterD' =>
                [
                    'value' => 0,
                    'max_days_old' => 999999999
                ]
        ];
    }

    protected function getProductMapper()
    {
        return [
            100000 => [
                'price' => 10,
            ],
            200000 => [
                'price' => 20,
            ],
            300000 => [
                'price' => 30,
            ],
            400000 => [
                'price' => 40,
            ],
            500000 => [
                'price' => 50,
            ],
            600000 => [
                'price' => 60,
            ],
            700000 => [
                'price' => 70,
            ],
            800000 => [
                'price' => 80,
            ],
            900000 => [
                'price' => 90,
            ],
            1000000 => [
                'price' => 100,
            ],
            1100000 => [
                'price' => 110,
            ],
            1200000 => [
                'price' => 120,
            ],
            1300000 => [
                'price' => 130,
            ],
            1400000 => [
                'price' => 140,
            ],
            1500000 => [
                'price' => 150,
            ],
            1600000 => [
                'price' => 160,
            ],
            1700000 => [
                'price' => 170,
            ],
            1800000 => [
                'price' => 180,
            ],
            1900000 => [
                'price' => 190,
            ],
            2000000 => [
                'price' => 200,
            ],
            3000000 => [
                'price' => 200,
                'multiplier' => 10
            ],
            4000000 => [
                'price' => 200,
                'qty' => 0
            ]
        ];
    }

    protected function getOrderMapper()
    {
        return [
            2000001 => [
                'price' => 10,
                'product_price' => 10,
                'product_id' => 100000,
                'qty_ordered' => 20
            ],
            2000002 => [
                'price' => 20,
                'product_price' => 20,
                'product_id' => 200000,
                'qty_ordered' => 19
            ],
            2000003 => [
                'price' => 30,
                'product_price' => 30,
                'product_id' => 300000,
                'qty_ordered' => 18
            ],
            2000004 => [
                'price' => 40,
                'product_price' => 40,
                'product_id' => 400000,
                'qty_ordered' => 17
            ],
            2000005 => [
                'price' => 50,
                'product_price' => 50,
                'product_id' => 500000,
                'qty_ordered' => 16
            ],
            2000006 => [
                'price' => 60,
                'product_price' => 60,
                'product_id' => 600000,
                'qty_ordered' => 15
            ],
            2000007 => [
                'price' => 70,
                'product_price' => 70,
                'product_id' => 700000,
                'qty_ordered' => 14
            ],
            2000008 => [
                'price' => 80,
                'product_price' => 80,
                'product_id' => 800000,
                'qty_ordered' => 13
            ],
            2000009 => [
                'price' => 90,
                'product_price' => 90,
                'product_id' => 900000,
                'qty_ordered' => 12
            ],
            20000010 => [
                'price' => 100,
                'product_price' => 100,
                'product_id' => 1000000,
                'qty_ordered' => 11
            ],
            20000011 => [
                'price' => 110,
                'product_price' => 110,
                'product_id' => 1100000,
                'qty_ordered' => 10
            ],
            20000012 => [
                'price' => 120,
                'product_price' => 120,
                'product_id' => 1200000,
                'qty_ordered' => 9
            ],
            20000013 => [
                'price' => 130,
                'product_price' => 130,
                'product_id' => 1300000,
                'qty_ordered' => 8
            ],
            20000014 => [
                'price' => 140,
                'product_price' => 140,
                'product_id' => 1400000,
                'qty_ordered' => 7
            ],
            20000015 => [
                'price' => 150,
                'product_price' => 150,
                'product_id' => 1500000,
                'qty_ordered' => 6
            ],
            20000016 => [
                'price' => 160,
                'product_price' => 160,
                'product_id' => 1600000,
                'qty_ordered' => 5
            ],
            20000017 => [
                'price' => 170,
                'product_price' => 170,
                'product_id' => 1700000,
                'qty_ordered' => 4
            ],
            20000018 => [
                'price' => 180,
                'product_price' => 180,
                'product_id' => 1800000,
                'qty_ordered' => 3
            ],
            20000019 => [
                'price' => 190,
                'product_price' => 190,
                'product_id' => 1900000,
                'qty_ordered' => 2
            ],
            20000020 => [
                'price' => 200,
                'product_price' => 200,
                'product_id' => 2000000,
                'qty_ordered' => 1
            ]
        ];
    }
}
