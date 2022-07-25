<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$resolver = \Magento\TestFramework\Workaround\Override\Fixture\Resolver::getInstance();

$resolver->requireDataFixture('Magento/Bundle/_files/product.php');

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$orderCreator = $objectManager->create(\MageSuite\ProductBestsellersRanking\Test\Helper\OrderCreator::class);

$orders = [
    1000001 => [
        'bundle_product_sku' => 'bundle-product',
        'qty_ordered' => 10,
        'days_ago' => '1'
    ],
    1000002 => [
        'bundle_product_sku' => 'bundle-product',
        'qty_ordered' => 2,
        'days_ago' => '8'
    ],
];

foreach ($orders as $incrementId => $orderData) {
    $bundleProductSku = $orderData['bundle_product_sku'];
    $bundleProduct = $productRepository->get($bundleProductSku);

    $bundleOptions = $bundleProduct->getTypeInstance()->getOptions($bundleProduct);

    $requestData =  [
        'uenc' => 'aHR0cHM6Ly9tYWdlc3VpdGUubWUvZGUvc3ByaXRlLXlvZ2EtY29tcGFuaW9uLWtpdC5odG1s',
        'product' => $bundleProduct->getId(),
        'parent_product_id' => $bundleProduct->getId(),
        'selected_configurable_option' => '',
        'related_product' => '',
        'item' => $bundleProduct->getId(),
        'bundle_option' => [],
        'bundle_option_qty' => [],
        'qty' => $orderData['qty_ordered'],
    ];

    $optionCollection = $bundleProduct->getTypeInstance()->getOptionsCollection($bundleProduct);

    $bundleOptions = [];
    $bundleOptionsQty = [];
    $optionsData = [];

    foreach ($optionCollection as $option) {
        $selectionsCollection = $bundleProduct->getTypeInstance()->getSelectionsCollection([$option->getId()], $bundleProduct);
        if ($option->isMultiSelection()) {
            $optionsData[$option->getId()] = array_column($selectionsCollection->toArray(), 'product_id');
            $bundleOptions[$option->getId()] = array_column($selectionsCollection->toArray(), 'selection_id');
        } else {
            $bundleOptions[$option->getId()] = $selectionsCollection->getFirstItem()->getSelectionId();
            $optionsData[$option->getId()] = $selectionsCollection->getFirstItem()->getProductId();
        }
        $bundleOptionsQty[$option->getId()] = 1;
    }

    $requestData['bundle_option'] = $bundleOptions;
    $requestData['bundle_option_qty'] = $bundleOptionsQty;

    $orderCreator->createOrder($incrementId, $orderData, $bundleProduct, $requestData);
}
