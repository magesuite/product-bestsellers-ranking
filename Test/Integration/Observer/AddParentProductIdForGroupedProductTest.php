<?php
namespace MageSuite\ProductBestsellersRanking\Test\Integration\Observer;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class AddParentProductIdForGroupedProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Checkout\Model\CartFactory
     */
    protected $cartFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->cartFactory = $this->objectManager->get(\Magento\Checkout\Model\CartFactory::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->dataObjectFactory = $this->objectManager->get(\Magento\Framework\DataObjectFactory::class);
        $this->quoteManagement = $this->objectManager->get(\Magento\Quote\Model\QuoteManagement::class);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/GroupedProduct/_files/product_grouped.php
     */
    public function testIfGroupedProductHasParentId()
    {
        $grouped = $this->productRepository->get('grouped-product');
        $product = $this->productRepository->get('virtual-product');
        $superGroup = [
            $product->getId() => 1
        ];
        $requestInfo = $this->dataObjectFactory->create(
            [
                'data' => [
                    'product' => $grouped->getId(),
                    'super_group' => $superGroup,
                    'qty' => 1
                ]
            ]
        );
        $cart = $this->cartFactory->create();
        $cart->addProduct($grouped, $requestInfo);
        $quoteItem = $cart->getQuote()
            ->getItemsCollection()
            ->getFirstItem();

        $this->assertEquals($grouped->getId(), $quoteItem->getParentProductId());
    }
}
