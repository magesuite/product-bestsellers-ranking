<?php

\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$categoryLinkManagement = $objectManager->create(\Magento\Catalog\Api\CategoryLinkManagementInterface::class);

$productMapper = include __DIR__ . '/../_files/product_mapper.php';

foreach ($productMapper as $id => $data) {
    /** @var $product \Magento\Catalog\Model\Product */
    $product = $objectManager->create('Magento\Catalog\Model\Product');
    $product->isObjectNew(true);
    $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        ->setId($id)
        ->setAttributeSetId(4)
        ->setWebsiteIds([1])
        ->setName('Simple Product' . $id)
        ->setSku('simple-'. $id)
        ->setPrice($data['price'])
        ->setWeight(1)
        ->setTaxClassId(0)
        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
        ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
        ->setStockData(
            [
                'use_config_manage_stock' => 1,
                'manage_stock' => 1,
                'qty' => isset($data['qty']) ? $data['qty'] : 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ]
        )->setCanSaveCustomOptions(false)
        ->setBestsellerScoreMultiplier(isset($data['multiplier']) ? $data['multiplier'] : 100)
        ->setQty(isset($data['qty']) ? $data['qty'] : 100)
        ->setHasOptions(false);

    $productRepository->save($product);
}
