<?php

namespace MageSuite\ProductBestsellersRanking\Service;

class ScoreManager
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\ScoreCalculationFactory
     */
    protected $scoreCalculationFactory;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\IndexerFactory
     */
    protected $indexerFactory;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \MageSuite\ProductBestsellersRanking\Model\ScoreCalculationFactory $scoreCalculationFactory,
        \MageSuite\ProductBestsellersRanking\Model\IndexerFactory $indexerFactory,
        \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->scoreCalculationFactory = $scoreCalculationFactory;
        $this->indexerFactory = $indexerFactory;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    public function recalculateScores($dryRun = false)
    {
        try {
            $scoreCalculator = $this->scoreCalculationFactory->create();
            $scoreCalculator->setUseTransaction($this->configuration->isUseTransactionsEnabled());

            if ($dryRun) {
                $scoreCalculator->setDryRun($dryRun);
            }

            $scoreCalculator->recalculateScore();

            $this->indexerFactory->create()->invalidate();
        } catch (\Exception | \Error $exception) {
            $this->logger->critical(sprintf(
                'Error during Bestseller score recalculation: %s',
                $exception->getMessage()
            ));
            throw $exception;
        }
    }
}
