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
use Stagem\OrderMapTracking\Ui\Component\Control\Route\GenericButton;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getButtonData()
    {
        if ($this->getRoute()) {
            return [
                'id' => 'delete',
                'label' => __('Delete'),
                'on_click' => "deleteConfirm('" .__('Are you sure you want to delete this route?') ."', '"
                    . $this->getUrl('*/*/delete', ['id' => $this->getRoute()]) . "', {data: {}})",
                'class' => 'delete',
                'sort_order' => 10
            ];
        }
        return [];
    }
}
