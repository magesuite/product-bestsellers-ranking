<?php

require __DIR__ . '/../_files/order_mapper.php';
$addressData = include __DIR__ . '/address_data.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

foreach ($orderMapper as $incrementId => $orderData) {
    if (!in_array($orderData['product_id'], [100000, 400000, 600000, 1200000])) {
        continue;
    }
    $billingAddress = $objectManager->create(\Magento\Sales\Model\Order\Address::class, ['data' => $addressData]);
    $billingAddress->setAddressType('billing');

    $shippingAddress = clone $billingAddress;
    $shippingAddress->setId(null)->setAddressType('shipping');

    $payment = $objectManager->create(\Magento\Sales\Model\Order\Payment::class);
    $payment->setMethod('checkmo')
        ->setAdditionalInformation('last_trans_id', '11122')
        ->setAdditionalInformation(
            'metadata',
            [
                'type' => 'free',
                'fraudulent' => false,
            ]
        );
    $orderItem = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
    $orderItem->setProductId($orderData['product_id'])
        ->setQtyOrdered($orderData['qty_ordered'])
        ->setBasePrice($orderData['product_price'])
        ->setPrice($orderData['product_price'])
        ->setRowTotal($orderData['product_price'])
        ->setProductType('grouped')
        ->setName('Simple '.$orderData['product_id'])
        ->setSku('simple-'.$orderData['product_id'])
        ->setParentProductId(13)
        ->setStoreId(1);

    $order = $objectManager->create(\Magento\Sales\Model\Order::class);
    $order->setIncrementId($incrementId*55)
        ->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
        ->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING))
        ->setSubtotal($orderData['product_price'] * $orderData['qty_ordered'])
        ->setGrandTotal($orderData['product_price'] * $orderData['qty_ordered'])
        ->setBaseSubtotal($orderData['product_price'] * $orderData['qty_ordered'])
        ->setBaseGrandTotal($orderData['product_price'] * $orderData['qty_ordered'])
        ->setCustomerIsGuest(true)
        ->setCustomerEmail('customer@null.com')
        ->setBillingAddress($billingAddress)
        ->setShippingAddress($shippingAddress)
        ->setStoreId($objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore()->getId())
        ->addItem($orderItem)
        ->setPayment($payment);
    $orderRepository = $objectManager->create(\Magento\Sales\Api\OrderRepositoryInterface::class);
    $orderRepository->save($order);
}
