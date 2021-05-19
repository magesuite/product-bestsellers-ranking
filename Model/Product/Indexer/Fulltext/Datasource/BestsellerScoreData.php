<?php

namespace MageSuite\ProductBestsellersRanking\Model\Product\Indexer\Fulltext\Datasource;

class BestsellerScoreData implements \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface
{
    const BESTSELLER_SCORE_ATTRIBUTES = [
        'bestseller_score_by_amount',
        'bestseller_score_by_turnover',
        'bestseller_score_by_sale'
    ];

    public function addData($storeId, array $indexData) : array
    {
        foreach ($indexData as &$productData) {
            if ($productData['type_id'] != \MageSuite\Frontend\Model\Product\Type\Configurable::TYPE_CODE) {
                continue;
            }

            foreach (self::BESTSELLER_SCORE_ATTRIBUTES as $bestsellerScoreAttributeCode) {
                if (!is_array($productData[$bestsellerScoreAttributeCode])) {
                    continue;
                }

                $configurableProductAttributeValue = array_shift($productData[$bestsellerScoreAttributeCode]);
                $productData[$bestsellerScoreAttributeCode] = $configurableProductAttributeValue;
            }
        }

        return $indexData;
    }
}
