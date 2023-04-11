<?php

namespace Boxofhope\ReturnLabelPlugin\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Boxofhope\ReturnLabelPlugin\Model\ReturnLabel;
use Boxofhope\ReturnLabelPlugin\Model\ReturnLabelFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Response\Http\FileFactory;

class BohLabelController extends Action
{
    protected OrderRepositoryInterface $orderRepository;
    protected LoggerInterface $logger;

    protected Registry|null $_coreRegistry = null;

    protected Curl $curl;
    protected ReturnLabelFactory $returnLabelFactory;
    protected ReturnLabel $label;
    public ScopeConfigInterface $scopeConfig;
    protected FileFactory $fileFactory;

    public function __construct(
        Action\Context           $context,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface          $logger,
        Curl                     $curl,
        ReturnLabelFactory       $returnLabelFactory,
        FileFactory              $fileFactory,
        ScopeConfigInterface     $scopeConfig,
        ReturnLabel              $label
    )
    {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->label = $label;
        $this->fileFactory = $fileFactory;
        $this->returnLabelFactory = $returnLabelFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->_initOrder();
        if (!$order) {
            return $this->resultRedirectFactory->create()->setPath('sales/*/');
        }

        $api_key = $this->getApiKey();
        if (!$api_key) {
            $this->messageManager->addErrorMessage(__('Incorrect plugin configuration, verify the settings'));
            return $this->resultRedirectFactory->create()->setPath('sales/order/view', ['order_id' => $order->getEntityId()]);
        }

        $deliveryData = $this->returnLabelFactory->create()->load($order->getEntityId(), 'order_id');
        $header = ["Content-Type" => "application/json"];
        $this->curl->setHeaders($header);
        $url = $this->getBoxOfHopeUrl();
        $this->curl->post(
            $url . 'api-delivery-ecosystem/v.0.1/delivery/business/label/' . $deliveryData['delivery_id'] . '/api-key/' . $api_key,
            []
        );
        $response = json_decode($this->curl->getBody(), true);
        if ($this->curl->getStatus() !== 201 && $this->curl->getStatus() !== 200) {
            $this->messageManager->addErrorMessage(__(json_encode($this->curl->getBody())));
        } elseif ($response['status'] !== 'queueing') {
            return $this->resultRedirectFactory->create()->setPath($response['url']);
        } else {
            $this->messageManager->addWarningMessage(__('I’m sorry, but your package is still in the preparation stage.'));
        }

        return $this->resultRedirectFactory->create()->setPath(
            'sales/order/view',
            [
                'order_id' => $order->getEntityId()
            ]
        );
    }

    private function getBoxOfHopeUrl(): string
    {
        $isTestMode = $this->scopeConfig->getValue(
            'boxofhope_returnlabelplugin/general/testing_mode',
            ScopeInterface::SCOPE_STORE,
        );

        return $isTestMode ? 'https://boh-stage.lessclub.dev/' : 'https://boxofhope.pl/';
    }

    private function getApiKey(): ?string
    {
        return $this->scopeConfig->getValue(
            'boxofhope_returnlabelplugin/general/boh_api_key',
            ScopeInterface::SCOPE_STORE,
        );
    }

    protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderRepository->get($id);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            return false;
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage(__('This order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            return false;
        }

        return $order;
    }
}
