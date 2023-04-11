<?php
namespace Boxofhope\ReturnLabelPlugin\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

class ReturnLabel extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'boh_return_label';

    protected $_cacheTag = 'boh_return_label';

    protected $_eventPrefix = 'boh_return_label';

    protected function _construct()
    {
        $this->_init('Boxofhope\ReturnLabelPlugin\Model\ResourceModel\ReturnLabel');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}
