<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Service;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Url\CssResolver;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Post-processes generated CSS to ensure URLs (images, fonts) are absolute.
 *
 * Critical CSS is inlined in the HTML <head>, so relative paths like '../images/foo.png'
 * will fail. This service converts them to absolute URLs.
 */
class CssProcessor
{
    /**
     * Regex to find relative paths in CSS.
     * Matches paths starting with ../ or pointing to static/pub folders.
     */
    private const REGEX_RELATIVE_URLS = '@(\.\./)*(static|/static|/pub/static)/(.+)$@i';

    /**
     * @param CssResolver $cssResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected CssResolver $cssResolver,
        protected StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Replace relative URLs in the CSS content with absolute URLs.
     *
     * @param StoreInterface $store
     * @param string $cssContent
     * @return string
     */
    public function process(StoreInterface $store, string $cssContent): string
    {
        // Ensure we have the Store Model to access base URL functionality
        if (!($store instanceof Store)) {
            $store = $this->storeManager->getStore($store->getId());
        }

        /** @var string $baseUrl */
        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);

        return $this->cssResolver->replaceRelativeUrls(
            $cssContent,
            function (string $path) use ($baseUrl): string {
                return $this->resolveAbsolutePath($path, $baseUrl);
            }
        );
    }

    /**
     * callback function to resolve a single path to absolute.
     *
     * @param string $path
     * @param string $baseUrl
     * @return string
     */
    private function resolveAbsolutePath(string $path, string $baseUrl): string
    {
        $matches = [];
        if (preg_match(self::REGEX_RELATIVE_URLS, $path, $matches)) {
            /**
             * Example Match breakdown:
             * [0] Full match: ../../../pub/static/version/frontend/Theme/en_US/image.png
             * [2] Base dir: pub/static
             * [3] Asset path: version/frontend/Theme/en_US/image.png
             */
            if (isset($matches[3])) {
                // Reconstruct: https://base.url/pub/static/version/...
                return $baseUrl . ltrim($matches[2] . '/' . $matches[3], '/');
            }
            
            // Fallback for simpler matches
            return $baseUrl . ltrim($matches[0], '/');
        }

        // Return original path if it doesn't match expected pattern
        return $path;
    }
}
