<?php

declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoDataFixture MageSuite_ProductBestsellersRanking::Test/_files/product_add.php
 * @magentoDataFixture MageSuite_ProductBestsellersRanking::Test/_files/order.php
 */
class SimpleCalculationsTest extends AbstractCalculationsTestCase
{
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager;
    protected ?\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation $scoreCalculationModel;
    protected ?\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider $boostingFactorDataProvider;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    protected ?\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->scoreCalculationModel = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation::class);
        $this->boostingFactorDataProvider = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
    }

    /**
     * Batch size is set to 2 to ensure pagination of products works correctly
     * @magentoAdminConfigFixture bestsellers/performance/batch_size 2
     */
    public function testSimpleProductsCalculation(): void
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        //period 1 - 7 days
        $product = $this->productRepository->get('simple-100000', false, 0, true);
        $this->assertEquals(6001, $product->getBestsellerScoreByAmount());
        $this->assertEquals(6000001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(301, $product->getBestsellerScoreBySale());

        //period 1 - 7 days and 2 orders
        $product = $this->productRepository->get('simple-400000', false, 0, true);
        $this->assertEquals(5401, $product->getBestsellerScoreByAmount());
        $this->assertEquals(21600001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(601, $product->getBestsellerScoreBySale());

        //period 8 - 30 days
        $product = $this->productRepository->get('simple-600000', false, 0, true);
        $this->assertEquals(3001, $product->getBestsellerScoreByAmount());
        $this->assertEquals(18000001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(201, $product->getBestsellerScoreBySale());

        //period 8 - 30 days and 2 orders
        $product = $this->productRepository->get('simple-1000000', false, 0, true);
        $this->assertEquals(2401, $product->getBestsellerScoreByAmount());
        $this->assertEquals(24000001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(401, $product->getBestsellerScoreBySale());

        //period 31 - 365 days
        $product = $this->productRepository->get('simple-1200000', false, 0, true);
        $this->assertEquals(901, $product->getBestsellerScoreByAmount());
        $this->assertEquals(10800001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(101, $product->getBestsellerScoreBySale());

        //period 31 - 365 days and 2 orders
        $product = $this->productRepository->get('simple-1400000', false, 0, true);
        $this->assertEquals(801, $product->getBestsellerScoreByAmount());
        $this->assertEquals(11200001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(201, $product->getBestsellerScoreBySale());

        //period > 365 days
        $product = $this->productRepository->get('simple-1600000', false, 0, true);
        $this->assertEquals(1, $product->getBestsellerScoreByAmount());
        $this->assertEquals(1, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(1, $product->getBestsellerScoreBySale());

        //period > 365 days and 2 orders
        $product = $this->productRepository->get('simple-1600000', false, 0, true);
        $this->assertEquals(1, $product->getBestsellerScoreByAmount());
        $this->assertEquals(1, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(1, $product->getBestsellerScoreBySale());

        //Mixed periods
        $product = $this->productRepository->get('simple-1800000', false, 0, true);
        $this->assertEquals(3603, $product->getBestsellerScoreByAmount());
        $this->assertEquals(64800003, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(1003, $product->getBestsellerScoreBySale());

        $product = $this->productRepository->get('simple-1900000', false, 0, true);
        $this->assertEquals(4002, $product->getBestsellerScoreByAmount());
        $this->assertEquals(76000002, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(502, $product->getBestsellerScoreBySale());
    }

    /**
     * @magentoAdminConfigFixture bestsellers/orders_period/period 1
     */
    public function testItOnlyTakesLastWeekOrdersIntoAccount(): void
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $product = $this->productRepository->get('simple-1800000', false, 0, true);
        $this->assertEquals(2701, $product->getBestsellerScoreByAmount());
        $this->assertEquals(48600001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(601, $product->getBestsellerScoreBySale());
    }

    public function testCalculationIncludingMultiplier(): void
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $product = $this->productRepository->get('simple-3000000', false, 0, true);
        $this->assertEquals(31, $product->getBestsellerScoreByAmount());
        $this->assertEquals(600001, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(31, $product->getBestsellerScoreBySale());
    }

    /**
     * @magentoConfigFixture current_store bestsellers/sorting/direction desc
     */
    public function testCalculationWithDescendingOrder(): void
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $product = $this->productRepository->get('simple-1200000', false, 0, true);
        $this->assertEquals(5101, $product->getBestsellerScoreByAmount());
        $this->assertEquals(65200002, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(903, $product->getBestsellerScoreBySale());
    }

    /**
     * @magentoConfigFixture current_store bestsellers/boosting_factors/boosting_factor_sold_out 0.5
     */
    public function testSoldOutProductCalculation(): void
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
}
