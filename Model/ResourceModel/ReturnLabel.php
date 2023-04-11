<?php
namespace Boxofhope\ReturnLabelPlugin\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class ReturnLabel
 * @package Boxofhope\ReturnLabelPlugin\Model\ResourceModel
 */
class ReturnLabel extends AbstractDb
{
    /**
     * Blog constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('boh_return_label', 'id');
    }
}
