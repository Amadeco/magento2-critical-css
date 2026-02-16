<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Service;

use M2Boilerplate\CriticalCss\Provider\ProviderInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Generates unique identifiers for Critical CSS files based on context.
 */
class Identifier
{
    /**
     * @param Encryptor $encryptor
     */
    public function __construct(
        protected Encryptor $encryptor
    ) {
    }

    /**
     * Generate a unique hash for a specific provider, store, and request identifier.
     *
     * Format: [STORE_CODE]PROVIDER_IDENTIFIER
     *
     * @param ProviderInterface $provider
     * @param StoreInterface $store
     * @param string $identifier
     * @return string
     */
    public function generateIdentifier(
        ProviderInterface $provider,
        StoreInterface $store,
        string $identifier
    ): string {
        $uniqueIdentifier = sprintf(
            '[%s]%s_%s',
            $store->getCode(),
            $provider->getName(),
            $identifier
        );

        return $this->encryptor->hash($uniqueIdentifier, Encryptor::HASH_VERSION_MD5);
    }
}
