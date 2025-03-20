<?php

namespace Thao\CartAbandonment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;

class Rule implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @param CollectionFactory $ruleCollectionFactory
     */
    public function __construct(CollectionFactory $ruleCollectionFactory)
    {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        $ruleCollection = $this->ruleCollectionFactory->create();
        foreach ($ruleCollection as $rule) {
            $options[] = ['value' => $rule->getId(), 'label' => $rule->getName()];
        }

        return $options;
    }
}

