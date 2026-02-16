<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Controller\CriticalCss;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller action to render a specific layout handle for CSS generation.
 *
 * This endpoint is hit by the headless browser to render the page structure
 * defined by the 'page_layout' parameter.
 */
class DefaultAction implements HttpGetActionInterface
{
    /**
     * @param PageFactory $pageFactory
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * Execute the action.
     *
     * @return Page
     */
    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $pageLayout = $this->request->getParam('page_layout');

        if ($pageLayout && is_string($pageLayout)) {
            $page->getConfig()->setPageLayout($pageLayout);
            $page->getLayout()->getUpdate()->addHandle('m2bp-' . $pageLayout);
        }

        return $page;
    }
}
