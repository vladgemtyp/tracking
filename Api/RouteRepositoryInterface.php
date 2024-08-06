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

namespace Stagem\OrderMapTracking\Api;

use Stagem\OrderMapTracking\Api\Data\RouteInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface RouteRepositoryInterface
{
    /**
     * @param int $id
     * @return RouteInterface
     */
    public function get(int $id): RouteInterface;

    /**
     * @param string $routeName
     * @return RouteInterface
     */
    public function getByRouteName(string $routeName): RouteInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return RouteSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RouteSearchResultInterface;

    /**
     * @param RouteInterface $route
     * @return RouteInterface
     */
    public function save(RouteInterface $route): RouteInterface;

    /**
     * @param RouteInterface $route
     * @return bool
     */
    public function delete(RouteInterface $route): bool;
}
