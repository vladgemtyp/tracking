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

namespace Stagem\OrderMapTracking\Helper;

use Stagem\OrderMapTracking\Model\Route;

class Data
{
    const GOOGLE_MAPS_API_KEY = 'AIzaSyAXLGek78kcqWSls6NFcOLmy9jO4WV-Cpw';

    const CUSTOMER_ID = 'UnitedPorte INC';
    const USER_NAME = 'tracking';
    const PASSWORD = 'UnitedPorteINC2023!!!!!!';
    const APPLICATION = 'UnitedPorte INC.app';

    public const BASE_URL_US = 'https://qws.quartix.com/v2/api';
    public const URL_AUTH = '/auth';
    public const URL_VEHICLES = '/vehicles';
    public const URL_VEHICLES_LIVE = '/vehicles/live';

    const TOKENS_TXT = 'order-map-tracking/quartix.txt';

    public const STOREHOUSE = [
        'customer'      => 'Storehouse',
        'order'         => '#',
        'address'       =>  '3045 Richmond Terrace, Staten Island, NY 10303',
        'position'      => [
            'lat'   => 40.63843093741179,
            'lng'   => -74.1615415017282
        ],
        'status'        => Route::ORDER_STATUS_STOREHOUSE
    ];
}
