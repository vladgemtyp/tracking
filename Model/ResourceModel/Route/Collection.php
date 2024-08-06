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

namespace Stagem\OrderMapTracking\Model\ResourceModel\Route;

use Stagem\OrderMapTracking\Model\Route;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Stagem\OrderMapTracking\Model\ResourceModel\Route as RouteResource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Route::class, RouteResource::class);
    }
}
