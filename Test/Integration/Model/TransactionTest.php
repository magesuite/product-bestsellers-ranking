<?php

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

class TransactionTest extends AbstractCalculationsTest
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
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadProducts
     * @magentoDataFixture loadOrders
     */
    public function testItDoesNotChangeExistingValueWhenUpdateQueriesFailed()
    {
        $product = $this->productRepository->get('simple-100000', false, 0, true);
        $product->setBestsellerScoreByAmount(1000);
        $product->setBestsellerScoreByTurnover(2000);
        $product->setBestsellerScoreBySale(3000);

        $this->productRepository->save($product);

        try {
            $this->scoreCalculationModel = $this->objectManager->create(
                \MageSuite\ProductBestsellersRanking\Model\ScoreCalculation::class,
                ['moveCalculationsToAttributes' => $this->objectManager->create(\MageSuite\ProductBestsellersRanking\Test\Fake\MoveCalculationsToAttributesFake::class)]
            );

            $this->scoreCalculationModel->setUseTransaction(true);
            $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
            $this->scoreCalculationModel->recalculateScore();
        } catch (\Exception $exception) {
            $this->assertEquals("Query failed", $exception->getMessage());
        }

        $product = $this->productRepository->get('simple-100000', false, 0, true);
        $this->assertEquals(1000, $product->getBestsellerScoreByAmount());
        $this->assertEquals(2000, $product->getBestsellerScoreByTurnover());
        $this->assertEquals(3000, $product->getBestsellerScoreBySale());
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
