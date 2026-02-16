<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Service;

use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessFactory;

/**
 * Service responsible for creating and validating the Critical CSS process.
 */
class CriticalCss
{
    /**
     * @param ProcessFactory $processFactory
     */
    public function __construct(
        protected ProcessFactory $processFactory
    ) {
    }

    /**
     * Create a configured Process instance for Critical CSS generation.
     *
     * @param string $url
     * @param array<string> $dimensions
     * @param array<string> $forceIncludeCssSelectors
     * @param string $criticalBinary
     * @param string|null $username
     * @param string|null $password
     * @return Process
     */
    public function createCriticalCssProcess(
        string $url,
        array $dimensions,
        array $forceIncludeCssSelectors,
        string $criticalBinary = 'critical',
        ?string $username = null,
        ?string $password = null
    ): Process {
        $command = [
            $criticalBinary,
            $url
        ];

        foreach ($forceIncludeCssSelectors as $selector) {
            $command[] = '--penthouse-forceInclude';
            $command[] = $selector;
        }

        foreach ($dimensions as $dimension) {
            $command[] = '--dimensions';
            $command[] = $dimension;
        }

        // Legacy Authentication Logic
        // Kept for backward compatibility with the critical binary signature.
        if ($username !== null && $password !== null && $username !== '' && $password !== '') {
            $command[] = '--user';
            $command[] = $username;
            $command[] = '--pass';
            $command[] = $password;
        }

        $command[] = '--strict';
        $command[] = '--minify';
        $command[] = '--no-request-https.rejectUnauthorized';

        $command[] = '--ignore-atrule';
        $command[] = '@font-face';
        $command[] = '--ignore-atrule';
        $command[] = 'print';

        /** @var Process $process */
        $process = $this->processFactory->create(['command' => $command]);

        return $process;
    }

    /**
     * Get the version of the installed critical binary.
     *
     * @param string $criticalBinary
     * @return string
     * @throws RuntimeException If the process fails.
     */
    public function getVersion(string $criticalBinary = 'critical'): string
    {
        $command = [$criticalBinary, '--version'];
        
        /** @var Process $process */
        $process = $this->processFactory->create(['command' => $command]);
        
        $process->mustRun();
        
        return trim($process->getOutput());
    }

    /**
     * Validate that the critical binary is installed and meets version requirements.
     *
     * @param string $criticalBinary
     * @return void
     * @throws RuntimeException If version is below 2.0.6.
     */
    public function test(string $criticalBinary = 'critical'): void
    {
        $version = $this->getVersion($criticalBinary);
        
        // Critical version 2.0.6 is the minimum requirement
        if (version_compare($version, '2.0.6', '<')) {
            throw new RuntimeException(
                sprintf('Critical version 2.0.6 is the minimum requirement, got: %s', $version)
            );
        }
    }
}
