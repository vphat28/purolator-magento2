<?php
namespace Purolator\Shipping\Api;

use Purolator\Shipping\Api\Data\PurolatorShipmentInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface PurolatorShipmentRepositoryInterface 
{
    public function save(PurolatorShipmentInterface $page);

    public function getById($id);

    public function getList(SearchCriteriaInterface $criteria);

    public function delete(PurolatorShipmentInterface $page);

    public function deleteById($id);
}
