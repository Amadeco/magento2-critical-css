<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Model;

use M2Boilerplate\CriticalCss\Provider\ProviderInterface;
use M2Boilerplate\CriticalCss\Service\Identifier;
use Magento\Store\Api\Data\StoreInterface;
use Symfony\Component\Process\Process;

/**
 * Context object holding state for a running Critical CSS process.
 */
class ProcessContext
{
    /**
     * @param Process $process Symfony Process instance.
     * @param ProviderInterface $provider The provider that initiated this process.
     * @param StoreInterface $store The store context.
     * @param Identifier $identifierService Service to generate unique hashes.
     * @param string $identifier The raw identifier (e.g., product ID or layout handle).
     */
    public function __construct(
        private readonly Process $process,
        private readonly ProviderInterface $provider,
        private readonly StoreInterface $store,
        private readonly Identifier $identifierService,
        private readonly string $identifier
    ) {
    }

    /**
     * @return StoreInterface
     */
    public function getStore(): StoreInterface
    {
        return $this->store;
    }

    /**
     * @return ProviderInterface
     */
    public function getProvider(): ProviderInterface
    {
        return $this->provider;
    }

    /**
     * @return Process
     */
    public function getProcess(): Process
    {
        return $this->process;
    }

    /**
     * Get the original, raw identifier.
     *
     * @return string
     */
    public function getOrigIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Get the hashed, unique identifier used for file storage.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifierService->generateIdentifier(
            $this->provider,
            $this->store,
            $this->identifier
        );
    }
}
