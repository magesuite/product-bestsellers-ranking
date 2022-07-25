<?php

namespace MageSuite\ProductBestsellersRanking\Model\Config\Source;

class SortingDirection
{
    public const DIRECTION_ASC = 'asc';
    public const DIRECTION_DESC = 'desc';

    public function toOptionArray()
    {
        return [
            ['value' => self::DIRECTION_ASC, 'label' => __('Ascending')],
            ['value' => self::DIRECTION_DESC, 'label' => __('Descending')],
        ];
    }
}
