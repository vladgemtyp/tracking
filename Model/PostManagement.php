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

namespace Stagem\OrderMapTracking\Model;

use Stagem\OrderMapTracking\Api\RouteRepositoryInterface;
use Stagem\OrderMapTracking\Helper\Data;
use Stagem\OrderMapTracking\Helper\Helper;
use Stagem\OrderMapTracking\Model\RouteFactory;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Event\ManagerInterface;

class PostManagement
{
    const FIELD_ROUTE_NAME = 'route_id';
    const FIELD_VEHICLE_REGISTRATION = 'vehicle_registration';
    const FIELD_ORDERS = 'orders';
    const FIELD_ORDER = 'order';
    const FIELD_ORDER_ID = 'order_id';
    const FIELD_CUSTOMER = 'customer';
    const FIELD_ADDRESS = 'address';

    const ROUTE_FIELDS = [self::FIELD_ROUTE_NAME, self::FIELD_VEHICLE_REGISTRATION, self::FIELD_ORDERS];
    const ORDER_FIELDS = [self::FIELD_ORDER_ID, self::FIELD_CUSTOMER, self::FIELD_ADDRESS];

    /**
     * @var Helper
     */
    private Helper $helper;

    /**
     * @var RouteRepositoryInterface
     */
    private RouteRepositoryInterface $routeRepository;

    /**
     * @var RouteFactory
     */
    private RouteFactory $routeFactory;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    private ManagerInterface $eventManager;

    /**
     * @param Helper $helper
     * @param RouteFactory $routeFactory
     * @param RouteRepositoryInterface $routeRepository
     * @param DateTime $dateTime
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Helper $helper,
        RouteFactory $routeFactory,
        RouteRepositoryInterface $routeRepository,
        DateTime $dateTime,
        ManagerInterface $eventManager
    ) {
        $this->helper = $helper;
        $this->routeFactory = $routeFactory;
        $this->routeRepository = $routeRepository;
        $this->dateTime = $dateTime;
        $this->eventManager = $eventManager;
    }

    /**
     * @param $routes
     * @return bool|string
     */
    public function importRoutes($routes)
    {
        $this->helper->logger->info("Routes From 1C: " . $this->helper->json->serialize($routes));
        $result = [];
        $routesArray = isset($routes['route'][0]) ? $routes['route'] : $routes;
        foreach ($routesArray as $route) {
            $checkResult = $this->checkRoute($route);
            if (is_array($checkResult)) {
                $result = array_merge($result, $checkResult);
                continue;
            }

            $orders = $this->getPreparedOrders($route[self::FIELD_ORDERS][self::FIELD_ORDER]);

            $routeModel = $this->routeFactory->create();
            $routeModel->setRoute($route[self::FIELD_ROUTE_NAME]);
            $routeModel->setVehicleRegistration(trim($route[self::FIELD_VEHICLE_REGISTRATION]));
            $routeModel->setOrderAddresses($this->helper->json->serialize($orders));
            $routeModel->setCreatedAt($this->dateTime->formatDate(true));
            $routeModel->setUpdatedAt($this->dateTime->formatDate(true));
            $routeModel->setStatus(Route::STATUS_PROCESSING);

            try {
                $routeModel = $this->routeRepository->save($routeModel);
                $successTxt = sprintf('SUCCESS : Route with route_id %s was saved', $routeModel->getRoute());
                $result[] = $successTxt;
                $this->helper->logger->info($successTxt);
                $this->eventManager->dispatch('stagem_tracking_rest_route_save', ['orders' => $orders]);
            } catch (\Exception $exception) {
                $errorTxt = sprintf('ERROR : CANNOT SAVE ROUTE. ROUTE ID: \'%s\'', $route[self::FIELD_ROUTE_NAME]);
                $result[] = $errorTxt;
                $this->helper->logger->error($errorTxt);
            }
        }

        $resultJson = $this->helper->json->serialize($result);
        $this->helper->logger->info($resultJson);
        return $this->helper->json->serialize($resultJson);
    }

