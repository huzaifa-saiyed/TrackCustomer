<?php

namespace Kitchen365\TrackCustomer\Model\ResourceModel;

class CustomerActivityLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('kitchen365_customer_track_activity', 'entity_id');
    }
}
