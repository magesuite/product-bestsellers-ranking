<?php

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoDataFixture loadProducts
 * @magentoDataFixture loadOrders
 */
class SimpleCalculationsTest extends AbstractCalculationsTest
{
    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\ScoreCalculation
     */
    protected $scoreCalculationModel;

    /**
     * @var \MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider
     */
    protected $boostingFactorDataProvider;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\MoveCalculationsToAttributes|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $moveCalculationsToAttributeMock;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->scoreCalculationModel = $this->objectManager->create(\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation::class);
        $this->boostingFactorDataProvider = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider::class);
        $this->productRepository = $this->objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
    }

    /**
     * Batch size is set to 2 to ensure pagination of products works correctly
     * @magentoAdminConfigFixture bestsellers/performance/batch_size 2
     */
    public function testSimpleProductsCalculation()
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

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
     * @magentoAdminConfigFixture bestsellers/orders_period/period 1
     */
    public function testItOnlyTakesLastWeekOrdersIntoAccount()
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $product = $this->productRepository->get('simple-1800000');
        $this->assertEquals(2701, $product->getBestsellerScoreByAmount());
        $this->assertEquals(48600001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(601, $product->getBestsellerScoreBySale());
    }

    public function testCalculationIncludingMultiplier()
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $product = $this->productRepository->get('simple-3000000');
        $this->assertEquals(31, $product->getBestsellerScoreByAmount());
        $this->assertEquals(600001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(31, $product->getBestsellerScoreBySale());
    }

    /**
     * @magentoConfigFixture current_store bestsellers/sorting/direction desc
     */
    public function testCalculationWithDescendingOrder()
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $product = $this->productRepository->get('simple-1200000');
        $this->assertEquals(5101, $product->getBestsellerScoreByAmount());
        $this->assertEquals(65200002, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(903, $product->getBestsellerScoreBySale());
    }

    /**
     * @magentoConfigFixture current_store bestsellers/boosting_factors/boosting_factor_sold_out 0.5
     */
    public function testSoldOutProductCalculation()
    {
        $product = $this->productRepository->get('simple-4000000', true, 0, true);
        $product->setQty(0);
        $product->setStockData(['qty' => 0, 'is_in_stock' => 0]);
        $this->productRepository->save($product);

        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $product = $this->productRepository->get('simple-4000000', true, 0, true);

        $this->assertEquals(151, $product->getBestsellerScoreByAmount());
        $this->assertEquals(3000001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(151, $product->getBestsellerScoreBySale());
    }

    public static function loadProducts()
    {
        include __DIR__.'/../../_files/product_add.php';
    }

    public static function loadProductsRollback()
    {
        include __DIR__.'/../../_files/product_add_rollback.php';
    }

    public static function loadOrders()
    {
        include __DIR__.'/../../_files/order.php';
    }

    public static function loadOrdersRollback()
    {
        include __DIR__.'/../../_files/order_rollback.php';
    }
}
