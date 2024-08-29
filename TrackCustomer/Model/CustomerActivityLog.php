<?php

namespace Kitchen365\TrackCustomer\Model;

class CustomerActivityLog extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Kitchen365\TrackCustomer\Model\ResourceModel\CustomerActivityLog::class);
    }
}
