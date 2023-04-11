<?php
namespace Boxofhope\ReturnLabelPlugin\Model\ResourceModel\ReturnLabel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'boh_return_label_collection';
    protected $_eventObject = 'boh_return_label_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Boxofhope\ReturnLabelPlugin\Model\ReturnLabel',
            'Boxofhope\ReturnLabelPlugin\Model\ResourceModel\ReturnLabel');
    }
}
