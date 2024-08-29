<?php

namespace Kitchen365\TrackCustomer\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Kitchen365\TrackCustomer\Model\CustomerActivityLogFactory;
use Kitchen365\TrackCustomer\Model\ResourceModel\CustomerActivityLog as ActivityLogResource;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Customer\Api\GroupRepositoryInterface;

class CustomerSavePlugin
{
    protected $adminSession;
    protected $activityLogFactory;
    protected $activityLogResource;
    protected $dateTime;
    protected $groupRepository;
    protected $customerRepository;
    protected $oldData = [];

    public function __construct(
        AdminSession $adminSession,
        CustomerActivityLogFactory $activityLogFactory,
        ActivityLogResource $activityLogResource,
        DateTime $dateTime,
        GroupRepositoryInterface $groupRepository,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->adminSession = $adminSession;
        $this->activityLogFactory = $activityLogFactory;
        $this->activityLogResource = $activityLogResource;
        $this->dateTime = $dateTime;
        $this->groupRepository = $groupRepository;
        $this->customerRepository = $customerRepository;
    }

    public function beforeSave(CustomerRepositoryInterface $subject, $customer)
    {
        $customerId = $customer->getId();
        if ($customerId) {
            $oldCustomer = $this->customerRepository->getById($customerId);
            $this->oldData = [
                'email' => $oldCustomer->getEmail(),
                'taxvat' => $oldCustomer->getTaxvat(),
                'group_id' => $oldCustomer->getGroupId(),
            ];

            $group = $this->groupRepository->getById($this->oldData['group_id']);
            $this->oldData['group_id'] = $group->getCode();
        }

        return [$customer];
    }

    public function afterSave(CustomerRepositoryInterface $subject, $customer)
    {
        // Fetch the actual group name for the new data
        $newGroup = $this->groupRepository->getById($customer->getGroupId());
        $newData = [
            'email' => $customer->getEmail(),
            'taxvat' => $customer->getTaxvat(),
            'group_id' => $newGroup->getCode(),
        ];

        $fieldsToCheck = ['email', 'taxvat', 'group_id'];

        foreach ($fieldsToCheck as $field) {
            if (isset($this->oldData[$field]) && $this->oldData[$field] != $newData[$field]) {
                $activityLog = $this->activityLogFactory->create();
                $activityLog->setFieldName(ucfirst(str_replace('_', ' ', $field)));
                $activityLog->setOldValue($this->oldData[$field]);
                $activityLog->setNewValue($newData[$field]);
                $activityLog->setAdminUser($this->adminSession->getUser()->getUsername());
                $activityLog->setCustomerId($customer->getId());
                $activityLog->setCustomerEmail($customer->getEmail());
                $activityLog->setDate($this->dateTime->gmtDate());

                $this->activityLogResource->save($activityLog);
            }
        }

        return $customer;
    }
}
