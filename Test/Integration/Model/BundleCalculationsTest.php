<?php

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class BundleCalculationsTest extends AbstractCalculationsTest
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
     * @magentoDataFixture loadOrders
     */
    public function testItCalculatesProperlyBundleProductsScores()
    {
        $this->boostingFactorDataProvider->setBoostingFactors($this->getBoostingFactorArray());
        $this->scoreCalculationModel->recalculateScore();

        $scores = [];

        foreach (['bundle-product'] as $sku) {
            $product = $this->productRepository->get($sku);

            $scores[$sku] = [
                'bestseller_score_by_amount' => $product->getData('bestseller_score_by_amount'),
                'bestseller_score_by_turnover' => $product->getData('bestseller_score_by_turnover'),
                'bestseller_score_by_sale' => $product->getData('bestseller_score_by_sale'),
            ];
        }

        $this->assertEquals('3402', $scores['bundle-product']['bestseller_score_by_amount']);
        $this->assertEquals('4335002', $scores['bundle-product']['bestseller_score_by_turnover']);
        $this->assertEquals('502', $scores['bundle-product']['bestseller_score_by_sale']);
    }

    public static function loadOrders()
    {
        include __DIR__ . '/../../_files/orders_with_bundle_products.php';
    }
}
