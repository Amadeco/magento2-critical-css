<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Service;

use M2Boilerplate\CriticalCss\Config\Config;
use M2Boilerplate\CriticalCss\Model\ProcessContext;
use M2Boilerplate\CriticalCss\Model\ProcessContextFactory;
use M2Boilerplate\CriticalCss\Provider\Container;
use M2Boilerplate\CriticalCss\Provider\ProviderInterface;
use Magento\Framework\App\Area;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Manages the creation and execution of Critical CSS generation processes.
 */
class ProcessManager
{
    /**
     * @param LoggerInterface $logger
     * @param Storage $storage
     * @param ProcessContextFactory $contextFactory
     * @param Config $config
     * @param CriticalCss $criticalCssService
     * @param Emulation $emulation
     * @param StoreManagerInterface $storeManager
     * @param Container $container
     * @param CssProcessor $cssProcessor
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected Storage $storage,
        protected ProcessContextFactory $contextFactory,
        protected Config $config,
        protected CriticalCss $criticalCssService,
        protected Emulation $emulation,
        protected StoreManagerInterface $storeManager,
        protected Container $container,
        protected CssProcessor $cssProcessor
    ) {
    }

    /**
     * Execute a list of processes in parallel batches.
     *
     * @param ProcessContext[] $processList
     * @param bool $deleteOldFiles
     * @return void
     */
    public function executeProcesses(array $processList, bool $deleteOldFiles = false): void
    {
        if ($deleteOldFiles) {
            $this->storage->clean();
        }

        $batchSize = $this->config->getNumberOfParallelProcesses();
        /** @var ProcessContext[] $runningBatch */
        $runningBatch = [];

        // Fill initial batch
        while (count($runningBatch) < $batchSize && count($processList) > 0) {
            $runningBatch[] = array_shift($processList);
        }

        // Start initial batch
        foreach ($runningBatch as $context) {
            $this->startProcess($context);
        }

        // Loop until all processes (both in queue and running) are finished
        while (count($runningBatch) > 0) {
            foreach ($runningBatch as $key => $context) {
                if (!$context->getProcess()->isRunning()) {
                    // Handle finished process
                    try {
                        $this->handleEndedProcess($context);
                    } catch (ProcessFailedException $e) {
                        $this->logger->error((string)$e);
                    }

                    // Remove finished process from batch
                    unset($runningBatch[$key]);

                    // If there are more processes in the queue, start the next one
                    if (count($processList) > 0) {
                        $nextContext = array_shift($processList);
                        $this->startProcess($nextContext);
                        $runningBatch[] = $nextContext;
                    }
                }
            }

            // Prevent CPU thrashing
            if (count($runningBatch) > 0) {
                usleep(500000); // 0.5 seconds
            }
        }
    }

    /**
     * Create process contexts for all active stores or specific store IDs.
     *
     * @param int[]|null $storeIds
     * @return ProcessContext[]
     */
    public function createProcesses(?array $storeIds = null): array
    {
        $processList = [];
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $storeId = (int)$store->getId();

            // Filter by requested Store IDs if provided
            if ($storeIds !== null && !in_array($storeId, $storeIds, true)) {
                continue;
            }

            if (!$store->getIsActive()) {
                continue;
            }

            // Emulate frontend environment to generate correct URLs
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            $this->storeManager->setCurrentStore($storeId);

            try {
                foreach ($this->container->getProviders() as $provider) {
                    $processList = array_merge(
                        $processList,
                        $this->createProcessesForProvider($provider, $store)
                    );
                }
            } finally {
                $this->emulation->stopEnvironmentEmulation();
            }
        }

        return $processList;
    }

    /**
     * Create processes for a specific provider and store.
     *
     * @param ProviderInterface $provider
     * @param StoreInterface $store
     * @return ProcessContext[]
     */
    public function createProcessesForProvider(ProviderInterface $provider, StoreInterface $store): array
    {
        $processList = [];
        $urls = $provider->getUrls($store);

        foreach ($urls as $identifier => $url) {
            // Cache Busting: Add timestamp to force Varnish/FPC bypass during generation
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . 'm2bp_t=' . time();

            $this->logger->info(sprintf(
                '[%s:%s|%s] - %s',
                $store->getCode(),
                $provider->getName(),
                $identifier,
                $url
            ));

            $process = $this->criticalCssService->createCriticalCssProcess(
                $url,
                $this->config->getDimensions(),
                $this->config->getForceIncludeCssSelectors(),
                $this->config->getCriticalBinary(),
                $this->config->getUsername(),
                $this->config->getPassword()
            );

            /** @var ProcessContext $context */
            $context = $this->contextFactory->create([
                'process' => $process,
                'store' => $store,
                'provider' => $provider,
                'identifier' => $identifier
            ]);

            $processList[] = $context;
        }

        return $processList;
    }

    /**
     * Start a process and log the sanitized command.
     *
     * @param ProcessContext $context
     * @return void
     */
    protected function startProcess(ProcessContext $context): void
    {
        $process = $context->getProcess();
        $process->start();

        $commandLine = $this->sanitizeCommand($process->getCommandLine());

        $this->logger->debug(sprintf(
            '[%s|%s] > %s',
            $context->getProvider()->getName(),
            $context->getOrigIdentifier(),
            $commandLine
        ));
    }

    /**
     * Handle the result of a finished process.
     *
     * @param ProcessContext $context
     * @return void
     * @throws ProcessFailedException
     */
    protected function handleEndedProcess(ProcessContext $context): void
    {
        $process = $context->getProcess();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $rawCss = $process->getOutput();
        $processedCss = $this->cssProcessor->process($context->getStore(), $rawCss);
        
        $this->storage->saveCriticalCss($context->getIdentifier(), $processedCss);
        
        $size = $this->storage->getFileSize($context->getIdentifier()) ?? '?';

        $this->logger->info(sprintf(
            '[%s:%s|%s] Finished: %s.css (%s bytes)',
            $context->getStore()->getCode(),
            $context->getProvider()->getName(),
            $context->getOrigIdentifier(),
            $context->getIdentifier(),
            $size
        ));
    }

    /**
     * Sanitize sensitive information (passwords) from the command line string.
     *
     * @param string $command
     * @return string
     */
    private function sanitizeCommand(string $command): string
    {
        // Regex to match --pass followed by a quoted or unquoted string
        // Matches: --pass 'secret' OR --pass "secret" OR --pass secret
        return preg_replace(
            '/(--pass\s+)([\'"]?)(.+?)(\2)(\s|$)/',
            '$1$2******$2$5',
            $command
        );
    }
}
