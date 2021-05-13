<?php
declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Plugin\Controller\Cart\Add;

class AddParentProductIdToRequestParams
{
    public function afterGetRequest(
        \Magento\Checkout\Controller\Cart\Add $subject,
        \Magento\Framework\App\RequestInterface $result
    ) {
        if (!$result->getParam('parent_product_id')) {
            $result->setParams(['parent_product_id' => $result->getParam('product')]);
        }

        return $result;
    }
}