    /**
     * @param $route
     */
    protected function checkRoute($route)
    {
        $errors = [];
        foreach (self::ROUTE_FIELDS as $required_field) {
            if (!array_key_exists($required_field, $route)) {
                $errorTxt = sprintf('ERROR : \'%s\' not exist!', $required_field);;
                $errors[] = $errorTxt;
                $this->helper->logger->error($errorTxt);
            }
            if (empty($route[$required_field])) {
                $errorTxt = sprintf('ERROR : \'%s\' is empty!', $required_field);;
                $errors[] = $errorTxt;
                $this->helper->logger->error($errorTxt);
            }
        }

        if (array_key_exists(self::FIELD_ROUTE_NAME, $route)) {
            try {
                $routeModel = $this->routeRepository->getByRouteName($route[self::FIELD_ROUTE_NAME]);
                if ($routeModel->getId()) {
                    $errorTxt = sprintf('ERROR : ERROR : ROUTE WITH ID %s ALREADY EXIST!', self::FIELD_ORDERS);
                    $errors[] = $errorTxt;
                    $this->helper->logger->error($errorTxt);
                }
            } catch (\Exception $e) {
            }
        }

        if (array_key_exists(self::FIELD_ORDERS, $route) && !is_null($route[self::FIELD_ORDERS])) {
            if (!array_key_exists(self::FIELD_ORDER, $route[self::FIELD_ORDERS]) &&
                empty($route[self::FIELD_ORDERS][self::FIELD_ORDER])) {
                $errorTxt = sprintf('ERROR :%s is empty!', self::FIELD_ORDERS);
                $errors[] = $errorTxt;
                $this->helper->logger->error($errorTxt);
            }
        } else {
            $errorTxt = sprintf('ERROR :%s is empty!', self::FIELD_ORDERS);
            $errors[] = $errorTxt;
            $this->helper->logger->error($errorTxt);
        }

        if (!empty($route[self::FIELD_ORDERS][self::FIELD_ORDER])) {

            if (array_key_exists(self::FIELD_ORDER_ID, $route[self::FIELD_ORDERS][self::FIELD_ORDER])) {
                $route[self::FIELD_ORDERS][self::FIELD_ORDER] = [
                    $route[self::FIELD_ORDERS][self::FIELD_ORDER]
                ];
            }

            foreach ($route[self::FIELD_ORDERS][self::FIELD_ORDER] as $order) {
                foreach (self::ORDER_FIELDS as $required_field) {
                    if (!array_key_exists($required_field, $order)) {
                        $errorTxt = sprintf('ERROR : \'%s\' not exist!', $order);
                        $errors[] = $errorTxt;
                        $this->helper->logger->error($errorTxt);
                    }
                }
            }
        }

        if (!empty($errors)) return $errors;
        return true;
    }

    protected function getPreparedOrders($orders): array
    {

        if (array_key_exists(self::FIELD_ORDER_ID, $orders)) {
            $orders = [$orders];
        }

        foreach ($orders as $order) {
            $orderIdWords = explode(' ', $order[self::FIELD_ORDER_ID]);
            foreach ($orderIdWords as $orderIdWord) {
                if (preg_match('#\d#', $orderIdWord)) {
                    $orderId = $orderIdWord;
                    break;
                }
            }
            $orderId = $orderId ?? 'ORDER NUMBER NOT LOADED';

            $address = urlencode($order[self::FIELD_ADDRESS]);
            $api_key = Data::GOOGLE_MAPS_API_KEY;
            $url = "https://maps.google.com/maps/api/geocode/json?sensor=false&address={$address}&key={$api_key}";
            $resp_json = file_get_contents($url);
            $resp      = $this->helper->json->unserialize($resp_json);

            if ($resp['status'] !== 'OK') {
                $this->helper->logger->error(sprintf('ERROR : CANNOT GET COORDINATES FOR ORDER %s', $orderId));
                $resp['results'][0]['geometry']['location']['lat'] = NULL;
                $resp['results'][0]['geometry']['location']['lng'] = NULL;
            }

            $ordersInfo[] = [
                'customer'      => $order[self::FIELD_CUSTOMER],
                'order'         => $orderId,
                'address'       => $order[self::FIELD_ADDRESS],
                'position'      => [
                    'lat'   => $resp['results'][0]['geometry']['location']['lat'],
                    'lng'   => $resp['results'][0]['geometry']['location']['lng']
                ],
                'status'        => Route::ORDER_STATUS_PROCESSING
            ];
        }

        return $ordersInfo;
    }
}
