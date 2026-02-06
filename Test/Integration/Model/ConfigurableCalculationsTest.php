<?php

declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ConfigurableCalculationsTest extends AbstractCalculationsTestCase
{
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager;
    protected ?\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation $scoreCalculationModel;
    protected ?\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider $boostingFactorDataProvider;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    protected ?\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        
        $this->scoreCalculationModel = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation::class);
        $this->boostingFactorDataProvider = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store carriers/flatrate/active 1
     * @magentoDataFixture MageSuite_ProductBestsellersRanking::Test/_files/orders_with_configurable_products.php
     */
    public function testItCalculatesProperlyConfigurableProductsScores(): void
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $scores = [];

        foreach (['configurable1', 'configurable2'] as $sku) {
            $product = $this->productRepository->get($sku, false, 0, true);

            $scores[$sku] = [
                'bestseller_score_by_amount' => $product->getData('bestseller_score_by_amount'),
                'bestseller_score_by_turnover' => $product->getData('bestseller_score_by_turnover'),
                'bestseller_score_by_sale' => $product->getData('bestseller_score_by_sale'),
            ];
        }

        $this->assertEquals('13002', $scores['configurable1']['bestseller_score_by_amount']);
        $this->assertEquals('23000002', $scores['configurable1']['bestseller_score_by_turnover']);
        $this->assertEquals('802', $scores['configurable1']['bestseller_score_by_sale']);

        $this->assertEquals('19001', $scores['configurable2']['bestseller_score_by_amount']);
        $this->assertEquals('70000001', $scores['configurable2']['bestseller_score_by_turnover']);
        $this->assertEquals('601', $scores['configurable2']['bestseller_score_by_sale']);
    }
}
