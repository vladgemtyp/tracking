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

namespace Stagem\OrderMapTracking\Block;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Stagem\OrderMapTracking\Controller\Index\Ajax as AjaxController;
use Stagem\OrderMapTracking\Model\Route;

class Index extends Template
{
    /**
     * @var AjaxController
     */
    private AjaxController $ajaxController;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @param Context $context
     * @param AjaxController $ajaxController
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        AjaxController $ajaxController,
        Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->ajaxController = $ajaxController;
        $this->json = $json;
    }

    /**
     * @return string
     */
    public function getVehicleSvgUrl(): string
    {
        return $this->getViewFileUrl("Stagem_OrderMapTracking::images/largevan-red.svg");
    }

    /**
     * @return false|mixed
     */
    public function getOrderParam()
    {
        $order = false;
        if (!empty($this->getRequest()->getParam('orderId'))) {
            $order = $this->getRequest()->getParam('orderId');
        }

        return $order;
    }

    public function getStatusDelivered(): string
    {
        return Route::ORDER_STATUS_DELIVERED;
    }

    public function getStatusProcessing(): string
    {
        return Route::ORDER_STATUS_PROCESSING;
    }
}
