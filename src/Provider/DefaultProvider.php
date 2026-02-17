<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Provider;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Model\PageLayout\Config\BuilderInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Provides URLs for generic Page Layouts (e.g., 1column, 2columns-left).
 *
 * This provider generates URLs pointing to a specific controller that renders
 * an empty page with the requested layout handle. This allows generating
 * "base" critical CSS for layouts that might not be covered by specific pages.
 *
 * It runs with the lowest priority to act as a fallback when no specific
 * provider (Product, Category, etc.) handles the request.
 */
class DefaultProvider implements ProviderInterface
{
    /**
     * Provider unique name
     */
    public const NAME = 'default';

    /**
     * @param UrlInterface $url
     * @param BuilderInterface $pageLayoutBuilder
     */
    public function __construct(
        private readonly UrlInterface $url,
        private readonly BuilderInterface $pageLayoutBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getUrls(StoreInterface $store): array
    {
        // Retrieve all registered page layouts (empty, 1column, 2columns-left, etc.)
        $options = array_keys($this->pageLayoutBuilder->getPageLayoutsConfig()->getOptions());

        $urls = [];
        foreach ($options as $option) {
            // Generate a URL to the internal controller that renders this specific layout
            $urls[$option] = $this->url->getUrl('m2bp/criticalCss/default', ['page_layout' => $option]);
        }

        return $urls;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        // Return minimum integer to ensure this runs last, acting as a generic fallback.
        return PHP_INT_MIN;
    }

    /**
     * @inheritDoc
     */
    public function getCssIdentifierForRequest(RequestInterface $request, LayoutInterface $layout): ?string
    {
        // The identifier is the page layout handle (e.g., '1column').
        // This acts as a catch-all: if a more specific provider hasn't matched,
        // we serve the Critical CSS generated for this general layout structure.
        return $layout->getUpdate()->getPageLayout();
    }
}
