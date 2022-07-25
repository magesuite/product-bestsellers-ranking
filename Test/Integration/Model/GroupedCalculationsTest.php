<?php

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class GroupedCalculationsTest extends AbstractCalculationsTest
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
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadGroupedProducts
     * @magentoDataFixture loadGroupedOrders
     */
    public function testItCalculatesProperlyGroupedProductsScores()
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $product = $this->productRepository->get('grouped');

        $bestsellerScoreByAmount = [6000, 5700, 5400, 4800];
        $bestsellerScoreByTurnover = [3000000, 2850000, 2700000, 2400000];
        $bestsellerScoreBySale = [300, 300, 300, 300];

        $this->assertEquals(array_sum($bestsellerScoreByAmount)+1, $product->getBestsellerScoreByAmount());
        $this->assertEquals(array_sum($bestsellerScoreByTurnover)+1, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(array_sum($bestsellerScoreBySale)+1, $product->getBestsellerScoreBySale());
    }
    public static function loadGroupedProducts()
    {
        include __DIR__.'/../../_files/product_grouped_with_simple.php';
    }

    public static function loadGroupedOrders()
    {
        include __DIR__.'/../../_files/orders_with_grouped_product.php';
    }
}
