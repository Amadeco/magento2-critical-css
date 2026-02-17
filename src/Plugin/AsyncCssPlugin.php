<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Plugin;

use M2Boilerplate\CriticalCss\Config\Config;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\HTTP\Header;
use Magento\Framework\View\Result\Layout;

/**
 * Plugin to conditionally disable Magento's native Async CSS loading.
 *
 * This ensures that the Critical CSS generation process (running via headless browser)
 * receives the fully rendered page without async JS interference, and prevents
 * async loading if no critical CSS is actually available.
 *
 * Since this plugin replaces the native Magento AsyncCssPlugin (via di.xml),
 * it implements the CSS replacement logic directly without inheritance.
 */
class AsyncCssPlugin
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
     * Regex to match standard stylesheet links for replacement.
     * Captures attributes before and after the rel="stylesheet" definition.
     */
    private const REGEX_CSS_LINK = '/<link([^>]+)rel="stylesheet"([^>]*)>/i';

    /**
     * @param Config $config
     * @param Header $httpHeader
     */
    public function __construct(
        private readonly Config $config,
        private readonly Header $httpHeader
    ) {
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
        if (!$this->canBeProcessed($httpResponse)) {
            return $result;
        }

        $content = (string)$httpResponse->getContent();

        // Apply Async CSS pattern (rel="preload" with JS onload handler)
        // This duplicates the logic of the native plugin we are replacing.
        $newContent = preg_replace_callback(
            self::REGEX_CSS_LINK,
            function (array $matches): string {
                // $matches[0] = Full match
                // $matches[1] = Attributes before rel
                // $matches[2] = Attributes after rel

                // Construct the preload link
                $preload = '<link' . $matches[1] . 'rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"' . $matches[2] . '>';

                // Construct the fallback noscript link for JS-disabled browsers
                $noscript = '<noscript>' . $matches[0] . '</noscript>';

                return $preload . $noscript;
            },
            $content
        );

        if (is_string($newContent)) {
            $httpResponse->setContent($newContent);
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
        if (!$this->config->isEnabled()) {
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

        // If the block is not found at all, we consider it "empty"
        return true;
    }
}
