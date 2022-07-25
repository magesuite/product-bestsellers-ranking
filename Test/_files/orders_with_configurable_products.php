<?php

require __DIR__ .'/product_configurable.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$orderCreator = $objectManager->create(\MageSuite\ProductBestsellersRanking\Test\Helper\OrderCreator::class);

$orders = [
    1000001 => [
        'configurable_product_sku' => 'configurable1',
        'product_sku' => 'simple_10',
        'qty_ordered' => 10,
        'days_ago' => '1'
    ],
    1000002 => [
        'configurable_product_sku' => 'configurable1',
        'product_sku' => 'simple_20',
        'qty_ordered' => 20,
        'days_ago' => '2'
    ],
    1000003 => [
        'configurable_product_sku' => 'configurable2',
        'product_sku' => 'simple_30',
        'qty_ordered' => 30,
        'days_ago' => '8'
    ],
    1000004 => [
        'configurable_product_sku' => 'configurable2',
        'product_sku' => 'simple_40',
        'qty_ordered' => 35,
        'days_ago' => '8'
    ],
    1000005 => [
        'configurable_product_sku' => 'configurable2',
        'product_sku' => 'simple_40',
        'qty_ordered' => 30,
        'days_ago' => '8'
    ],
    1000006 => [
        'configurable_product_sku' => 'configurable1',
        'product_sku' => 'simple_20',
        'qty_ordered' => 20,
        'days_ago' => '9'
    ],
];

foreach ($orders as $incrementId => $orderData) {
    $configurableProductSku = $orderData['configurable_product_sku'];
    $configurableProduct = $productRepository->get($configurableProductSku);

    $configurableOptions = $configurableProduct->getTypeInstance()->getConfigurableOptions($configurableProduct);
    $optionId = current(array_keys($configurableOptions));
    $configurableOptions = current($configurableOptions);

    foreach ($configurableOptions as $option) {
        if ($option['sku'] !== $orderData['product_sku']) {
            continue;
        }

        $requestData = [
            'product' => $option['product_id'],
            'parent_product_id' => $configurableProduct->getId(),
            'super_attribute' => [$optionId => $option['value_index']],
            'qty' => $orderData['qty_ordered']
        ];
    }

    $request = new \Magento\Framework\DataObject($requestData);

    $orderCreator->createOrder($incrementId, $orderData, $configurableProduct, $requestData);
}
