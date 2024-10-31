<?php

namespace QPMN\Partner\Libs\HttpClient\Obj;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * @property int $id
 * @property int $quantity
 * @property string $comment
 * 
 */
class QPMNOrderItem extends Base
{
    public int $id;
    public int $productId;
    public int $quantity;
    public $metaData = [];

    public function CGPData()
    {
        return [
            'product_id' => $this->productId,
            'qty' => $this->quantity,
            'meta_data' => $this->metaData
        ];
    }
}
