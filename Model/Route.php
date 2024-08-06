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

namespace Stagem\OrderMapTracking\Model;

use Stagem\OrderMapTracking\Api\Data\RouteInterface;
use Magento\Framework\Model\AbstractModel;

class Route extends AbstractModel implements RouteInterface
{
    const STATUS_PROCESSING = 'processing';

    const STATUS_COMPLETE = 'complete';

    const ORDER_STATUS_DELIVERED = 'delivered';

    const ORDER_STATUS_PROCESSING = 'processing';

    const ORDER_STATUS_STOREHOUSE = 'storehouse';

    protected function _construct()
    {
        $this->_init('Stagem\OrderMapTracking\Model\ResourceModel\Route');
    }

    public function getRoute(): string
    {
        return $this->getData(RouteInterface::ROUTE);
    }

    public function setRoute(string $route): void
    {
        $this->setData(RouteInterface::ROUTE, $route);
    }

    public function getVehicleRegistration(): string
    {
        return $this->getData(RouteInterface::VEHICLE_REGISTRATION);
    }

    public function setVehicleRegistration(string $vehicleRegistration): void
    {
        $this->setData(RouteInterface::VEHICLE_REGISTRATION, $vehicleRegistration);
    }

    public function getOrderAddresses(): string
    {
        return $this->getData(RouteInterface::ORDER_ADDRESSES);
    }

    public function setOrderAddresses(string $orderAddresses): void
    {
        $this->setData(RouteInterface::ORDER_ADDRESSES, $orderAddresses);
    }

    public function getCreatedAt()
    {
        return $this->getData(RouteInterface::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->setData(RouteInterface::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt()
    {
        return $this->getData(RouteInterface::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): void
    {
        $this->setData(RouteInterface::UPDATED_AT, $updatedAt);
    }

    public function getStatus()
    {
        return $this->getData(RouteInterface::STATUS);
    }

    public function setStatus(string $status): void
    {
        $this->setData(RouteInterface::STATUS, $status);
    }
}
