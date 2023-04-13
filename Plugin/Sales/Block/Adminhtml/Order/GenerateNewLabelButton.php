<?php
namespace Boxofhope\ReturnLabelPlugin\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;
use Boxofhope\ReturnLabelPlugin\Model\ReturnLabel;
use Boxofhope\ReturnLabelPlugin\Model\ReturnLabelFactory;
use Psr\Log\LoggerInterface;

class GenerateNewLabelButton
{
    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Registry */
    protected $_coreRegistry = null;

    /** @var Curl */
    protected Curl $curl;
    protected ReturnLabelFactory $returnLabelFactory;

    protected ReturnLabel $label;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        ReturnLabelFactory $returnLabelFactory,
        ReturnLabel $label
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->returnLabelFactory = $returnLabelFactory;
        $this->label = $label;
    }

    public function beforeSetLayout(OrderView $subject)
    {
        $orderId = $subject->getOrder()->getEntityId();
        $labelId = $this->returnLabelFactory->create()->load($orderId, 'order_id');

        if (!$labelId->getEntityId()) {
            $subject->addButton(
                'boh_return_label_generate_label_button',
                [
                    'label' => __('Generate BoxOfHope label'),
                    'class' => __('boh_return_label_generate_label_button') . ' action-secondary',
                    'id' => 'order-view-boh-return-label-generate-label-button',
                    'onclick' => 'setLocation(\'' . $subject->getUrl('boxofhope_returnlabelplugin/order/BohDeliveryController') . '\'); document.getElementById(\'order-view-boh-return-label-generate-label-button\').disabled = true;'
                ]
            );
        } else {
            $subject->addButton(
                'boh_return_label_get_label',
                [
                    'label' => __('Download BoxOfHope label'),
                    'class' => __('boh_return_label_get_label_button') . ' action-secondary',
                    'id' => 'order-view-boh-return-label-get-label-button',
                    'onclick' => 'setLocation(\'' . $subject->getUrl('boxofhope_returnlabelplugin/order/BohLabelController') . '\')'
                ]
            );
        }
    }
}
