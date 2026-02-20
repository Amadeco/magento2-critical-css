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
     * @param string $url Target URL to process.
     * @param string[] $dimensions Array of viewport dimensions (e.g., ['1024x768', '1920x1080']).
     * @param string[] $forceIncludeCssSelectors Array of CSS selectors to forcefully include.
     * @param string $criticalBinary Executable binary command or absolute path.
     * @param string|null $username HTTP Basic Authentication username.
     * @param string|null $password HTTP Basic Authentication password.
     * * @return Process
     */
    public function createCriticalCssProcess(
        string $url,
        array $dimensions,
        array $forceIncludeCssSelectors,
        string $criticalBinary = 'critical',
        ?string $username = null,
        ?string $password = null
    ): Process {
        $command = [$criticalBinary, $url];

        // Append forcefully included CSS selectors
        foreach ($forceIncludeCssSelectors as $selector) {
            array_push($command, '--penthouse-forceInclude', $selector);
        }

        // Append viewport dimensions
        foreach ($dimensions as $dimension) {
            array_push($command, '--dimensions', $dimension);
        }

        // Append Legacy Authentication logic if credentials are provided
        if (trim((string)$username) !== '' && trim((string)$password) !== '') {
            array_push($command, '--user', $username, '--pass', $password);
        }

        // Append static core arguments (Note: '--minify' explicitly omitted for binary compatibility)
        array_push(
            $command,
            '--strict',
            '--no-request-https.rejectUnauthorized',
            '--ignore-atrule',
            '@font-face',
            '--ignore-atrule',
            'print'
        );

        /** @var Process $process */
        return $this->processFactory->create(['command' => $command]);
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
