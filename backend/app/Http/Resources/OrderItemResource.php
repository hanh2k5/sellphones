<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'product_id'   => $this->product_id,
            'product_name' => $this->product ? $this->product->name : 'Sản phẩm đã bị xóa',
            'quantity'     => $this->quantity,
            'price'        => (float) $this->price_at_purchase,
            'total'        => (float) ($this->quantity * $this->price_at_purchase),
            'product'      => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
