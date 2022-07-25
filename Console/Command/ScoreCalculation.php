<?php
namespace MageSuite\ProductBestsellersRanking\Console\Command;

class ScoreCalculation extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Magento\Framework\Config\ScopeInterface $scope
     */
    protected $scope;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Service\ScoreManager
     */
    protected $scoreManager;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\Config\ScopeInterface $scope,
        \MageSuite\ProductBestsellersRanking\Service\ScoreManager $scoreManager
    ) {
        parent::__construct();

        $this->state = $state;
        $this->scope = $scope;
        $this->scoreManager = $scoreManager;
    }

    protected function configure()
    {
        $this->setName('cs:bestsellers:recalculate')
            ->addOption('dry-run', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Calculated values will not be migrated to attributes', null);
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        if ($this->scope->getCurrentScope() !== 'frontend') {
            $this->state->setAreaCode('frontend');
        }

        $dryRun = $input->getOption('dry-run') ? (bool)$input->getOption('dry-run') :  false;

        $this->scoreManager->recalculateScores($dryRun);
    }
}
