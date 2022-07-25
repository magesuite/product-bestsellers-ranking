<?php

$productMapper = include __DIR__ . '/../_files/product_mapper.php';

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

$productLinkFactory = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\Catalog\Api\Data\ProductLinkInterfaceFactory::class);

foreach ($productMapper as $id => $data) {
    if (!in_array($id, [100000, 200000, 300000, 500000])) {
        continue;
    }
    /** @var $product \Magento\Catalog\Model\Product */
    $product = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Product::class);
    $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        ->setId($id)
        ->setWebsiteIds([1])
        ->setAttributeSetId(4)
        ->setName('Simple ' . 'Simple Product' . $id)
        ->setSku('simple-'. $id)
        ->setPrice($data['price'])
        ->setWeight(1)
        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
        ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
        ->setTierPrice(
            [
                [
                    'website_id' => 0,
                    'cust_group' => \Magento\Customer\Model\Group::CUST_GROUP_ALL,
                    'price_qty' => 2,
                    'price' => 8,
                ],
                [
                    'website_id' => 0,
                    'cust_group' => \Magento\Customer\Model\Group::CUST_GROUP_ALL,
                    'price_qty' => 5,
                    'price' => 5,
                ],
                [
                    'website_id' => 0,
                    'cust_group' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
                    'price_qty' => 3,
                    'price' => 5,
                ],
            ]
        )
        ->setStockData(
            [
                'use_config_manage_stock' => 1,
                'qty' => isset($data['qty']) ? $data['qty'] : 1000,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ]
        )->setCanSaveCustomOptions(false)
        ->setBestsellerScoreByAmount(0)
        ->setBestsellerScoreByTurnover(0)
        ->setBestsellerScoreMultiplier(isset($data['multiplier']) ? $data['multiplier'] : 100)
        ->setQty(1)
        ->setHasOptions(false);
    ;

    $linkedProducts[] = $productRepository->save($product);
}

/** @var $product \Magento\Catalog\Model\Product */
$groupedProduct = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Product::class);

$groupedProduct->setTypeId(\Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE)
    ->setId(13)
    ->setWebsiteIds([1])
    ->setAttributeSetId(4)
    ->setName('Grouped Product')
    ->setSku('grouped')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1, 'qty' => 100])
    ->setBestsellerScoreByAmount(0)
    ->setBestsellerScoreByTurnover(0)
    ->setBestsellerScoreMultiplier(100);

foreach ($linkedProducts as $linkedProduct) {
    /** @var \Magento\Catalog\Api\Data\ProductLinkInterface $productLink */
    $productLink = $productLinkFactory->create();
    $productLink->setSku($groupedProduct->getSku())
        ->setLinkType('associated')
        ->setLinkedProductSku($linkedProduct->getSku())
        ->setLinkedProductType($linkedProduct->getTypeId())
        ->getExtensionAttributes()
        ->setQty(1000);
    $newLinks[] = $productLink;
}

$groupedProduct->setProductLinks($newLinks);

$productRepository->save($groupedProduct);
