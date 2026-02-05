<?php

declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

class TransactionTest extends AbstractCalculationsTestCase
{
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager;
    protected ?\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation $scoreCalculationModel;
    protected ?\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider $boostingFactorDataProvider;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    protected ?\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    protected ?\PHPUnit\Framework\MockObject\MockObject $moveCalculationsToAttributeMock;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->scoreCalculationModel = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation::class);
        $this->boostingFactorDataProvider = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture MageSuite_ProductBestsellersRanking::Test/_files/product_add.php
     * @magentoDataFixture MageSuite_ProductBestsellersRanking::Test/_files/order.php
     */
    public function testItDoesNotChangeExistingValueWhenUpdateQueriesFailed(): void
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
}
