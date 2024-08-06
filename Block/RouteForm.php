<?php
namespace Stagem\OrderMapTracking\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class RouteForm extends Template
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct
    (
        Template\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getFormAction(): string
    {
        return $this->getUrl('*/*/save', ['_secure' => true]);
    }
}
