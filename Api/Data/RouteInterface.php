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

namespace Stagem\OrderMapTracking\Api\Data;

interface RouteInterface
{
    const ID = 'entity_id';

    const ROUTE = 'route';

    const VEHICLE_REGISTRATION = 'vehicle_registration';

    const ORDER_ADDRESSES = 'order_addresses';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    const STATUS = 'status';

    /**
     * @return string
     */
    public function getVehicleRegistration(): string;

    /**
     * @param string $vehicleRegistration
     * @return void
     */
    public function setVehicleRegistration(string $vehicleRegistration): void;

    /**
     * @return string
     */
    public function getRoute(): string;

    /**
     * @param string $route
     * @return void
     */
    public function setRoute(string $route): void;


    /**
     * @return string
     */
    public function getOrderAddresses(): string;

    /**
     * @param string $orderAddresses
     * @return void
     */
    public function setOrderAddresses(string $orderAddresses): void;

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return void
     */
    public function setCreatedAt(string $createdAt): void;

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return void
     */
    public function setUpdatedAt(string $updatedAt): void;

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @param string $status
     * @return void
     */
    public function setStatus(string $status): void;
}
