<?php
declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Model\Product\Indexer\Fulltext\Datasource;

class BestsellerScoreData implements \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface
{
    const BESTSELLER_SCORE_ATTRIBUTES = [
        'bestseller_score_by_amount',
        'bestseller_score_by_turnover',
        'bestseller_score_by_sale'
    ];

    protected array $compositeProductTypes = [
        \Magento\Bundle\Model\Product\Type::TYPE_CODE,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
        \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE,
    ];

    public function addData($storeId, array $indexData): array
    {
        foreach ($indexData as &$productData) {
            if (!in_array($productData['type_id'], $this->compositeProductTypes)) {
                continue;
            }

            foreach (self::BESTSELLER_SCORE_ATTRIBUTES as $attributeCode) {
                if (!isset($productData[$attributeCode]) || !is_array($productData[$attributeCode])) {
                    continue;
                }

                $attributeValue = array_shift($productData[$attributeCode]);
                $productData[$attributeCode] = $attributeValue;
            }
        }

        return $indexData;
    }
}
