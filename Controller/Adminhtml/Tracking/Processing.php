<?php
namespace Stagem\OrderMapTracking\Controller\Adminhtml\Tracking;

use Stagem\OrderMapTracking\Api\RouteRepositoryInterface;
use Stagem\OrderMapTracking\Model\Route;
use Stagem\OrderMapTracking\Model\RouteFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;

class Processing extends Action
{

    private RouteRepositoryInterface $routeRepository;

    private RouteFactory $routeFactory;

    /**
     * @param Context $context
     * @param RouteRepositoryInterface $routeRepository
     * @param RouteFactory $routeFactory
     */
    public function __construct(
        Context $context,
        RouteRepositoryInterface $routeRepository,
        RouteFactory $routeFactory
    ) {
        parent::__construct($context);
        $this->routeRepository = $routeRepository;
        $this->routeFactory = $routeFactory;
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
        $route->setStatus(Route::STATUS_PROCESSING);

        try {
            $this->routeRepository->save($route);
            $resultRedirect->setPath(
                '*/*/index'
            );
            $this->messageManager->addSuccessMessage(__('Route was set to processing.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Error. Cannot save : ' . $exception->getMessage()));
            $resultRedirect->setPath('*/*/index');
        }

        return $resultRedirect;
    }
}
