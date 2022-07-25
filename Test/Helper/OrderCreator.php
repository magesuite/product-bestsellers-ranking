<?php

namespace MageSuite\ProductBestsellersRanking\Test\Helper;

class OrderCreator
{
    protected \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
    protected \Magento\Quote\Model\Quote\AddressFactory $addressFactory;
    protected \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory;
    protected \Magento\Quote\Model\QuoteFactory $quoteFactory;
    protected \Magento\Quote\Model\QuoteManagement $quoteManagement;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Quote\Model\Quote\AddressFactory $addressFactory,
        \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    ) {
        $this->orderRepository = $orderRepository;
        $this->addressFactory = $addressFactory;
        $this->paymentFactory = $paymentFactory;
        $this->quoteFactory = $quoteFactory;
        $this->quoteManagement = $quoteManagement;
    }

    public function createOrder($incrementId, $orderData, $product, $requestData) // phpcs:ignore
    {
        $addressData = include BP . '/dev/tests/integration/testsuite/Magento/Sales/_files/address_data.php';

        $billingAddress = $this->addressFactory->create(['data' => $addressData]);
        $billingAddress->setAddressType('billing');

        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)
            ->setAddressType('shipping')
            ->setShippingMethod('flatrate_flatrate');

        $payment = $this->paymentFactory->create();
        $payment->setMethod('checkmo');

        $quote = $this->quoteFactory->create();

        $request = new \Magento\Framework\DataObject($requestData);

        $quote->addProduct($product, $request);

        $quote->setReservedOrderId($incrementId)
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setCustomerIsGuest(true)
            ->setCustomerEmail('customer@example.com')
            ->setPayment($payment);

        $quote->getShippingAddress()
            ->setCollectShippingRates(true)
            ->collectShippingRates();
        $quote->save();

        $orderId = $this->quoteManagement->placeOrder($quote->getId());

        $order = $this->orderRepository->get($orderId);

        $date = new \DateTime();
        $date->sub(new \DateInterval('P' . $orderData['days_ago'] . 'D'));

        $order->setCreatedAt($date);

        foreach ($order->getItems() as $item) {
            $item->setCreatedAt($date);
        }

        $this->orderRepository->save($order);
    }
}
