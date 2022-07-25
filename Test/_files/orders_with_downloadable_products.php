<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$resolver = \Magento\TestFramework\Workaround\Override\Fixture\Resolver::getInstance();

require 'product_downloadable.php';

$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$orderCreator = $objectManager->create(\MageSuite\ProductBestsellersRanking\Test\Helper\OrderCreator::class);

$orders = [
    1000001 => [
        'downloadable_product_sku' => 'downloadable-product',
        'qty_ordered' => 10,
        'days_ago' => '1'
    ],
    1000002 => [
        'downloadable_product_sku' => 'downloadable-product',
        'qty_ordered' => 2,
        'days_ago' => '8'
    ],
];

foreach ($orders as $incrementId => $orderData) {
    $downloadableProductSku = $orderData['downloadable_product_sku'];
    $downloadableProduct = $productRepository->get($downloadableProductSku);

    $requestData =[
        'links' => array_keys($downloadableProduct->getDownloadableLinks()),
        'qty' => $orderData['qty_ordered']
    ];

    $request = new \Magento\Framework\DataObject($requestData);

    $orderCreator->createOrder($incrementId, $orderData, $downloadableProduct, $requestData);

}
