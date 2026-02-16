<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Provider;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Provider for CMS Pages.
 *
 * Currently configured to only provide the Home Page (cms_index_index).
 */
class CmsPageProvider implements ProviderInterface
{
    public const NAME = 'cms_page';

    /**
     * @param UrlInterface $url
     */
    public function __construct(
        private readonly UrlInterface $url
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getUrls(StoreInterface $store): array
    {
        return [
            'cms_index_index' => $store->getUrl('/'),
        ];
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
        return 1000;
    }

    /**
     * @inheritDoc
     */
    public function getCssIdentifierForRequest(RequestInterface $request, LayoutInterface $layout): ?string
    {
        if (!$request instanceof Http || $request->getModuleName() !== 'cms') {
            return null;
        }

        if ($request->getFullActionName('_') === 'cms_index_index') {
            // Home page
            return 'cms_index_index';
        }

        return null;
    }
}
