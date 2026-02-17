<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration service for Critical CSS settings.
 *
 * Handles retrieval of module settings from the core_config_data.
 */
class Config
{
    public const CONFIG_PATH_ENABLED = 'dev/css/use_css_critical_path';
    public const CONFIG_PATH_CRITICAL_BINARY = 'dev/css/critical_css_critical_binary';
    public const CONFIG_PATH_PARALLEL_PROCESSES = 'dev/css/critical_css_parallel_processes';
    public const CONFIG_PATH_USERNAME = 'dev/css/critical_css_username';
    public const CONFIG_PATH_PASSWORD = 'dev/css/critical_css_password';
    public const CONFIG_PATH_DIMENSIONS = 'dev/css/critical_css_dimensions';
    public const CONFIG_PATH_FORCE_INCLUDE_CSS_SELECTORS = 'dev/css/critical_css_force_include_css_selectors';

    /**
     * Default screen dimensions if none are configured.
     * @var string[]
     */
    private array $defaultDimensions = [
        '375x812',  // XS / iPhone X
        '576x1152', // SM
        '768x1024', // MD / iPad
        '1024x768', // LG / iPad
        '1280x720', // XL
    ];

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check if Critical CSS generation is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ENABLED);
    }

    /**
     * Get list of CSS selectors to always include in the critical CSS.
     *
     * @return string[]
     */
    public function getForceIncludeCssSelectors(): array
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_FORCE_INCLUDE_CSS_SELECTORS);
        if (empty($value)) {
            return [];
        }

        // INTEGRATION FIX: Use newline as delimiter instead of comma.
        // This prevents the selector from breaking when it contains a comma (e.g. "div[data-val='1,2']").
        // We strictly treat each line as a single selector.
        $selectors = preg_split('/\r\n|\r|\n/', (string)$value);

        return array_values(array_filter(array_map('trim', $selectors)));
    }

    /**
     * Get configured screen dimensions for Critical CSS generation.
     *
     * @return string[]
     */
    public function getDimensions(): array
    {
        $value = $this->scopeConfig->getValue(self::CONFIG_PATH_DIMENSIONS);
        if (empty($value)) {
            return $this->defaultDimensions;
        }

        $dimensions = explode(',', (string)$value);
        $dimensions = array_values(array_filter(array_map('trim', $dimensions)));

        return empty($dimensions) ? $this->defaultDimensions : $dimensions;
    }

    /**
     * Get the number of parallel processes allowed.
     *
     * @return int
     */
    public function getNumberOfParallelProcesses(): int
    {
        $processes = (int)$this->scopeConfig->getValue(self::CONFIG_PATH_PARALLEL_PROCESSES);
        return max(1, $processes);
    }

    /**
     * Get HTTP Basic Auth Username (if applicable).
     *
     * @param string|null $scopeCode
     * @return string|null
     */
    public function getUsername(?string $scopeCode = null): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::CONFIG_PATH_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
        return !empty($value) ? (string)$value : null;
    }

    /**
     * Get HTTP Basic Auth Password (if applicable).
     *
     * @param string|null $scopeCode
     * @return string|null
     */
    public function getPassword(?string $scopeCode = null): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::CONFIG_PATH_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
        return !empty($value) ? (string)$value : null;
    }

    /**
     * Get the path to the 'critical' binary executable.
     *
     * @return string
     */
    public function getCriticalBinary(): string
    {
        return (string)$this->scopeConfig->getValue(self::CONFIG_PATH_CRITICAL_BINARY);
    }
}
