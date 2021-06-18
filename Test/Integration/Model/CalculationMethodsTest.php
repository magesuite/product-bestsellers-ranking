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

        //period 1 - 7 days
        $product = $this->productRepository->get('simple-100000');
        $this->assertEquals(6001, $product->getBestsellerScoreByAmount());
        $this->assertEquals(6000001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(301, $product->getBestsellerScoreBySale());

        //period 1 - 7 days and 2 orders
        $product = $this->productRepository->get('simple-400000');
        $this->assertEquals(5401, $product->getBestsellerScoreByAmount());
        $this->assertEquals(21600001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(601, $product->getBestsellerScoreBySale());

        //period 8 - 30 days
        $product = $this->productRepository->get('simple-600000');
        $this->assertEquals(3001, $product->getBestsellerScoreByAmount());
        $this->assertEquals(18000001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(201, $product->getBestsellerScoreBySale());

        //period 8 - 30 days and 2 orders
        $product = $this->productRepository->get('simple-1000000');
        $this->assertEquals(2401, $product->getBestsellerScoreByAmount());
        $this->assertEquals(24000001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(401, $product->getBestsellerScoreBySale());

        //period 31 - 365 days
        $product = $this->productRepository->get('simple-1200000');
        $this->assertEquals(901, $product->getBestsellerScoreByAmount());
        $this->assertEquals(10800001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(101, $product->getBestsellerScoreBySale());

        //period 31 - 365 days and 2 orders
        $product = $this->productRepository->get('simple-1400000');
        $this->assertEquals(801, $product->getBestsellerScoreByAmount());
        $this->assertEquals(11200001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(201, $product->getBestsellerScoreBySale());

        //period > 365 days
        $product = $this->productRepository->get('simple-1600000');
        $this->assertEquals(1, $product->getBestsellerScoreByAmount());
        $this->assertEquals(1, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(1, $product->getBestsellerScoreBySale());

        //period > 365 days and 2 orders
        $product = $this->productRepository->get('simple-1600000');
        $this->assertEquals(1, $product->getBestsellerScoreByAmount());
        $this->assertEquals(1, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(1, $product->getBestsellerScoreBySale());

        //Mixed periods
        $product = $this->productRepository->get('simple-1800000');
        $this->assertEquals(3603, $product->getBestsellerScoreByAmount());
        $this->assertEquals(64800003, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(1003, $product->getBestsellerScoreBySale());

        $product = $this->productRepository->get('simple-1900000');
        $this->assertEquals(4002, $product->getBestsellerScoreByAmount());
        $this->assertEquals(76000002, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(502, $product->getBestsellerScoreBySale());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadGroupedProducts
     * @magentoDataFixture loadGroupedOrders
     */
    public function testCalculationForGroupedProduct()
    {
        $scoreCalculationModel = $this->scoreCalculationModel;
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $scoreCalculationModel->recalculateScore();

        $indexerRegistry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Framework\Indexer\IndexerRegistry::class);
        $product = $this->productRepository->get('grouped');

        $bestsellerScoreByAmount = [6001, 5701, 5401, 4801];
        $bestsellerScoreByTurnover = [6000001, 11400001, 16200001, 24000001];
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
        $this->assertEquals(5101, $product->getBestsellerScoreByAmount());
        $this->assertEquals(46200001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(501, $product->getBestsellerScoreBySale());
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
}
