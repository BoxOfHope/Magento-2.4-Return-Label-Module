<?php

namespace Boxofhope\ReturnLabelPlugin\Block\Admin\Order\View\Tab;

use Boxofhope\ReturnLabelPlugin\Model\ReturnLabel;
use Boxofhope\ReturnLabelPlugin\Model\ReturnLabelFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;

class BoxOfHopeReturnLabelTab extends Template implements TabInterface
{
    protected $_template = 'order/view/tab/boxofhopereturnlabel.phtml';
    private Registry $_coreRegistry;
    protected ReturnLabelFactory $returnLabelFactory;
    protected ReturnLabel $label;

    /**
     * View constructor.
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ReturnLabelFactory $returnLabelFactory,
        ReturnLabel $label,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->returnLabelFactory = $returnLabelFactory;
        $this->label = $label;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }
    /**
     * Retrieve order model instance
     *
     * @return int
     *Get current id order
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }

    public function isOrderHasBohReturnLabel() :bool
    {
        $returnLabel = $this->returnLabelFactory->create()->load($this->getOrderId(), 'order_id');
        return (bool)$returnLabel->getEntityid();
    }

    public function getBohReturnLabel()
    {
        return $this->returnLabelFactory->create()->load($this->getOrderId(), 'order_id');
    }

    public function getBohReturnLabelDeliveryId() :string
    {
        return $this->getBohReturnLabel()->getDeliveryId();
    }

    public function getBohReturnLabelReturnCode() :string
    {
        return $this->getBohReturnLabel()->getReturnCode();
    }

    /**
     * Retrieve order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Collaboration with BoxOfHope - label');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Collaboration with BoxOfHope - label');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
