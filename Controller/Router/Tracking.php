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

namespace Stagem\OrderMapTracking\Controller\Router;

use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;

class Tracking implements RouterInterface
{
    const ALIAS = 'tracking';

    const AJAX_ALIAS = 'ajax';

    /**
     * @var ActionFactory
     */
    protected ActionFactory $actionFactory;

    /**
     * CustomRouter constructor.
     *
     * @param ActionFactory $actionFactory
     */
    public function __construct(
        ActionFactory $actionFactory
    ) {
        $this->actionFactory = $actionFactory;
    }

    /**
     * Match Router
     *
     * @param RequestInterface $request
     * @return ActionInterface
     */
    public function match(RequestInterface $request)
    {
        $identifier = trim($request->getPathInfo(), '/');
        $paramsArr = explode('/', $identifier);

        if ($paramsArr[0] !== self::ALIAS || $request->getModuleName() === 'tracking') {
            return;
        }

        if ($paramsArr[0] === self::ALIAS) {
            $request->setModuleName('tracking')
            ->setControllerName('index')
            ->setActionName('index');
        }

        if (!empty($paramsArr[2]) && $paramsArr[2] === self::AJAX_ALIAS) {
            $request->setActionName('ajax');
        }


        if (!empty($paramsArr[1]) && $paramsArr[1] !== 'index') {
            $request->setParams(['orderId'  => $paramsArr[1]]);
        }

        return $this->actionFactory->create(Forward::class);
    }
}
