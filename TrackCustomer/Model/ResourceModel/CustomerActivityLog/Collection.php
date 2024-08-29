<?php

namespace Kitchen365\TrackCustomer\Model\ResourceModel\CustomerActivityLog;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Kitchen365\TrackCustomer\Model\CustomerActivityLog::class, 
            \Kitchen365\TrackCustomer\Model\ResourceModel\CustomerActivityLog::class);
    }
}
