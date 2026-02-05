<?php

declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class BundleCalculationsTest extends AbstractCalculationsTestCase
{
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager;
    protected ?\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation $scoreCalculationModel;
    protected ?\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider $boostingFactorDataProvider;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->scoreCalculationModel = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\Model\ScoreCalculation::class);
        $this->boostingFactorDataProvider = $this->objectManager->get(\MageSuite\ProductBestsellersRanking\DataProviders\BoostingFactorDataProvider::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture MageSuite_ProductBestsellersRanking::Test/_files/orders_with_bundle_products.php
     */
    public function testItCalculatesProperlyBundleProductsScores(): void
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
}
