<?php
namespace Thao\CartAbandonment\Cron;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Quote\Api\CartRepositoryInterface;



class CartAbandonment{
    protected $quoteCollectionFactory;
    protected $transportBuilder;
    protected $scopeConfig;
    protected $inlineTranslation;
    protected $quoteRepository;


    public function __construct(
        CollectionFactory $quoteCollectionFactory,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        StateInterface $inlineTranslation,
        CartRepositoryInterface $quoteRepository,  // Inject CartRepositoryInterface


    ) {
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->inlineTranslation = $inlineTranslation;
        $this->quoteRepository = $quoteRepository;  // Inject CartRepositoryInterface

    }
    public function execute(){
        $isCronEnabled = $this->scopeConfig->isSetFlag(
            'CartAbandonment/general/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if($isCronEnabled){
            $filterTime = $this->scopeConfig->getValue(
                'CartAbandonment/general/filter_time',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $quoteCollection = $this->quoteCollectionFactory->create();
            $timeLimit = (New \DateTime())->modify("-". $filterTime ." hours")->format('Y-m-d H:i:s');
            $quoteCollection->addFieldToFilter('abandonment_status', 0);
            $quoteCollection->addFieldToFilter('customer_email', ['notnull' => true]);
            $quoteCollection->addFieldToFilter('updated_at', ['lt' => $timeLimit]);
            if (!$quoteCollection->getSize()) {
                return;
            }

            foreach ($quoteCollection as $quote) {
                $this->sendAbandonmentEmail($quote);
            }
        }
        }



    protected function sendAbandonmentEmail($quote){
        $customerEmail = $quote->getCustomerEmail();
        $customerName = $quote->getCustomerFirstname().' '.$quote->getCustomerLastname();
        $senderEmail = $this->scopeConfig
            ->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $senderName = $this->scopeConfig
            ->getValue('trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $couponCode = $this->scopeConfig->getValue(
            'CartAbandonment/general/discount_code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        try {
            $this->inlineTranslation->suspend();
            $sender = [
                'name' => $senderName,
                'email' => $senderEmail,
            ];
            $templateType = $this->scopeConfig->getValue('CartAbandonment/general/email_template', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateType)
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )

                ->setTemplateVars([
                    'customer_name' => $customerName,
                    'quote_id' => $quote->getId(),
                    'coupon_code'=> $couponCode
                ])
                ->setFrom($sender)
                ->addTo($customerEmail)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $quote->setAbandonmentStatus(1);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        return $this;
    }
}
