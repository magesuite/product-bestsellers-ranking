<?php

\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

$productMapper = include __DIR__ . '/../_files/product_mapper.php';

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

foreach ($productMapper as $id => $data) {
    $product = $objectManager->create(\Magento\Catalog\Model\Product::class);

    $product->load($id);
    if ($product->getId()) {
        $product->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
