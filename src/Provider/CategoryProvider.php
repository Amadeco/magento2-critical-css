<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Provider;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Provides URLs for Category pages to generate Critical CSS.
 * Retrieves representative categories based on layout configuration (Anchor, Page Layout, etc.).
 */
class CategoryProvider implements ProviderInterface
{
    public const NAME = 'category';

    /**
     * Maximum number of categories to process to avoid OOM on large catalogs.
     */
    private const COLLECTION_LIMIT = 500;

    /**
     * @param Registry $registry
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        protected Registry $registry,
        protected CollectionFactory $categoryCollectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getUrls(StoreInterface $store): array
    {
        $urls = [];

        // 1. Fetch Product Listing Categories (Grouped by Layout settings)
        $this->collectProductListingUrls($store, $urls);

        // 2. Fetch Landing Pages (Categories without products)
        $this->collectLandingPageUrls($store, $urls);

        return $urls;
    }

    /**
     * Fetch representative URLs for standard product listing categories.
     *
     * @param StoreInterface $store
     * @param array $urls
     */
    private function collectProductListingUrls(StoreInterface $store, array &$urls): void
    {
        try {
            /** @var Collection $collection */
            $collection = $this->categoryCollectionFactory->create();
            $collection->setStore($store);
            $collection->addIsActiveFilter();
            
            // Filter: Product Mode OR Default (null)
            $collection->addAttributeToFilter(
                [
                    ['attribute' => 'display_mode', 'eq' => Category::DM_PRODUCT],
                    ['attribute' => 'display_mode', 'null' => true]
                ],
                null,
                'left'
            );
            $collection->addAttributeToFilter('level', ['gt' => 1]);
            
            // Optimization: Only select necessary attributes for identifier generation
            $collection->addAttributeToSelect(['is_anchor', 'page_layout', 'custom_design', 'display_mode']);
            
            $collection->addUrlRewriteToResult();

            // Order by complexity/importance to find "main" categories first
            $collection->addAttributeToSort('children_count', DataCollection::SORT_ORDER_DESC);
            $collection->addAttributeToSort('level', DataCollection::SORT_ORDER_ASC);

            // LIMIT the collection to prevent OOM
            $collection->setPageSize(self::COLLECTION_LIMIT);
            $collection->setCurPage(1);

            /** @var Category $category */
            foreach ($collection as $category) {
                // We use the unique Identifier hash to deduplicate layouts.
                // If this layout config is already in $urls, we skip overwriting it (first win).
                $id = $this->getIdentifier($category);
                if (!isset($urls[$id])) {
                    $urls[$id] = $store->getUrl("catalog/category/view/", ["id" => $category->getId()]);
                }
            }
        } catch (LocalizedException $e) {
            // Silently fail for this provider part, similar to original implementation
        }
    }

    /**
     * Fetch representative URLs for Landing Page categories (Static Blocks).
     *
     * @param StoreInterface $store
     * @param array $urls
     */
    private function collectLandingPageUrls(StoreInterface $store, array &$urls): void
    {
        try {
            /** @var Collection $collection */
            $collection = $this->categoryCollectionFactory->create();
            $collection->setStore($store);
            $collection->addIsActiveFilter();
            
            // Filter: Non-Product Mode (Page/Block mode)
            $collection->addAttributeToFilter('display_mode', ['neq' => Category::DM_PRODUCT]);
            $collection->addAttributeToFilter('level', ['gt' => 1]);
            
            $collection->addUrlRewriteToResult();
            
            // Limit landing pages as well
            $collection->setPageSize(self::COLLECTION_LIMIT);
            $collection->setCurPage(1);

            /** @var Category $category */
            foreach ($collection as $category) {
                $id = $this->getIdentifier($category);
                if (!isset($urls[$id])) {
                    $urls[$id] = $category->getUrl();
                }
            }
        } catch (LocalizedException $e) {
            // Silently fail
        }
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
        return 1500;
    }

    /**
     * @inheritDoc
     */
    public function getCssIdentifierForRequest(RequestInterface $request, LayoutInterface $layout): ?string
    {
        if (!$request instanceof Http) {
            return null;
        }

        if ($request->getFullActionName('_') === 'catalog_category_view') {
            $category = $this->registry->registry('current_category');
            
            if ($category instanceof Category) {
                return $this->getIdentifier($category);
            }
        }
        return null;
    }

    /**
     * Generate a unique identifier based on layout-impacting attributes.
     *
     * @param Category $category
     * @return string
     */
    protected function getIdentifier(Category $category): string
    {
        // If it's a Landing Page (Static Block), unique by ID as content varies wildly
        if ($category->getDisplayMode() !== Category::DM_PRODUCT && $category->getDisplayMode() !== null) {
            return (string)$category->getId();
        }

        // For Product Listings, group by layout settings
        return sprintf(
            'is_anchor:%d,page_layout:%s,custom_design:%s',
            (int)$category->getData('is_anchor'),
            (string)$category->getData('page_layout'),
            (string)$category->getData('custom_design')
        );
    }
}
