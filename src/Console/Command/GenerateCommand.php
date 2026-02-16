<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Console\Command;

use M2Boilerplate\CriticalCss\Config\Config;
use M2Boilerplate\CriticalCss\Logger\ConsoleLoggerFactory;
use M2Boilerplate\CriticalCss\Service\CriticalCss;
use M2Boilerplate\CriticalCss\Service\ProcessManager;
use M2Boilerplate\CriticalCss\Service\ProcessManagerFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * CLI Command to generate Critical CSS for configured stores.
 */
class GenerateCommand extends Command
{
    private const INPUT_OPTION_KEY_STORE_IDS = 'store-id';

    /**
     * @param Config $config
     * @param CriticalCss $criticalCssService
     * @param ConsoleLoggerFactory $consoleLoggerFactory
     * @param ProcessManagerFactory $processManagerFactory
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        protected Config $config,
        protected CriticalCss $criticalCssService,
        protected ConsoleLoggerFactory $consoleLoggerFactory,
        protected ProcessManagerFactory $processManagerFactory,
        protected State $state,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('m2bp:critical-css:generate');
        $this->setDescription('Generate critical CSS for the configured stores.');
        $this->addOption(
            self::INPUT_OPTION_KEY_STORE_IDS,
            null,
            InputOption::VALUE_REQUIRED,
            'Comma-separated list of Magento Store IDs to process (e.g., "1,2").'
        );
        
        parent::configure();
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Adminhtml area is required for many backend operations
            $this->state->setAreaCode(Area::AREA_ADMINHTML);

            // Pre-flight check for the critical binary
            $this->criticalCssService->test($this->config->getCriticalBinary());

            // Create a logger bound to this command's output
            $logger = $this->consoleLoggerFactory->create($output);

            /** @var ProcessManager $processManager */
            $processManager = $this->processManagerFactory->create(['logger' => $logger]);

            $this->displayConfiguration($output);

            $output->writeln('<info>Gathering URLs...</info>');
            $output->writeln('<info>-----------------------------------------</info>');

            $processes = $processManager->createProcesses(
                $this->getStoreIds($input)
            );

            $count = count($processes);
            $output->writeln('<info>-----------------------------------------</info>');
            $output->writeln(sprintf('<info>Generating Critical CSS for %d URLs...</info>', $count));
            $output->writeln('<info>-----------------------------------------</info>');

            if ($count > 0) {
                $processManager->executeProcesses($processes, true);
            } else {
                $output->writeln('<comment>No URLs found to process.</comment>');
            }

            $output->writeln('<info>Done.</info>');

        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            // In verbose mode, print the stack trace
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln($e->getTraceAsString());
            }
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Display current configuration to the console.
     *
     * @param OutputInterface $output
     * @return void
     */
    private function displayConfiguration(OutputInterface $output): void
    {
        $isEnabled = $this->config->isEnabled() ? 'Enabled' : 'Disabled';
        
        $output->writeln(sprintf("<info>'Use CSS critical path' config is %s</info>", $isEnabled));
        $output->writeln("<info>-----------------------------------------</info>");
        $output->writeln('<info>Critical Command Configured Options</info>');
        $output->writeln("<info>-----------------------------------------</info>");
        
        $output->writeln(sprintf(
            '<comment>Screen Dimensions: %s</comment>',
            implode(', ', $this->config->getDimensions())
        ));
        
        $output->writeln(sprintf(
            '<comment>Force Include Css Selectors: %s</comment>',
            implode(', ', $this->config->getForceIncludeCssSelectors())
        ));

        // Security: Mask password in output if present
        $username = $this->config->getUsername();
        $hasPassword = $this->config->getPassword() ? '******' : 'None';

        if ($username) {
            $output->writeln(sprintf('<comment>HTTP Auth Username: %s</comment>', $username));
            $output->writeln(sprintf('<comment>HTTP Auth Password: %s</comment>', $hasPassword));
        }

        $output->writeln("<info>-----------------------------------------</info>");
    }

    /**
     * Parse store IDs from input.
     *
     * @param InputInterface $input
     * @return int[]|null
     */
    private function getStoreIds(InputInterface $input): ?array
    {
        $ids = $input->getOption(self::INPUT_OPTION_KEY_STORE_IDS);
        
        if (empty($ids) || !is_string($ids)) {
            return null;
        }

        $idList = explode(',', $ids);
        $result = [];

        foreach ($idList as $id) {
            $val = trim($id);
            if (is_numeric($val)) {
                $result[] = (int)$val;
            }
        }

        return empty($result) ? null : $result;
    }
}
