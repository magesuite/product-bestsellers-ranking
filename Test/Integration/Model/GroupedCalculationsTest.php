<?php

declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class GroupedCalculationsTest extends AbstractCalculationsTestCase
{
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager;
    protected ?\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation $scoreCalculationModel;
    protected ?\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider $boostingFactorDataProvider;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->scoreCalculationModel = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation::class);
        $this->boostingFactorDataProvider = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture MageSuite_ProductBestsellersRanking::Test/_files/product_grouped_with_simple.php
     * @magentoDataFixture MageSuite_ProductBestsellersRanking::Test/_files/orders_with_grouped_product.php
     */
    public function testItCalculatesProperlyGroupedProductsScores(): void
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $product = $this->productRepository->get('grouped', false, 0, true);

        $bestsellerScoreByAmount = [6000, 5700, 5400, 4800];
        $bestsellerScoreByTurnover = [3000000, 2850000, 2700000, 2400000];
        $bestsellerScoreBySale = [300, 300, 300, 300];

        $this->assertEquals(array_sum($bestsellerScoreByAmount)+1, $product->getBestsellerScoreByAmount());
        $this->assertEquals(array_sum($bestsellerScoreByTurnover)+1, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(array_sum($bestsellerScoreBySale)+1, $product->getBestsellerScoreBySale());
    }
}
