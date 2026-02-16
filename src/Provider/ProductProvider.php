<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Provider;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Provides URLs for Product pages to generate Critical CSS.
 * Retrieves a representative product for each Product Type (Simple, Configurable, etc.).
 */
class ProductProvider implements ProviderInterface
{
    public const NAME = 'product';

    /**
     * @param Registry $registry
     * @param CollectionFactory $productCollectionFactory
     * @param Status $productStatus
     * @param Visibility $productVisibility
     * @param UrlInterface $url
     */
    public function __construct(
        protected Registry $registry,
        protected CollectionFactory $productCollectionFactory,
        protected Status $productStatus,
        protected Visibility $productVisibility,
        protected UrlInterface $url
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getUrls(StoreInterface $store): array
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->setStore($store);
        $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        $collection->setVisibility($this->productVisibility->getVisibleInSiteIds());
        
        // Strategy: Get one product per Type ID (simple, configurable, bundle, etc.)
        // This ensures we cover different layout templates without loading the whole catalog.
        $collection->groupByAttribute('type_id');
        
        // Safety Limit: Prevent OOM if something is wrong with the grouping or custom types
        $collection->setPageSize(20);
        $collection->setCurPage(1);

        $urls = [];
        /** @var Product $product */
        foreach ($collection->getItems() as $product) {
            $urls[$product->getTypeId()] = $product->getProductUrl();
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
        return 1400;
    }

    /**
     * @inheritDoc
     */
    public function getCssIdentifierForRequest(RequestInterface $request, LayoutInterface $layout): ?string
    {
        if (!$request instanceof Http) {
            return null;
        }

        if ($request->getFullActionName('_') === 'catalog_product_view') {
            $product = $this->registry->registry('current_product');
            
            if ($product instanceof ProductInterface) {
                return (string)$product->getTypeId();
            }
        }

        return null;
    }
}
