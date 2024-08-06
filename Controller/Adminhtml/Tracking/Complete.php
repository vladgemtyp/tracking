<?php
namespace Stagem\OrderMapTracking\Controller\Adminhtml\Tracking;

use Stagem\OrderMapTracking\Api\RouteRepositoryInterface;
use Stagem\OrderMapTracking\Model\Route;
use Stagem\OrderMapTracking\Model\RouteFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Stagem\OrderMapTracking\Helper\Helper;
use Magento\Framework\Stdlib\DateTime;

class Complete extends Action
{

    private RouteRepositoryInterface $routeRepository;

    private RouteFactory $routeFactory;

    private Helper $helper;

    private DateTime $dateTime;

    /**
     * @param Context $context
     * @param RouteRepositoryInterface $routeRepository
     * @param RouteFactory $routeFactory
     * @param Helper $helper
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        RouteRepositoryInterface $routeRepository,
        RouteFactory $routeFactory,
        Helper $helper,
        DateTime $dateTime
    ) {
        parent::__construct($context);
        $this->routeRepository = $routeRepository;
        $this->routeFactory = $routeFactory;
        $this->helper = $helper;
        $this->dateTime = $dateTime;
    }

    /**
     * @return ResultInterface
     * @throws Exception
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $request = $this->getRequest();

        $route = $this->routeRepository->get($request->getParams()['id']);
        $route->setStatus(Route::STATUS_COMPLETE);

        $orders =  $this->helper->json->unserialize($route->getOrderAddresses());
        foreach ($orders as $key => $order) {
            if ($order['status'] === Route::ORDER_STATUS_PROCESSING) {
                $orders[$key]['status'] = Route::ORDER_STATUS_DELIVERED;
                $orders[$key]['deliveryDate']  = $this->dateTime->formatDate(true);
            }
        }
        $route->setOrderAddresses($this->helper->json->serialize($orders));

        try {
            $this->routeRepository->save($route);
            $resultRedirect->setPath(
                '*/*/index'
            );
            $this->messageManager->addSuccessMessage(__('Route was completed.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Error. Cannot save : ' . $exception->getMessage()));
            $resultRedirect->setPath('*/*/index');
        }

        return $resultRedirect;
    }
}
