<?php
declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Observer;

class AddParentProductIdFromBuyRequestToQuoteItem implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $parentProductId = $observer->getEvent()->getQuoteItem()->getBuyRequest()->getParentProductId();
        $quoteItem->setParentProductId($parentProductId);
    }
}
