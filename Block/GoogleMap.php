<?php
namespace Stagem\OrderMapTracking\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Stagem\OrderMapTracking\Api\RouteRepositoryInterface;
use Stagem\OrderMapTracking\Helper\Data;
use Stagem\OrderMapTracking\Helper\Helper;
use Stagem\OrderMapTracking\Model\Route;
use Magento\Framework\Serialize\Serializer\Json;

class GoogleMap extends Template
{
    /**
     * @var int
     */
    public int $routeId;

    /**
     * @var Helper
     */
    public Helper $helper;

    /**
     * @var RouteRepositoryInterface
     */
    public RouteRepositoryInterface $routeRepository;

    /**
     * @var Json
     */
    public Json $json;

    /**
     * @param Context $context
     * @param Helper $helper
     * @param RouteRepositoryInterface $routeRepository
     * @param Json $json
     * @param array $data
     */
    public function __construct
    (
        Template\Context $context,
        Helper $helper,
        RouteRepositoryInterface $routeRepository,
        Json $json,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->routeRepository = $routeRepository;
        $this->json = $json;
        $this->routeId = (int)$this->getRequest()->getParam('id');
    }

    /**
     * @return bool|\Exception|string
     */
    public function getVehicle()
    {
        $result = false;

        try {
            $route = $this->routeRepository->get($this->routeId);
        } catch (\Exception $e) {
            return $e;
        }

        $vehicles = $this->helper->json->unserialize(
            $this->helper->sendVehiclesRequest(Data::BASE_URL_US . Data::URL_VEHICLES)->getBody()
        )['Data'];

        if (is_array($vehicles)) {
            foreach ($vehicles as $vehicle) {
                if ($route->getVehicleRegistration() === $vehicle['RegistrationNumber']) {
                    $quartixResponse = $this->helper->sendVehiclesLiveRequest(Data::BASE_URL_US . Data::URL_VEHICLES_LIVE);
                    if ($quartixResponse) {
                        $vehiclesLive = $this->helper->json->unserialize($quartixResponse->getBody());
                        foreach ($vehiclesLive['Data'] as $vehicleLive) {
                            if ($vehicle['VehicleId'] === $vehicleLive['VehicleId']) {
                                $result = $vehicleLive;
                                break;
                            }
                        }
                    }
                    break;
                }
            }
        }

        return $this->json->serialize($result);
    }

    public function getVehicleSvgUrl(): string
    {
        return $this->getViewFileUrl("Stagem_OrderMapTracking::images/largevan-red.svg");
    }

    /**
     * @return \Exception|string
     */
    public function getAddresses()
    {
        try {
            $route = $this->routeRepository->get($this->routeId);
        } catch (\Exception $e) {
            return $e;
        }
        $addresses = $this->helper->json->unserialize($route->getOrderAddresses());
        foreach ($addresses as &$address) {
            if (!array_key_exists('status', $address)) {
                $address['status'] = NULL;
            }
        }
        array_unshift($addresses, Data::STOREHOUSE);
        return $this->helper->json->serialize($addresses);
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        try {
            $route = $this->routeRepository->get($this->routeId);
        } catch (\Exception $e) {
            return $e;
        }
        return $route->getCreatedAt();
    }

    public function getOrderDeliveredStatus(): string
    {
        return Route::ORDER_STATUS_DELIVERED;
    }

    public function getOrderStorehouseStatus(): string
    {
        return Route::ORDER_STATUS_STOREHOUSE;
    }

    public function getStatusUrl($order, $status): string
    {
        return $this->getUrl('*/*/order', [
            'id'        =>  $this->routeId,
            'order'     => $order,
            'status'    => $status
        ]);
    }

    public function getOrderStatusList(): array
    {
        return [
            Route::ORDER_STATUS_DELIVERED   => 'Delivered',
            Route::ORDER_STATUS_PROCESSING  => 'Processing'
        ];
    }
}
