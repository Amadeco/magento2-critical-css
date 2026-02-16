<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Logger;

use M2Boilerplate\CriticalCss\Logger\Handler\ConsoleHandlerFactory;
use Magento\Framework\Logger\Monolog;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Factory for creating a Console Logger instance bound to a specific OutputInterface.
 *
 * @api
 */
class ConsoleLoggerFactory
{
    /**
     * @param ConsoleHandlerFactory $consoleHandlerFactory
     */
    public function __construct(
        protected ConsoleHandlerFactory $consoleHandlerFactory
    ) {
    }

    /**
     * Create a logger instance configured to write to the console output.
     *
     * @param OutputInterface $output
     * @param string $name
     * @return LoggerInterface
     */
    public function create(OutputInterface $output, string $name = 'critical-css-console'): LoggerInterface
    {
        $consoleHandler = $this->consoleHandlerFactory->create(['output' => $output]);
        
        // We manually instantiate Monolog here to avoid ObjectManager usage.
        // This replicates the behavior of the virtualType 'M2Boilerplate\CriticalCss\Logger\Console'
        // defined in di.xml, but allows runtime injection of the output stream.
        return new Monolog(
            $name,
            [$consoleHandler]
        );
    }
}
