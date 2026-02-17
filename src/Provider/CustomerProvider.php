<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Provider;

use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Provides URLs for standard Customer Account pages (Login, Create, Forgot Password).
 *
 * This provider leverages the native Customer Url model to ensure all generated URLs
 * adhere to system configuration (like custom routes) and context.
 */
class CustomerProvider implements ProviderInterface
{
    /**
     * Provider unique name
     */
    public const NAME = 'customer';

    /**
     * @param CustomerUrl $customerUrl
     */
    public function __construct(
        private readonly CustomerUrl $customerUrl
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getUrls(StoreInterface $store): array
    {
        // We use the CustomerUrl model methods instead of hardcoded strings.
        // Since the ProcessManager runs this inside an emulated store environment,
        // the CustomerUrl model will correctly generate URLs for the specific store context.
        return [
            'customer_account_login' => $this->customerUrl->getLoginUrl(),
            'customer_account_create' => $this->customerUrl->getRegisterUrl(),
            'customer_account_forgotpassword' => $this->customerUrl->getForgotPasswordUrl(),
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
        return 1100;
    }

    /**
     * @inheritDoc
     */
    public function getCssIdentifierForRequest(RequestInterface $request, LayoutInterface $layout): ?string
    {
        if (!$request instanceof Http || $request->getModuleName() !== 'customer') {
            return null;
        }

        $actionName = $request->getFullActionName('_');
        $supportedActions = [
            'customer_account_login',
            'customer_account_create',
            'customer_account_forgotpassword'
        ];

        if (in_array($actionName, $supportedActions, true)) {
            return $actionName;
        }

        return null;
    }
}
