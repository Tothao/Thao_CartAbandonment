<?php

namespace Thao\CartAbandonment\Cron;

use DateTime;
use Exception;
use Magento\Framework\App\Area;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\SalesRule\Model\CouponGenerator;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class CartAbandonment
{
    public const ABANDONMENT_STATUS_NOT_SEND = 0;
    public const ABANDONMENT_STATUS_SEND = 1;
    /**
     * @var CollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var CouponGenerator
     */
    protected $couponGenerator;

    protected $helper;

    /**
     * @param CollectionFactory $quoteCollectionFactory
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param StateInterface $inlineTranslation
     * @param CartRepositoryInterface $quoteRepository
     * @param RuleFactory $ruleFactory
     * @param CouponGenerator $couponGenerator
     */
    public function __construct(
        CollectionFactory $quoteCollectionFactory,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        StateInterface $inlineTranslation,
        CartRepositoryInterface $quoteRepository,
        RuleFactory $ruleFactory,
        CouponGenerator $couponGenerator,
        \Thao\CartAbandonment\Helper\Data $helper
    ) {
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->inlineTranslation = $inlineTranslation;
        $this->quoteRepository = $quoteRepository;
        $this->ruleFactory = $ruleFactory;
        $this->couponGenerator = $couponGenerator;
        $this->helper = $helper;

    }

    public function execute()
    {
        $isCronEnabled = $this->helper->isEnable();

        if ($isCronEnabled) {
            $filterTime = $this->helper->getFilterTime();

            $quoteCollection = $this->quoteCollectionFactory->create();
            $timeLimit = (new DateTime())->modify("-" . $filterTime . " hours")->format('Y-m-d H:i:s');
            $quoteCollection->addFieldToFilter('abandonment_status', self::ABANDONMENT_STATUS_NOT_SEND);
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

    protected function sendAbandonmentEmail($quote)
    {
        $customerEmail = $quote->getCustomerEmail();
        $customerName = $quote->getCustomerFirstname() . ' ' . $quote->getCustomerLastname();
        $senderEmail = $this->scopeConfig
            ->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE);
        $senderName = $this->scopeConfig
            ->getValue('trans_email/ident_general/name', ScopeInterface::SCOPE_STORE);
        $ruleId = $this->helper->getCartAbandonmentRuleId();

        $couponCode = $this->helper->autoGerenateCouponCode($ruleId);

        $templateType = $this->helper->getEmailTemplate();
        try {
            $this->inlineTranslation->suspend();
            $sender = ['name' => $senderName, 'email' => $senderEmail,];

            $transport = $this->transportBuilder->setTemplateIdentifier($templateType)
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => Store::DEFAULT_STORE_ID,])
                ->setTemplateVars(
                    ['customer_name' => $customerName, 'quote_id' => $quote->getId(), 'coupon_code' => $couponCode]
                )
                ->setFrom($sender)
                ->addTo($customerEmail)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $quote->setAbandonmentStatus(self::ABANDONMENT_STATUS_SEND);
            $this->quoteRepository->save($quote);
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }

        return $this;
    }
}
