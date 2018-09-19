<?php
namespace MageSuite\ProductBestsellersRanking\Console\Command;

class ScoreCalculation extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\ScoreCalculationFactory
     */
    protected $scoreCalculationFactory;

    /**
     * @var \Magento\Framework\Config\ScopeInterface $scope
     */
    protected $scope;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\Config\ScopeInterface $scope,
        \MageSuite\ProductBestsellersRanking\Model\ScoreCalculationFactory $scoreCalculationFactory
    )
    {
        parent::__construct();

        $this->state = $state;
        $this->scoreCalculationFactory = $scoreCalculationFactory;
        $this->scope = $scope;
    }

    protected function configure()
    {
        $this->setName('cs:bestsellers:recalculate');
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    )
    {
        if ($this->scope->getCurrentScope() !== 'frontend') {
            $this->state->setAreaCode('frontend');
        }

        $scoreCalculation = $this->scoreCalculationFactory->create();

        $scoreCalculation->recalculateScore();
    }
}