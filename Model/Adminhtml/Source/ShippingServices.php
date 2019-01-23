<?php

namespace Purolator\Shipping\Model\Adminhtml\Source;

class ShippingServices implements \Magento\Framework\Option\ArrayInterface
{
    protected $serviceList = [
        'PurolatorExpress9AM' => 1,
        'PurolatorExpress10:30AM' => 1,
        'PurolatorExpress12PM' => 1,
        'PurolatorExpress' => 1,
        'PurolatorExpressEvening' => 1,
        'PurolatorExpressEnvelope9AM' => 1,
        'PurolatorExpressEnvelope10:30AM' => 1,
        'PurolatorExpressEnvelope12PM' => 1,
        'PurolatorExpressEnvelope' => 1,
        'PurolatorExpressEnvelopeEvening' => 1,
        'PurolatorExpressPack9AM' => 1,
        'PurolatorExpressPack10:30AM' => 1,
        'PurolatorExpressPack12PM' => 1,
        'PurolatorExpressPack' => 1,
        'PurolatorExpressPackEvening' => 1,
        'PurolatorExpressBox9AM' => 1,
        'PurolatorExpressBox10:30AM' => 1,
        'PurolatorExpressBox12PM' => 1,
        'PurolatorExpressBox' => 1,
        'PurolatorExpressBoxEvening' => 1,
        'PurolatorGround' => 2,
        'PurolatorGround9AM' => 2,
        'PurolatorGround10:30AM' => 2,
        'PurolatorGroundEvening' => 2,
        'PurolatorExpressU.S.' => 3,
        'PurolatorExpressU.S.9AM' => 3,
        'PurolatorExpressU.S.10:30AM' => 3,
        'PurolatorExpressU.S.12:00' => 3,
        'PurolatorExpressEnvelopeU.S.' => 3,
        'PurolatorExpressU.S.Envelope9AM' => 3,
        'PurolatorExpressU.S.Envelope10:30AM' => 3,
        'PurolatorExpressU.S.Envelope12:00' => 3,
        'PurolatorExpressPackU.S.' => 3,
        'PurolatorExpressU.S.Pack9AM' => 3,
        'PurolatorExpressU.S.Pack10:30AM' => 3,
        'PurolatorExpressU.S.Pack12:00' => 3,
        'PurolatorExpressBoxU.S.' => 3,
        'PurolatorExpressU.S.Box9AM' => 3,
        'PurolatorExpressU.S.Box10:30AM' => 3,
        'PurolatorExpressU.S.Box12:00' => 3,
        'PurolatorGroundU.S.' => 4,
        'PurolatorExpressInternational' => 5,
        'PurolatorExpressInternational9AM' => 5,
        'PurolatorExpressInternational10:30AM' => 5,
        'PurolatorExpressInternational12:00' => 5,
        'PurolatorExpressEnvelopeInternational' => 5,
        'PurolatorExpressInternationalEnvelope9AM' => 5,
        'PurolatorExpressInternationalEnvelope10:30AM' => 5,
        'PurolatorExpressInternationalEnvelope12:00' => 5,
        'PurolatorExpressPackInternational' => 5,
        'PurolatorExpressInternationalPack9AM' => 5,
        'PurolatorExpressInternationalPack10:30AM' => 5,
        'PurolatorExpressInternationalPack12:00' => 5,
        'PurolatorExpressBoxInternational' => 5,
        'PurolatorExpressInternationalBox9AM' => 5,
        'PurolatorExpressInternationalBox10:30AM' => 5,
        'PurolatorExpressInternationalBox12:00' => 5,
    ];

    private $serviceGroup = [
        'purolatorexpress' => 1,
        'purolatorground' => 2,
        'purolatorexpressus' => 3,
        'purolatorgroundus' => 4,
        'purolatorexpressinternational' => 5,
    ];

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->serviceList as $k => $v) {
            $options[] = ['value' => $k, 'label' => $k];
        }

        return $options;
    }

    /**
     * @param $serviceId
     * @return mixed|null
     */
    public function getGroupFromService($serviceId)
    {
        $correctGroup = null;

        foreach ($this->serviceList as $service => $groupId) {
            if ($serviceId == $service) {
                $correctGroup = $groupId;
                break;
            }
        }

        foreach ($this->serviceGroup as $group => $id) {
            if ($correctGroup == $id) {
                $correctGroup = $group;
                break;
            }
        }

        return $correctGroup;
    }
}