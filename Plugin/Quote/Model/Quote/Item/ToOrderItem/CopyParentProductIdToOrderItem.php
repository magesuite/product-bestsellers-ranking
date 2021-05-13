<?php
declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Plugin\Quote\Model\Quote\Item\ToOrderItem;

class CopyParentProductIdToOrderItem
{
    public function afterConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Magento\Sales\Api\Data\OrderItemInterface $result,
        $item,
        $data = []
    ) {
        $result->setParentProductId($item->getParentProductId());

        return $result;
    }
}
