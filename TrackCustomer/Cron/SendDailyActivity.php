<?php

namespace Kitchen365\TrackCustomer\Cron;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Kitchen365\TrackCustomer\Model\ResourceModel\CustomerActivityLog\CollectionFactory;
use Psr\Log\LoggerInterface;

class SendDailyActivity
{
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $dateTime;
    protected $scopeConfig;
    protected $activityCollectionFactory;
    protected $logger;

    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $activityCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
        $this->activityCollectionFactory = $activityCollectionFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $startDate = $this->dateTime->gmtDate('Y-m-d 00:00:00');
            $endDate = $this->dateTime->gmtDate('Y-m-d 23:59:59');

            $collection = $this->activityCollectionFactory->create()
                ->addFieldToFilter('created_at', ['gteq' => $startDate])
                ->addFieldToFilter('created_at', ['lteq' => $endDate]);

            if ($collection->getSize()) {
                $this->sendEmail($collection->getData());
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in Send Email To Admin: ' . $e->getMessage());
        }
    }

    protected function sendEmail($results)
    {
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => 1);

        $adminEmail = $this->scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE);
        $adminName = $this->scopeConfig->getValue('trans_email/ident_general/name', ScopeInterface::SCOPE_STORE);

        $content = '';
        foreach ($results as $result) {
            $content .= "<tr>
                <td>{$result['field_name']}</td>
                <td>{$result['old_value']}</td>
                <td>{$result['new_value']}</td>
                <td>{$result['admin_user']}</td>
                <td>{$result['customer_id']}</td>
                <td>{$result['customer_email']}</td>
            </tr>";
        }

        $this->inlineTranslation->suspend();

        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('customer_activity_email_template')
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars(['content' => $content])
                ->setFrom([
                        'name' => $adminName,
                        'email' => $adminEmail
                    ])
                ->addTo([$adminEmail])
                ->getTransport();

            $transport->sendMessage();
            $this->logger->info('Email sent successfully to ' . $adminEmail);
        } catch (\Exception $e) {
            $this->logger->error('Error while sending email: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
