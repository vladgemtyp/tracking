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

namespace Stagem\OrderMapTracking\Cron;

use Magento\Framework\Exception\AlreadyExistsException;
use Stagem\OrderMapTracking\Api\RouteRepositoryInterface;
use Stagem\OrderMapTracking\Helper\Data;
use Stagem\OrderMapTracking\Helper\Helper;
use Stagem\OrderMapTracking\Model\ResourceModel\Route\CollectionFactory as RouteCollectionFactory;
use Stagem\OrderMapTracking\Model\Route;
use Magento\Framework\Stdlib\DateTime;
use Stagem\Esputnik\Model\OrdersForSendingRepository;
use Stagem\Esputnik\Model\OrdersForSendingFactory;

class PingVehicles
{
    private Helper $helper;

    private RouteCollectionFactory $routeCollectionFactory;

    private RouteRepositoryInterface $routeRepository;

    private DateTime $dateTime;

    private OrdersForSendingRepository $ordersForSendingRepository;

    private OrdersForSendingFactory $ordersForSendingFactory;

    /**
     * @param Helper $helper
     * @param RouteCollectionFactory $routeCollectionFactory
     * @param RouteRepositoryInterface $routeRepository
     * @param DateTime $dateTime
     * @param OrdersForSendingRepository $ordersForSendingRepository
     * @param OrdersForSendingFactory $ordersForSendingFactory
     */
    public function __construct(
        Helper $helper,
        RouteCollectionFactory $routeCollectionFactory,
        RouteRepositoryInterface $routeRepository,
        DateTime $dateTime,
        OrdersForSendingRepository $ordersForSendingRepository,
        OrdersForSendingFactory $ordersForSendingFactory
    ) {
        $this->helper = $helper;
        $this->routeCollectionFactory = $routeCollectionFactory;
        $this->routeRepository = $routeRepository;
        $this->dateTime = $dateTime;
        $this->ordersForSendingRepository = $ordersForSendingRepository;
        $this->ordersForSendingFactory = $ordersForSendingFactory;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $routeCollection = $this->routeCollectionFactory->create()
            ->addFieldToSelect(['entity_id', 'order_addresses', 'vehicle_registration'])
            ->addFieldToFilter('status', ['neq' => Route::STATUS_COMPLETE])
            ->load();
        if ($routeCollection->count()) {
            $ordersForSending = [];
            $vehiclesData = $this->getVehicles();
            if (!$vehiclesData) {
                $this->helper->logger->info('CRON : GET VEHICLES ERROR');
                return $this;
            }

            foreach ($routeCollection->getItems() as $route) {
                if (!array_key_exists($route->getVehicleRegistration(), $vehiclesData)) continue;

                $isDataChanged = false;
                $ordersInfo = $this->helper->json->unserialize($route->getOrderAddresses());
                foreach ($ordersInfo as $key => &$orderInfo) {
                    if (isset($orderInfo['status']) && $orderInfo['status'] === Route::ORDER_STATUS_DELIVERED) continue;

                    $orderLat = (float)number_format($orderInfo['position']['lat'], 3, '.', '');
                    $orderLng = (float)number_format($orderInfo['position']['lng'], 3, '.', '');

                    $vehicleLat = (float)number_format($vehiclesData[$route->getVehicleRegistration()]['Latitude'], 3, '.', '');
                    $vehicleLng = (float)number_format($vehiclesData[$route->getVehicleRegistration()]['Longitude'], 3, '.', '');

                    $isLatEq = $orderLat === $vehicleLat;
                    $isLngEq = $orderLng === $vehicleLng;

                    if ($isLatEq && $isLngEq) {
                        $orderInfo['status'] = Route::ORDER_STATUS_DELIVERED;
                        $orderInfo['deliveryDate'] = $this->dateTime->formatDate(true);
                        $changedItemKey = $key;
                        $isDataChanged = true;
                        $ordersForSending[] = $orderInfo['order'];
                        $this->helper->logger->info('CRON : STATUS CHANGED : CAR TOUCH POINT : ' . $orderInfo['order']);
                        break;
                    }
                }

                if ($isDataChanged) {
                    foreach ($ordersInfo as $key => &$orderInfo) {
                        if ($changedItemKey === $key) break;
                        if (isset($orderInfo['status']) && $orderInfo['status'] === Route::ORDER_STATUS_DELIVERED) continue;
                        $ordersForSending[] = $orderInfo['order'];

                        $orderInfo['status'] = Route::ORDER_STATUS_DELIVERED;
                        $orderInfo['deliveryDate'] = $this->dateTime->formatDate(true);
                        $this->helper->logger->info('CRON : STATUS CHANGED : PREVIOUS POINT : ' . $orderInfo['order']);
                    }

                    $route->setOrderAddresses($this->helper->json->serialize($ordersInfo));
                    if (array_key_exists('status', end($ordersInfo)) && end($ordersInfo)['status'] === Route::ORDER_STATUS_DELIVERED)
                        $route->setStatus(Route::STATUS_COMPLETE);
                    try {
                        $this->routeRepository->save($route);
                        $this->helper->logger->info('CRON : Route saved : ' . $route->getId());
                    } catch (\Exception $e) {
                        $this->helper->logger->error('CRON : SAVING ORDER : ' . $e->getMessage());
                    }
                }

            }

            if (!empty($ordersForSending)) {
                try {
                    $orderForSendingObject = $this->ordersForSendingFactory->create();
                    $orderForSendingObject->setOrdersIds($ordersForSending);
                    $orderForSendingObject->setCreatedAt($this->dateTime->formatDate(true));
                    $this->ordersForSendingRepository->save($orderForSendingObject);
                } catch (AlreadyExistsException $e) {
                    $this->helper->logger->info('CRON : SAVING ORDERS FOR SENDING ERROR : ' . $e->getMessage());
                }
            }

        }

        return $this;
    }

    /**
     * @return false|mixed
     */
    public function getVehicles()
    {
        $result = false;

        $vehicles = $this->helper->json->unserialize(
            $this->helper->sendVehiclesRequest(Data::BASE_URL_US . Data::URL_VEHICLES)->getBody()
        )['Data'];

        $quartixResponse = $this->helper->sendVehiclesLiveRequest(Data::BASE_URL_US . Data::URL_VEHICLES_LIVE);
        $vehiclesLive = $this->helper->json->unserialize($quartixResponse->getBody());



		try {
			foreach ($vehicles as $vehicle) {
				foreach ($vehiclesLive['Data'] as $key => $vehicleLive) {
					if ($vehicle['VehicleId'] === $vehicleLive['VehicleId']) {
						$result[$vehicle['RegistrationNumber']] = $vehicleLive;
						unset($vehiclesLive[$key]);
						break;
					}
				}
			}
		} catch (\Exception $e) {
			$this->helper->logger->info(print_r($vehicles, true));
		}



        return $result;
    }
}
