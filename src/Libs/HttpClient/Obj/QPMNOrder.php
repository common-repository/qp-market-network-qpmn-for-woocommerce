<?php

namespace QPMN\Partner\Libs\HttpClient\Obj;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * @property string $id
 * @property string $comment
 * @property array<QPMNOrderItem> $orderItems
 * @property QPMNBillingAddress $deliveryAddress
 */
class QPMNOrder extends Base
{
    public string $id;
    public bool $setPaid = false;
    public array $orderItems;
    public array $billingAddress;
    public array $shippingAddress;
    public array $metaData = [];

    public function CGPData()
    {
        //prepare order item array
        $orderItems = array_map(function ($i) {
            /**
             * @var QPMNOrderItem $i
             */
            return $i->CGPData();
        }, $this->orderItems);

        return [
            'set_paid' => $this->setPaid,
            'line_items' => $orderItems,
            'billing' => (array)$this->billingAddress,
            'shipping' => (array)$this->shippingAddress,
            'meta_data' => $this->metaData
        ];
    }
}
