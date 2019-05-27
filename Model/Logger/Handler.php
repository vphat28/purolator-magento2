<?php

namespace Purolator\Shipping\Model\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    protected $fileName = '/var/log/purolator.log';
    protected $loggerType = Logger::DEBUG;
}