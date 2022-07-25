<?php

const GROUPED_PRODUCT_ID = 13;

$orderMapper = include __DIR__ . '/../_files/order_mapper.php';
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$orderCreator = $objectManager->create(\MageSuite\ProductBestsellersRanking\Test\Helper\OrderCreator::class);

foreach ($orderMapper as $incrementId => $orderData) {
    if (!in_array($orderData['product_id'], [100000, 200000, 300000, 500000])) {
        continue;
    }

    $groupedProduct = $productRepository->getById(GROUPED_PRODUCT_ID);

    $associatedProducts = $groupedProduct->getTypeInstance()->getAssociatedProducts($groupedProduct);

    $superGroup = [];

    foreach ($associatedProducts as $associatedProduct) {
        $superGroup[$associatedProduct->getId()] = 0;
    }

    $superGroup[$orderData['product_id']] = $orderData['qty_ordered'];

    $requestData = [
        'product' => GROUPED_PRODUCT_ID,
        'parent_product_id' => GROUPED_PRODUCT_ID,
        'selected_configurable_option' => '',
        'related_product' => '',
        'item' => GROUPED_PRODUCT_ID,
        'super_group' => $superGroup,
        'qty' => 1
    ];

    $request = new \Magento\Framework\DataObject($requestData);

    $orderCreator->createOrder($incrementId, $orderData, $groupedProduct, $requestData);
}
