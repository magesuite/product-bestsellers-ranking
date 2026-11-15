<?php

declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

class StaleScoresTest extends AbstractCalculationsTestCase
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
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture MageSuite_ProductBestsellersRanking::Test/_files/product_add.php
     * @magentoDataFixture MageSuite_ProductBestsellersRanking::Test/_files/order.php
     */
    public function testItRemovesScoresOfProductsWithoutOrdersInPeriod(): void
    {
        $productWithoutOrders = $this->productRepository->get('simple-2000000', false, 0, true);
        $productWithoutOrders->setBestsellerScoreByAmount(1000);
        $productWithoutOrders->setBestsellerScoreByTurnover(2000);
        $productWithoutOrders->setBestsellerScoreBySale(3000);
        $this->productRepository->save($productWithoutOrders);

        $storeViewProduct = $this->productRepository->get('simple-2000000', true, 1, true);
        $storeViewProduct->setBestsellerScoreByAmount(4000);
        $this->productRepository->save($storeViewProduct);

        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $productWithoutOrders = $this->productRepository->get('simple-2000000', false, 0, true);
        $this->assertNull($productWithoutOrders->getBestsellerScoreByAmount());
        $this->assertNull($productWithoutOrders->getBestsellerScoreByTurnover());
        $this->assertNull($productWithoutOrders->getBestsellerScoreBySale());
        $this->assertEquals(100, $productWithoutOrders->getBestsellerScoreMultiplier());

        $storeViewProduct = $this->productRepository->get('simple-2000000', false, 1, true);
        $this->assertEquals(4000, $storeViewProduct->getBestsellerScoreByAmount());

        $productWithOrders = $this->productRepository->get('simple-100000', false, 0, true);
        $this->assertEquals(6001, $productWithOrders->getBestsellerScoreByAmount());
        $this->assertEquals(6000001, $productWithOrders->getBestsellerScoreByTurnover());
        $this->assertEquals(301, $productWithOrders->getBestsellerScoreBySale());
    }
}
