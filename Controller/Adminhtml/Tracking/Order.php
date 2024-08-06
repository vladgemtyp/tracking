<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2023 Serhii Popov
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

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Stagem\OrderMapTracking\Api\RouteRepositoryInterface;
use Stagem\OrderMapTracking\Model\Route;
use Stagem\OrderMapTracking\Helper\Helper;
use Magento\Framework\Stdlib\DateTime;

class Order extends Action
{
    /**
     * @var RouteRepositoryInterface
     */
    private RouteRepositoryInterface $routeRepository;

    /**
     * @var Helper
     */
    private Helper $helper;

    private DateTime $dateTime;

    /**
     * @param Context $context
     * @param RouteRepositoryInterface $routeRepository
     * @param Helper $helper
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        RouteRepositoryInterface $routeRepository,
        Helper $helper,
        DateTime $dateTime
    ) {
        parent::__construct($context);
        $this->routeRepository = $routeRepository;
        $this->helper = $helper;
        $this->dateTime = $dateTime;
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $request = $this->getRequest();

        $changedOrders = explode(',', $request->getParam('order'));
        $route = $this->routeRepository->get($request->getParam('id'));
        $addresses = $this->helper->json->unserialize($route->getOrderAddresses());
        foreach ($addresses as &$address) {
            if (in_array($address['order'], $changedOrders)) {
                $address['status'] = $this->getOrderStatus($address);
                $address['deliveryDate'] = $address['status'] === Route::ORDER_STATUS_DELIVERED ?
                    $this->dateTime->formatDate(true) : NULL;
                $this->helper->logger->info('ADMIN : STATUS CHANGED : MANUAL : ' . $address['order'] . ' : TO ' . $address['status']);
            }
        }
        $route->setOrderAddresses($this->helper->json->serialize($addresses));

        try {
            $this->routeRepository->save($route);
            $resultRedirect->setPath(
                '*/*/open',
                [
                    'id' => $request->getParam('id'),
                    '_current' => true,
                ]
            );
            $this->messageManager->addSuccessMessage(__('Order status was changed to ') . $request->getParam('status'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Error. Cannot save : ' . $exception->getMessage()));
            $resultRedirect->setPath('*/*/index');
        }

        return $resultRedirect;
    }

    public function getOrderStatus($address): string
    {
        $requestParamStatus = $this->getRequest()->getParam('status');
        if ($requestParamStatus !== 'swap') return $requestParamStatus;

        if (!array_key_exists('status', $address)) return Route::ORDER_STATUS_DELIVERED;

        return $address['status'] === Route::ORDER_STATUS_DELIVERED ?
                Route::ORDER_STATUS_PROCESSING : Route::ORDER_STATUS_DELIVERED;
    }
}
