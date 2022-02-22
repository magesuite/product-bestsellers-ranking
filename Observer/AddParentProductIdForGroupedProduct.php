<?php
declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Observer;

class AddParentProductIdForGroupedProduct implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $productType = $quoteItem->getBuyRequest()->getDataByPath('super_product_config/product_type');
        $parentProductId = (int)$quoteItem->getBuyRequest()->getDataByPath('super_product_config/product_id');

        if ($productType !== \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE || !$parentProductId) {
            return;
        }

        $quoteItem->setParentProductId($parentProductId);
    }
}
