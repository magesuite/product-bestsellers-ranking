<?php

namespace MageSuite\ProductBestsellersRanking\Test\Fake;

class MoveCalculationsToAttributesFake extends \MageSuite\ProductBestsellersRanking\Model\ResourceModel\MoveCalculationsToAttributes
{
    public function execute()
    {
        parent::execute();
        throw new \Exception("Query failed");
    }
}
