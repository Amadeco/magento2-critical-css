<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Provider;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Interface ProviderInterface
 *
 * Contract for classes that provide URLs for Critical CSS generation
 * and identify requests to serve that CSS.
 */
interface ProviderInterface
{
    /**
     * Get a list of URLs to process for the given store.
     *
     * @param StoreInterface $store
     * @return array<string, string> Map of [Identifier => URL]
     */
    public function getUrls(StoreInterface $store): array;

    /**
     * Get the unique name of the provider.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if the provider is active/available.
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Get the execution priority (higher is earlier).
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Determine the CSS identifier for a given request.
     *
     * @param RequestInterface $request
     * @param LayoutInterface $layout
     * @return string|null Returns the identifier if this provider handles the request, otherwise null.
     */
    public function getCssIdentifierForRequest(RequestInterface $request, LayoutInterface $layout): ?string;
}
