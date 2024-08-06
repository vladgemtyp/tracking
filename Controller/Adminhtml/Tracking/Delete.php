<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2022 Serhii Popov
 * This source file is subject to The MIT License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @category Popov
 * @package Popov_<package>
 * @author Serhii Popov <popow.serhii@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Stagem\OrderMapTracking\Controller\Adminhtml\Tracking;

use Stagem\OrderMapTracking\Api\RouteRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Delete extends Action
{
    /**
     * @var RouteRepositoryInterface
     */
    private RouteRepositoryInterface $routeRepository;

    /**
     * @param Context $context
     * @param RouteRepositoryInterface $routeRepository
     */
    public function __construct(
        Context $context,
        RouteRepositoryInterface $routeRepository
    ) {
        parent::__construct($context);
        $this->routeRepository = $routeRepository;
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $routeId = (int)$this->getRequest()->getParam('id');

        if(!$routeId) {
            $this->messageManager->addErrorMessage(__('Error.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $route = $this->routeRepository->get($routeId);
            $this->routeRepository->delete($route);
            $this->messageManager->addSuccessMessage(__('You deleted the Route.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Cannot delete Route : ' . $e->getMessage()));

        }
        return $resultRedirect->setPath('*/*/index');
    }
}
