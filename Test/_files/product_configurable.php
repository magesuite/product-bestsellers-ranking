<?php

$objectManager = \Magento\TestFramework\ObjectManager::getInstance();

$resolver = \Magento\TestFramework\Workaround\Override\Fixture\Resolver::getInstance();

$resolver->requireDataFixture('Magento/Catalog/_files/product_varchar_attribute.php');
$resolver->requireDataFixture('Magento/ConfigurableProduct/_files/configurable_attribute.php');

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$categoryLinkManagement = $objectManager->create(\Magento\Catalog\Api\CategoryLinkManagementInterface::class);

/** @var $installer \Magento\Catalog\Setup\CategorySetup */
$installer = $objectManager->create(\Magento\Catalog\Setup\CategorySetup::class);
$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
$attribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_configurable');

$configurableProductsSkus = ['configurable1' => 1, 'configurable2' => 2];

$currentSimpleId = 0;

foreach ($configurableProductsSkus as $configurableProductSku => $configurableProductId) {
    $options = $attribute->getOptions();

    $attributeValues = [];
    $attributeSetId = $installer->getAttributeSetId(\Magento\Catalog\Model\Product::ENTITY, 'Default');
    $associatedProductIds = [];
    $idsToReindex = $productIds = [$currentSimpleId+10, $currentSimpleId+20];
    array_shift($options); //remove the first option which is empty

    foreach ($options as $option) {
        /** @var $product \Magento\Catalog\Model\Product */
        $product = $objectManager->create(\Magento\Catalog\Model\Product::class);
        $productId = array_shift($productIds);
        $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
            ->setId($productId)
            ->setAttributeSetId($attributeSetId)
            ->setWebsiteIds([1])
            ->setName('Configurable Option ' . $productId . ' ' . $option->getLabel())
            ->setSku('simple_' . $productId)
            ->setPrice($productId)
            ->setTestConfigurable($option->getValue())
            ->setVarcharAttribute('varchar' . $productId)
            ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)
            ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);

        $product = $productRepository->save($product);

        /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
        $stockItem = $objectManager->create(\Magento\CatalogInventory\Model\Stock\Item::class);
        $stockItem->load($productId, 'product_id');

        if (!$stockItem->getProductId()) {
            $stockItem->setProductId($productId);
        }
        $stockItem->setUseConfigManageStock(1);
        $stockItem->setQty(1000);
        $stockItem->setIsQtyDecimal(0);
        $stockItem->setIsInStock(1);
        $stockItem->save();

        $attributeValues[] = [
            'label' => 'test',
            'attribute_id' => $attribute->getId(),
            'value_index' => $option->getValue(),
        ];
        $associatedProductIds[] = $product->getId();
    }

    /** @var $product \Magento\Catalog\Model\Product */
    $configurableProduct = $objectManager->create(\Magento\Catalog\Model\Product::class);

    /** @var \Magento\ConfigurableProduct\Helper\Product\Options\Factory $optionsFactory */
    $optionsFactory = $objectManager->create(\Magento\ConfigurableProduct\Helper\Product\Options\Factory::class);

    $configurableAttributesData = [
        [
            'attribute_id' => $attribute->getId(),
            'code' => $attribute->getAttributeCode(),
            'label' => $attribute->getStoreLabel(),
            'position' => '0',
            'values' => $attributeValues,
        ],
    ];

    $configurableOptions = $optionsFactory->create($configurableAttributesData);

    $extensionConfigurableAttributes = $configurableProduct->getExtensionAttributes();
    $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
    $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);

    $configurableProduct->setExtensionAttributes($extensionConfigurableAttributes);

    /** @var \Magento\Framework\Registry $registry */
    $registry = $objectManager->get(\Magento\Framework\Registry::class);
    $registry->unregister('isSecureArea');
    $registry->register('isSecureArea', true);
    try {
        $productToDelete = $productRepository->getById($configurableProductId);
        $productRepository->delete($productToDelete);

        /** @var \Magento\Quote\Model\ResourceModel\Quote\Item $itemResource */
        $itemResource = $objectManager->get(\Magento\Quote\Model\ResourceModel\Quote\Item::class);
        $itemResource->getConnection()->delete(
            $itemResource->getMainTable(),
            'product_id = ' . $productToDelete->getId()
        );

        $objectManager->get(\Magento\Framework\Indexer\IndexerRegistry::class)
            ->get(\Magento\CatalogInventory\Model\Indexer\Stock\Processor::INDEXER_ID)
            ->reindexAll();
    } catch (\Exception $e) {
        // Nothing to remove
    }
    $registry->unregister('isSecureArea');
    $registry->register('isSecureArea', false);

    $configurableProduct->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
        ->setId($configurableProductId)
        ->setAttributeSetId($attributeSetId)
        ->setWebsiteIds([1])
        ->setName('Configurable Product '.$configurableProductId)
        ->setSku($configurableProductSku)
        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
        ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);

    $productRepository->save($configurableProduct);

    /** @var \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */

    $categoryLinkManagement->assignProductToCategories(
        $configurableProduct->getSku(),
        [2]
    );

    $currentSimpleId = $currentSimpleId+20;
}
