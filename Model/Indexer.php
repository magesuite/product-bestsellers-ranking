<?php

namespace MageSuite\ProductBestsellersRanking\Model;

class Indexer
{
    /**
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $indexerFactory;

    public function __construct(\Magento\Indexer\Model\IndexerFactory $indexerFactory)
    {
        $this->indexerFactory = $indexerFactory;
    }

    public function invalidate()
    {
        $indexerIds = ['catalogsearch_fulltext'];

        foreach ($indexerIds as $indexerId) {
            $indexer = $this->indexerFactory->create();
            $indexer->load($indexerId);

            if($indexer->isScheduled()) {
                continue;
            }

            $indexer->invalidate();
        }
    }

}
