<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Provider;

/**
 * Container service for managing Critical CSS providers.
 *
 * This class aggregates multiple providers (product, category, cms, etc.),
 * sorts them by priority, and offers O(1) lookup by name.
 */
class Container
{
    /**
     * Associative array of providers indexed by their unique name.
     * Use this for direct lookups.
     *
     * @var array<string, ProviderInterface>
     */
    private array $providers = [];

    /**
     * Cached list of providers sorted by priority.
     * Use this for iteration.
     *
     * @var array<int, ProviderInterface>|null
     */
    private ?array $sortedProviders = null;

    /**
     * @param array<mixed> $providers Raw array of providers injected via DI.
     */
    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            if ($provider instanceof ProviderInterface) {
                $this->addProvider($provider);
            }
        }
    }

    /**
     * Get all registered providers, sorted by priority (Descending).
     *
     * This method uses memoization to avoid re-sorting on every call.
     *
     * @return ProviderInterface[]
     */
    public function getProviders(): array
    {
        if ($this->sortedProviders !== null) {
            return $this->sortedProviders;
        }

        // Create a list for sorting
        $list = array_values($this->providers);

        // Sort descending by priority (Higher number = Higher priority/Earlier in list)
        usort($list, fn(ProviderInterface $a, ProviderInterface $b): int => 
            $b->getPriority() <=> $a->getPriority()
        );

        $this->sortedProviders = $list;

        return $this->sortedProviders;
    }

    /**
     * Add a provider to the container.
     *
     * Note: This invalidates the sorted cache.
     *
     * @param ProviderInterface $provider
     * @return void
     */
    public function addProvider(ProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
        
        // Invalidate cache to force re-sort on next retrieval
        $this->sortedProviders = null;
    }

    /**
     * Retrieve a specific provider by name.
     *
     * @param string $name
     * @return ProviderInterface|null
     */
    public function getProvider(string $name): ?ProviderInterface
    {
        return $this->providers[$name] ?? null;
    }
}
