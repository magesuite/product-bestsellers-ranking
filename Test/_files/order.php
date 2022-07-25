<?php

require __DIR__ . '/../_files/product_add.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$orderMapper = include __DIR__ . '/../_files/order_mapper.php';
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$orderCreator = $objectManager->create(\MageSuite\ProductBestsellersRanking\Test\Helper\OrderCreator::class);

foreach ($orderMapper as $incrementId => $orderData) {
    $requestData = [
        'product' => $orderData['product_id'],
        'qty' => $orderData['qty_ordered']
    ];

    $product = $productRepository->getById($orderData['product_id']);

    $orderCreator->createOrder($incrementId, $orderData, $product, $requestData);
}
