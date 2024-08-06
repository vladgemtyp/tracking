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

namespace Stagem\OrderMapTracking\Ui\Component\Control\Route;

use Magento\Framework\Exception\NoSuchEntityException;
use Stagem\OrderMapTracking\Model\Route;
use Stagem\OrderMapTracking\Model\RouteRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

class GenericButton
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var RouteRepository
     */
    private RouteRepository $routeRepository;

    /**
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     * @param RouteRepository $routeRepository
     */
    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request,
        RouteRepository $routeRepository
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->routeRepository = $routeRepository;
    }

    /**
     * @param $route
     * @param $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }

    /**
     * @return int|mixed|null
     * @throws NoSuchEntityException
     */
    public function getRoute()
    {
        $routeId = $this->request->getParam('id');
        if ($routeId === null) {
            return 0;
        }
        $route = $this->routeRepository->get($routeId);

        return $route->getId() ?: null;
    }

    /**
     * @return bool|int
     * @throws NoSuchEntityException
     */
    public function routeIsComplete()
    {
        $routeId = $this->request->getParam('id');
        if ($routeId === null) {
            return 0;
        }
        $route = $this->routeRepository->get($routeId);

        return $route->getStatus() === Route::STATUS_COMPLETE;
    }

}
