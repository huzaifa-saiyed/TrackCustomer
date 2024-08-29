<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Kitchen365\TrackCustomer\Controller\Adminhtml\Customers;


use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Kitchen365\TrackCustomer\Model\CustomerActivityLogFactory;
use Kitchen365\TrackCustomer\Model\ResourceModel\CustomerActivityLog as GalleryResourceModel;

class InlineEdit extends \Magento\Backend\App\Action
{
    protected $jsonFactory;
    private $customerActivityLogFactory;
    private $galleryResourceModel;

    public function __construct(
        Context $context,
        CustomerActivityLogFactory $customerActivityLogFactory,
        JsonFactory $jsonFactory,
        GalleryResourceModel $galleryResourceModel
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->customerActivityLogFactory = $customerActivityLogFactory;
        $this->galleryResourceModel = $galleryResourceModel;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if($this->getRequest()->getParam('isAjax')){
            $postItems = $this->getRequest()->getParam('items', []);
            if(!count($postItems)){
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $entityId) {
                $varInlineEdit = $this->customerActivityLogFactory->create();
                $this->galleryResourceModel->load($varInlineEdit, $entityId);
                $varInlineEdit->setData(array_merge($varInlineEdit->getData(), $postItems[$entityId]));
                $this->galleryResourceModel->save($varInlineEdit);
                }
            }
        }

        return $resultJson->setData(
            [
                'messages' => $messages,
                'error' => $error
            ]
        );
    }
}