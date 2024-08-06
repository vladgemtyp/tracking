<?php

namespace Stagem\OrderMapTracking\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Stagem\OrderMapTracking\Api\Data\RouteInterface;
use Stagem\OrderMapTracking\Api\RouteRepositoryInterface;
use Stagem\OrderMapTracking\Api\RouteSearchResultInterface;
use Stagem\OrderMapTracking\Model\ResourceModel\Route\CollectionFactory;
use Stagem\OrderMapTracking\Model\ResourceModel\Route as RouteResource;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaInterface;
use Stagem\OrderMapTracking\Api\RouteSearchResultInterfaceFactory;

class RouteRepository implements RouteRepositoryInterface
{
    private CollectionFactory $collectionFactory;
    private RouteResource $routeResource;
    private RouteFactory $routeFactory;
    private RouteSearchResultInterfaceFactory $searchResultFactory;

    /**
     * @param RouteFactory $routeFactory
     * @param CollectionFactory $collectionFactory
     * @param RouteResource $routeResource
     * @param RouteSearchResultInterfaceFactory $searchResultInterfaceFactory
     */
    public function __construct(
        RouteFactory $routeFactory,
        CollectionFactory $collectionFactory,
        RouteResource  $routeResource,
        RouteSearchResultInterfaceFactory $searchResultInterfaceFactory
    ) {
        $this->routeFactory = $routeFactory;
        $this->collectionFactory = $collectionFactory;
        $this->routeResource = $routeResource;
        $this->searchResultFactory = $searchResultInterfaceFactory;
    }

    /**
     * @param int $id
     * @return RouteInterface
     * @throws NoSuchEntityException
     */
    public function get(int $id): RouteInterface
    {
        $object = $this->routeFactory->create();
        $this->routeResource->load($object, $id);
        $a = $object->getData('entity_id');
        if (!$object->getData('entity_id')) {
            throw new NoSuchEntityException(__('Unable to find entity with ID "%1"', $id));
        }
        return $object;
    }

    public function getByRouteName(string $routeName): RouteInterface
    {
        $object = $this->routeFactory->create();
        $this->routeResource->load($object, $routeName, RouteInterface::ROUTE);
        $a = $object->getData('entity_id');
        if (!$object->getData('entity_id')) {
            throw new NoSuchEntityException(__('Unable to find entity with route "%1"', $routeName));
        }
        return $object;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return RouteSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RouteSearchResultInterface
    {
        $collection = $this->collectionFactory->create();
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $searchResult = $this->searchResultFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        return $searchResult;
    }

    /**
     * @param RouteInterface $route
     * @return RouteInterface
     * @throws AlreadyExistsException
     */
    public function save(RouteInterface $route): RouteInterface
    {
        $this->routeResource->save($route);
        return $route;
    }

    /**
     * @param RouteInterface $route
     * @return bool
     */
    public function delete(RouteInterface $route): bool
    {
        try {
            $this->routeResource->delete($route);
        } catch (\Exception $e) {
            throw new StateException(__('Unable to remove entity #%1', $route->getId()));
        }
        return true;
    }
}
