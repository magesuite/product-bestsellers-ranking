<?php

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ConfigurableCalculationsTest extends AbstractCalculationsTest
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

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->scoreCalculationModel = $this->objectManager->create(\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation::class);
        $this->boostingFactorDataProvider = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider::class);
        $this->productRepository = $this->objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store carriers/flatrate/active 1
     * @magentoDataFixture loadOrders
     */
    public function testItCalculatesProperlyConfigurableProductsScores()
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $scores = [];

        foreach (['configurable1', 'configurable2'] as $sku) {
            $product = $this->productRepository->get($sku);

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

    public static function loadOrders()
    {
        include __DIR__ . '/../../_files/orders_with_configurable_products.php';
    }
}
