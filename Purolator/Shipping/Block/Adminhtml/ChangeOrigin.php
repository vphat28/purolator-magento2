<?php

namespace Purolator\Shipping\Block\Adminhtml;

use Magento\Directory\Api\CountryInformationAcquirerInterface as CountryCollection;
use Magento\Framework\View\Element\Template;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Purolator\Shipping\Helper\Address;

class ChangeOrigin extends \Magento\Framework\View\Element\Template
{
    /** @var CountryCollection */
    private $countryCollection;

    /** @var RegionCollection */
    private $regionCollection;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var Address */
    private $helper;

    /**
     * ChangeOrigin constructor.
     * @param Template\Context $context
     * @param CountryCollection $countryCollection
     * @param RegionCollection $regionCollection
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CountryCollection $countryCollection,
        RegionCollection $regionCollection,
        OrderRepositoryInterface $orderRepository,
        Address $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->countryCollection = $countryCollection;
        $this->regionCollection = $regionCollection;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
    }

    public function getOriginSettings()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        $modifiedAddress = $this->helper->getModifiedOriginAddress($order);

        if (!empty($modifiedAddress)) {
            $countryId = $modifiedAddress['country_id'];
            $regionId = $modifiedAddress['region_id'];
            $postcode = $modifiedAddress['postcode'];
            $region = $modifiedAddress['region'];
            $city = $modifiedAddress['city'];
            $streetLine1 = $modifiedAddress['streetLine1'];
        } else {
            $countryId = $this->_scopeConfig->getValue('shipping/origin/country_id');
            $regionId = $this->_scopeConfig->getValue('shipping/origin/region_id');
            $region = $regionId;
            $postcode = $this->_scopeConfig->getValue('shipping/origin/postcode');
            $city = $this->_scopeConfig->getValue('shipping/origin/city');
            $streetLine1 = $this->_scopeConfig->getValue('shipping/origin/street_line1');
        }

        return [
            'countryCollection' => $this->countryCollection->getCountriesInfo(),
            'country' => $this->countryCollection->getCountryInfo($countryId),
            'regionId' => $regionId,
            'postcode' => $postcode,
            'city' => $city,
            'region' => $region,
            'streetLine1' => $streetLine1,
        ];
    }

    /**
     * @return RegionCollection
     */
    public function getRegionCollection()
    {
        return $this->regionCollection;
    }
}