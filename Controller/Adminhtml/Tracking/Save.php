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

namespace Stagem\OrderMapTracking\Controller\Adminhtml\Tracking;
ini_set("memory_limit","-1");

use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Stagem\OrderMapTracking\Api\RouteRepositoryInterface;
use Stagem\OrderMapTracking\Helper\Data;
use Stagem\OrderMapTracking\Model\Route;
use Stagem\OrderMapTracking\Model\RouteFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime;

class Save extends Action implements HttpPostActionInterface
{
    const FIELD_REGISTRATION_NUMBER = 'Transport:';
    const FIELD_CUSTOMER_NUMBER = '№';
    const FIELD_ROUTE = 'Route:';

    /**
     * @var RouteRepositoryInterface
     */
    private RouteRepositoryInterface $routeRepository;

    /**
     * @var RouteFactory
     */
    private RouteFactory $routeFactory;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var Xls
     */
    private Xls $xlsReader;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @param Context $context
     * @param RouteRepositoryInterface $routeRepository
     * @param RouteFactory $routeFactory
     * @param Json $json
     * @param Xls $xlsReader
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        RouteRepositoryInterface $routeRepository,
        RouteFactory $routeFactory,
        Json $json,
        Xls $xlsReader,
        DateTime $dateTime
    ) {
        parent::__construct($context);
        $this->routeRepository = $routeRepository;
        $this->routeFactory = $routeFactory;
        $this->json = $json;
        $this->xlsReader = $xlsReader;
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

        if (!$request->isPost() || empty($this->getRequest()->getFiles('file'))) {
            $this->messageManager->addErrorMessage(__('Wrong request.'));
            $resultRedirect->setPath('*/*/index');
            return $resultRedirect;
        }

        $xls = $this->getRequest()->getFiles('file')['tmp_name'];
        $spreadsheet = $this->xlsReader->load($xls);

        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $ordersInfo = [];
        $addresses = false;
        foreach ($sheetData as $cell) {
            if ($addresses && empty($cell[0])) break;

            if ($cell[0] === self::FIELD_ROUTE) {
                $routeName = $cell[4];
                continue;
            }

            if ($cell[0] === self::FIELD_REGISTRATION_NUMBER) {
                $vehicleRegistrationNumber = $cell[4];
                continue;
            }

            if ($cell[0] === self::FIELD_CUSTOMER_NUMBER) {
                $addresses = true;
                continue;
            }

            if ($addresses) {
                $address   = urlencode($cell[4]);
                $api_key = Data::GOOGLE_MAPS_API_KEY;
                $url = "https://maps.google.com/maps/api/geocode/json?sensor=false&address={$address}&key={$api_key}";
                $resp_json = file_get_contents($url);
                $resp      = $this->json->unserialize($resp_json);

                if ($resp['status'] !== 'OK') {
                    continue;
                }

                $orderNumbersWords = explode(' ', $cell[2]);
                foreach ($orderNumbersWords as $orderNumberWord) {
                    if (preg_match('#\d#', $orderNumberWord)) {
                        $orderNumber = $orderNumberWord;
                        break;
                    }
                }

                $ordersInfo[] = [
                    'customer'      => $cell[1],
                    'order'         => $orderNumber ?? 'ORDER NUMBER NOT LOADED',
                    'address'       => $cell[4],
                    'position'      => [
                        'lat'   => $resp['results'][0]['geometry']['location']['lat'],
                        'lng'   => $resp['results'][0]['geometry']['location']['lng']
                    ],
                    'status'        => Route::ORDER_STATUS_PROCESSING,
                    'deliveryDate'  => NULL
                ];
            }
        }

        if (empty($vehicleRegistrationNumber) || empty($ordersInfo) || empty($routeName)) {
            $this->messageManager->addErrorMessage(__('Empty saved data.'));
            $resultRedirect->setPath('*/*/index');
            return $resultRedirect;
        }

        $route = $this->routeFactory->create();
        $route->setRoute($routeName);
        $route->setVehicleRegistration(trim($vehicleRegistrationNumber));
        $route->setOrderAddresses($this->json->serialize($ordersInfo));

        if ($route->isObjectNew() && !$route->getCreatedAt()) {
            $route->setCreatedAt($this->dateTime->formatDate(true));
        }
        $route->setUpdatedAt($this->dateTime->formatDate(true));

        if ($route->isObjectNew() && !$route->getStatus()) {
            $route->setStatus(Route::STATUS_PROCESSING);
        }

        try {
            $route = $this->routeRepository->save($route);
            $resultRedirect->setPath(
                '*/*/index'
            );
            $this->messageManager->addSuccessMessage(__('Route parameters was saved.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Error. Cannot save : ' . $exception->getMessage()));
            $resultRedirect->setPath('*/*/index');
        }

        return $resultRedirect;
    }
}
