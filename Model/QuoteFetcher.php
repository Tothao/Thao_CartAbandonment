<?php

namespace Thao\CartAbandonment\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;

class QuoteFetcher
{
    protected $resourceConnection;
    protected $quoteCollectionFactory;

    public function __construct(
        ResourceConnection $resourceConnection,
        QuoteCollectionFactory $quoteCollectionFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
    }

    public function getQuotesForAbandonment()
    {
        $timeLimit = (new \DateTime())->modify('-24 hours')->format('Y-m-d H:i:s');

        $collection = $this->quoteCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', ['notnull' => true])
            ->addFieldToFilter('abandonment_status', 0)
            ->addFieldToFilter('updated_at', ['lt' => $timeLimit]);

        return $collection->getItems();
    }
}
