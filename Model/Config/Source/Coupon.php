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
        foreach ($ruleCollection as $rule) {
            $options[] = [
                'value' => $rule->getId(),
                'label' => $rule->getName()
            ];
        }

        return $options;
    }
}

