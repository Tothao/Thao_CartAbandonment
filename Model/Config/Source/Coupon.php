<?php
namespace Thao\CartAbandonment\Model\Config\Source;
use Magento\Framework\Option\ArrayInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection as RuleCollection;
Class Coupon implements ArrayInterface {
    protected $ruleCollectionFactory;
    public function __construct(CollectionFactory $ruleCollectionFactory) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }
    public function toOptionArray() {
        $options = [];

        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->getSelect()->joinLeft(
            ['src' => 'salesrule_coupon'],   // Alias cho bảng salesrule_coupon
            'main_table.rule_id = src.rule_id AND src.is_primary = 1', // Điều kiện join
            ['code']  // Chỉ lấy cột "code" từ bảng salesrule_coupon
        );
        $ruleCollection ->addFieldToFilter('is_active',1);
        foreach ($ruleCollection as $rule) {
            $couponCode = $rule->getData('code');
            $options[] = [
                'value' => $couponCode ?: '',
                'label' => $rule->getName()
            ];
        }

        return $options;  // return array of options
    }
}

