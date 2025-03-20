<?php

namespace Thao\CartAbandonment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\SalesRule\Model\CouponGenerator;
class Data extends AbstractHelper
{
    /**
     * @var CouponGenerator
     */
    protected $couponGenerator;

    /**
     * @param Context $context
     * @param CouponGenerator $couponGenerator
     */
    public function __construct(
        Context $context,
        CouponGenerator $couponGenerator,
    ) {
        $this->couponGenerator = $couponGenerator;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function isEnable( )
    {
        return $this->scopeConfig->getValue(
            'cart_abandonment/general/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );
    }

    /**
     * @return mixed
     */
    public function getFilterTime()
    {
        return $this->scopeConfig->getValue(
            'cart_abandonment/general/filter_time',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );
    }

    /**
     * @return mixed
     */
    public function getCartAbandonmentRuleId()
    {
        return $this->scopeConfig->getValue(
            'cart_abandonment/general/discount_code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

    }

    /**
     * @return mixed
     */
    public function getEmailTemplate()
    {
        return $this->scopeConfig->getValue(
            'cart_abandonment/general/email_template',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $ruleId
     * @return string[]
     */
    public function autoGerenateCouponCode($ruleId)
    {
        $data = array('rule_id' => $ruleId, 'qty' => '1', 'length' => '12', 'format' => 'alphanum');
        $code = $this->couponGenerator->generateCodes($data);
        return $code;
    }

}

