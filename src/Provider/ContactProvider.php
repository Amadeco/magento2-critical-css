<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Provider;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Provides URLs for the Contact Us page to generate Critical CSS.
 */
class ContactProvider implements ProviderInterface
{
    public const NAME = 'contact';

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
            'contact_index_index' => $this->url->getUrl('contact'),
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
        return 1200;
    }

    /**
     * @inheritDoc
     */
    public function getCssIdentifierForRequest(RequestInterface $request, LayoutInterface $layout): ?string
    {
        if (!$request instanceof Http || $request->getModuleName() !== 'contact') {
            return null;
        }

        if ($request->getFullActionName('_') === 'contact_index_index') {
            return 'contact_index_index';
        }

        return null;
    }
}
