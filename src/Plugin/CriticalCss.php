<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Plugin;

use M2Boilerplate\CriticalCss\Provider\Container;
use M2Boilerplate\CriticalCss\Service\Identifier;
use M2Boilerplate\CriticalCss\Service\Storage;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Header\CriticalCss as Subject;

/**
 * Plugin for Magento\Theme\Block\Html\Header\CriticalCss.
 *
 * This interceptor overrides the native Critical CSS retrieval logic to provide
 * dynamically generated CSS based on the current request context and active providers.
 */
class CriticalCss
{
    /**
     * @param LayoutInterface $layout Current layout instance.
     * @param RequestInterface $request Current HTTP request.
     * @param Container $container Container holding all active CSS providers.
     * @param Storage $storage Persistence layer for reading generated CSS files.
     * @param Identifier $identifier Service for generating unique CSS hash identifiers.
     * @param StoreManagerInterface $storeManager Store manager for scope resolution.
     */
    public function __construct(
        private readonly LayoutInterface $layout,
        private readonly RequestInterface $request,
        private readonly Container $container,
        private readonly Storage $storage,
        private readonly Identifier $identifier,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Intercepts native Critical CSS data retrieval.
     *
     * Iterates through registered providers to find a matching CSS file for the current request.
     * Returns the raw CSS content if found, ensuring the first matching provider takes precedence.
     *
     * @param Subject $subject Native block subject.
     * @param string|null $result Original result from the core method (ignored).
     * @return string|null The generated Critical CSS content or null if no match is found.
     * @throws FileSystemException If file reading operations fail.
     */
    public function afterGetCriticalCssData(Subject $subject, ?string $result): ?string
    {
        // Reset result to ensure only our custom logic determines the output.
        $result = '';

        try {
            $store = $this->storeManager->getStore();
        } catch (NoSuchEntityException) {
            // If store resolution fails, return empty content to avoid blocking page render.
            return $result;
        }

        $providers = $this->container->getProviders();

        foreach ($providers as $provider) {
            // Check if this provider has a CSS identifier for the current request context.
            $requestIdentifier = $provider->getCssIdentifierForRequest($this->request, $this->layout);

            if ($requestIdentifier) {
                // Generate the unique hash for the provider, store, and request context.
                $cssHash = $this->identifier->generateIdentifier($provider, $store, $requestIdentifier);

                // Attempt to retrieve the actual CSS content from storage.
                $content = $this->storage->getCriticalCss($cssHash);

                if ($content) {
                    // Return the first match found (Respecting provider priority).
                    return $content;
                }
            }
        }

        return $result;
    }
}
