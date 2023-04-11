<?php

namespace Boxofhope\ReturnLabelPlugin\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Boxofhope\ReturnLabelPlugin\Model\ReturnLabel;
use Boxofhope\ReturnLabelPlugin\Model\ReturnLabelFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Throwable;

class BohDeliveryController extends Action
{
    protected Registry|null $_coreRegistry = null;
    protected OrderRepositoryInterface $orderRepository;
    protected LoggerInterface $logger;
    protected Curl $curl;
    protected ReturnLabelFactory $returnLabelFactory;
    protected ReturnLabel $label;
    public ScopeConfigInterface $scopeConfig;

    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        Curl $curl,
        ReturnLabelFactory $returnLabelFactory,
        ScopeConfigInterface $scopeConfig,
        ReturnLabel $label
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->label = $label;
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

        $this->sendRequest($order, $api_key);

        return $this->resultRedirectFactory->create()->setPath('sales/order/view', ['order_id' => $order->getEntityId()]);
    }

    private function sendRequest($order, $api_key)
    {
        try {
            $shippingAddress = $order->getShippingAddress();
            $header = ["Content-Type" => "application/json"];
            $this->curl->setHeaders($header);
            $url = $this->getBoxOfHopeUrl();
            $returnData = json_encode($this->createRequestDataForGenerateDelivery($shippingAddress));
            $this->curl->post($url . 'api-delivery-ecosystem/v.0.1/delivery/business/api-key/' . $api_key , $returnData);
            $result = json_decode($this->curl->getBody(), true);

            if (in_array($this->curl->getStatus(), [201, 200])) {
                $this->createNewLabelRecord($order, $result);
                $this->messageManager->addSuccessMessage(__('Your package is currently in preparation'));
            } else {
                $this->messageManager->addErrorMessage(__($result['error']));
            }
        } catch (Throwable $e) {
            $this->messageManager->addErrorMessage(__('We can\'t process your request' . $e->getMessage()));
            $this->logger->critical($e);
        }
    }

    private function createNewLabelRecord($order, $result)
    {
        $returnBoh = $this->returnLabelFactory->create();
        $returnBoh->setOrderId($order->getEntityId());
        $returnBoh->setDeliveryId($result['package_id']);
        $returnBoh->setReturnCode($result['reference_code']);
        $returnBoh->save();
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

    private function createRequestDataForGenerateDelivery(OrderAddressInterface $shippingAddress): array
    {
        return [
            "pickup" => [
                "name" => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
                "street" => "6146 Honey Bluff Parkway 2",
                "postcode" => $shippingAddress->getPostcode(),
                "city" => $shippingAddress->getCity(),
                "email" => $shippingAddress->getEmail(),
                "phone" => $shippingAddress->getTelephone(),
                "company" => $shippingAddress->getCompany()
            ],
            "parcels" => [
                "width" => 10,
                "depth" => 10,
                "height" => 10,
                "weight" => 2,
                "value" => 100,
                "description" => "test"
            ]
        ];
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
