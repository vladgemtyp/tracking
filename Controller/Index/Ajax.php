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

namespace Stagem\OrderMapTracking\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Stagem\OrderMapTracking\Helper\Data;
use Stagem\OrderMapTracking\Model\ResourceModel\Route\CollectionFactory;
use Stagem\OrderMapTracking\Helper\Helper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Action;

class Ajax extends Action
{
    protected CollectionFactory $routeCollectionFactory;

    protected Helper $helper;

    protected JsonFactory $resultJsonFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $routeCollectionFactory
     * @param Helper $helper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        CollectionFactory $routeCollectionFactory,
        Helper $helper,
        JsonFactory $resultJsonFactory
        ) {
        $this->routeCollectionFactory = $routeCollectionFactory;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        return parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $response = $this->getResponseData();

        return $this->resultJsonFactory->create()
            ->setData($response);
    }

    /**
     * @return array
     */
    public function getResponseData(): array
    {
        $response['success'] = false;

        if (!empty($this->getRequest()->getParam('orderId'))) {
            $orderId = $this->getRequest()->getParam('orderId');

            $routeCollection = $this->routeCollectionFactory->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('order_addresses', ['like' => '%"order":"' . $orderId . '"%'])
                ->load();
            $route = $routeCollection->getFirstItem();

            if (!is_null($route->getId())) {
                $response['status'] = $route->getStatus();

                $orders =  $this->helper->json->unserialize($route->getOrderAddresses());
                foreach ($orders as $order) {
                    $response['data']['addresses'][] = $order;

                    if ($order['order'] === $orderId) {
                        $response['success'] = true;
                        break;
                    }
                }
                array_unshift($response['data']['addresses'], Data::STOREHOUSE);

                $vehicle = $this->getVehicle($route);
                if ($vehicle) {
                    $response['data']['vehicle'] = $vehicle;
                }
            }
        }

        return $response;
    }

    /**
     * @param $route
     * @return false|mixed
     */
    public function getVehicle($route)
    {
        $result = false;

        $vehicles = $this->helper->json->unserialize(
            $this->helper->sendVehiclesRequest(Data::BASE_URL_US . Data::URL_VEHICLES)
                ->getBody()
        )['Data'];

        if (is_array($vehicles)) {
            foreach ($vehicles as $vehicle) {
                if ($route->getVehicleRegistration() === $vehicle['RegistrationNumber']) {
                    $quartixResponse = $this->helper->sendVehiclesLiveRequest(Data::BASE_URL_US . Data::URL_VEHICLES_LIVE
                    );
                    if ($quartixResponse) {
                        $vehiclesLive = $this->helper->json->unserialize($quartixResponse->getBody());
                        foreach ($vehiclesLive['Data'] as $vehicleLive) {
                            if ($vehicle['VehicleId'] === $vehicleLive['VehicleId']) {
                                $result = $vehicleLive;
                                break;
                            }
                        }
                    }
                    break;
                }
            }
        }

        return $result;
    }
}
