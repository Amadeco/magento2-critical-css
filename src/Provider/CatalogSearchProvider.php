<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Provider;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Search\Model\Query;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Provider for Search Result pages.
 */
class CatalogSearchProvider implements ProviderInterface
{
    public const NAME = 'catalogsearch';

    /**
     * @param UrlInterface $url
     * @param CollectionFactory $queryCollectionFactory
     */
    public function __construct(
        private readonly UrlInterface $url,
        private readonly CollectionFactory $queryCollectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getUrls(StoreInterface $store): array
    {
        $urls = [
            'catalogsearch_advanced_index' => $store->getUrl('catalogsearch/advanced'),
            'search_term_popular' => $store->getUrl('search/term/popular'),
        ];

        /** @var Query|null $term */
        $term = $this->queryCollectionFactory
            ->create()
            ->setPopularQueryFilter((int)$store->getId())
            ->setPageSize(1)
            ->load()
            ->getFirstItem();

        if ($term && $term->getId() && $term->getQueryText()) {
            $urls['catalogsearch_result_index'] = $store->getUrl(
                'catalogsearch/result',
                ['_query' => ['q' => $term->getQueryText()]]
            );
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
        return 1300;
    }

    /**
     * @inheritDoc
     */
    public function getCssIdentifierForRequest(RequestInterface $request, LayoutInterface $layout): ?string
    {
        if (!$request instanceof Http || $request->getModuleName() !== 'catalogsearch') {
            return null;
        }

        $actionName = $request->getFullActionName('_');
        $supportedActions = [
            'catalogsearch_advanced_index',
            'catalogsearch_result_index',
            'search_term_popular',
        ];

        return in_array($actionName, $supportedActions, true) ? $actionName : null;
    }
}
