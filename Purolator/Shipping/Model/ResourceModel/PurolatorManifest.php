<?php
namespace Purolator\Shipping\Model\ResourceModel;
class PurolatorManifest extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('ch_purolator_manifest', 'id');
    }
}
