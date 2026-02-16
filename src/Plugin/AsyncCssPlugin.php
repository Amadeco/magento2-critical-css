<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\HTTP\Header;
use Magento\Framework\View\Result\Layout;
use Magento\Store\Model\ScopeInterface;
use Magento\Theme\Controller\Result\AsyncCssPlugin as MagentoAsyncCssPlugin;

/**
 * Plugin to conditionally disable Magento's native Async CSS loading.
 *
 * This ensures that the Critical CSS generation process (running via headless browser)
 * receives the fully rendered page without async JS interference, and prevents
 * async loading if no critical CSS is actually available.
 */
class AsyncCssPlugin extends MagentoAsyncCssPlugin
{
    /**
     * User Agent used by the 'critical' npm package (via 'got' library).
     * We must disable async loading when this user agent requests the page.
     */
    private const CRITICAL_GENERATOR_USER_AGENT = 'got (https://github.com/sindresorhus/got)';

    /**
     * Regex to identify the critical CSS style block injected by our module.
     * Matches: <style ... data-type="criticalCss" ...>CONTENT</style>
     */
    private const REGEX_CRITICAL_CSS_BLOCK = '#<style[^>]*data-type=["\']criticalCss["\'][^>]*>(.*?)</style>#si';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Header $httpHeader
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        protected Header $httpHeader
    ) {
        parent::__construct($scopeConfig);
    }

    /**
     * Intercept the render result to conditionally apply async CSS behavior.
     *
     * @param Layout $subject
     * @param Layout $result
     * @param ResponseInterface $httpResponse
     * @return Layout
     */
    public function afterRenderResult(Layout $subject, Layout $result, ResponseInterface $httpResponse): Layout
    {
        if ($this->canBeProcessed($httpResponse)) {
            return parent::afterRenderResult($subject, $result, $httpResponse);
        }

        return $result;
    }

    /**
     * Determine if standard Async CSS processing should be applied.
     *
     * @param ResponseInterface $httpResponse
     * @return bool
     */
    private function canBeProcessed(ResponseInterface $httpResponse): bool
    {
        // 1. Is the feature enabled in config?
        if (!$this->isCssCriticalEnabled()) {
            return false;
        }

        // 2. Is this request coming from the Critical CSS generator tool?
        // If yes, we disable async loading so the generator can scrape the "real" CSS.
        if ($this->httpHeader->getHttpUserAgent() === self::CRITICAL_GENERATOR_USER_AGENT) {
            return false;
        }

        // 3. Does the page actually contain non-empty Critical CSS?
        // If the critical CSS block is empty, we shouldn't defer the main CSS
        // because the user would see unstyled content (FOUC).
        $content = (string)$httpResponse->getContent();
        if ($this->isCriticalCssNodeEmpty($content)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the Critical CSS style block in the HTML is empty.
     *
     * @param string $content HTML content
     * @return bool
     */
    private function isCriticalCssNodeEmpty(string $content): bool
    {
        // Use regex to extract the content of the critical css style tag.
        // matches[1] will contain the inner text of the style tag.
        if (preg_match(self::REGEX_CRITICAL_CSS_BLOCK, $content, $matches)) {
            $cssContent = trim($matches[1] ?? '');
            
            // If the trimmed content is empty, the node is considered empty.
            return $cssContent === '';
        }

        // If the block is not found at all, we consider it "empty" (or non-existent)
        // implying standard async loading might not be appropriate or necessary via this logic.
        // However, looking at legacy logic: if the block was removed/not found, it returned true (empty).
        return true;
    }

    /**
     * Returns information whether css critical path is enabled.
     *
     * @return bool
     */
    private function isCssCriticalEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'dev/css/use_css_critical_path',
            ScopeInterface::SCOPE_STORE
        );
    }
}
